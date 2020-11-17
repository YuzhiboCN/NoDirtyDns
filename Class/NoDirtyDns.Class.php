<?php
class NoDirtyDns{
    /**
     * @var array Listen DNS Server socket
     */
    protected $ListenServer;
    /**
     * @var array Inside DNS Server socket
     */
    protected $InsideDnsServer;
    /**
     * @var array Outside DNS Server socket
     */
    protected $OutsideDnsServer;
    /**
     * @var array Fake DNS Server socket
     */
    protected $FakeDnsServer;
    /**
     * @var int Send DNS timeout (millisecond)
     */
    protected $SendDnsMsgTimeout=200;
    /**
     * @var int Fake DNS timeout (millisecond)
     */
    protected $FakeDnsTimeout=100;
    /**
     * @var int Fake DNS Retry times
     */
    protected $Retry=6;
    /**
     * @var false|resource
     */
    protected $ServerSocket;
    /**
     * @var array
     */
    protected static $DnsCache=array();
    /**
     * @var array
     */
    protected static $DnsCacheFake=array();
    /**
     * @var bool
     */
    protected $Debug=false;
    protected $DebugNum=0;
    protected $DebugInfo=array();
    protected $ErrInfo;

    /**
     * default param
     */
    const DefaultProtocol='udp';
    const DefaultPort=53;
    const DefaultSockType=array('null','IPv4-TCP','IPv4-UDP','IPv6-TCP','IPv6-UDP');

    /**
     * @param string | array $Socks
     * @param string $TypeStr
     * @return array
     */
    protected function CheckSock($Socks,string $TypeStr=''){
        return array_map(function ($Sock)use($TypeStr){
            preg_match('/^([a-zA-Z]+)?.*?([0-9\.]+)(?:#?([0-9]+)?)$/i',$Sock,$SockArr);
            if (!isset($SockArr[0]))
                die($TypeStr." sock is error!");

            $Protocol=strtolower($SockArr[1]?$SockArr[1]:self::DefaultProtocol);
            $IP=$SockArr[2]??'';
            $Port=(int)($SockArr[3]??self::DefaultPort);

            if ($Protocol=='tcp')
                $SockType=1;
            elseif ($Protocol=='udp')
                $SockType=2;
            else
                die($TypeStr." Protocol is error!");

            if(filter_var($IP,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))
                $SockType+=0;
            elseif (filter_var($IP,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
                $SockType+=2;
            else
                die($TypeStr." IP is error!");

            if($Port<0 or $Port>65535)
                die($TypeStr." Port is error!");
            return array('IP'=>$IP,'Port'=>$Port,'SockType'=>$SockType);
        },(array)$Socks);
    }

    /**
     * @param string $Msg
     * @param array $DnsSvrSocket
     * @param int $Timeout
     * @param array $CacheArr
     * @param bool $UseCache
     * @return string
     * @throws Exception
     */
    protected function DnsCache(string $Msg,array $DnsSvrSocket,int $Timeout,array &$CacheArr,bool &$UseCache=true){
        if (($Answer=DnsParser::CacheRead($Msg,$CacheArr)) === null){
            $Answer=$this->SendDnsMsg($Msg,$DnsSvrSocket,$Timeout);
            DnsParser::CacheWrite($Msg,$Answer,$CacheArr);
            $UseCache=false;
        }
        return $Answer??'';
    }

    /**
     * echo help menu
     */
    protected function EchoHelpMsg(){
        echo <<<EOF
Use: 
    1. php start[Swoole].php -l 127.0.0.1 -f 1.1.1.1
    2. php start[Swoole].php -l 127.0.0.1 -f 1.1.1.1 -i 223.5.5.5 -i 114.114.114.114 -o 8.8.8.8 -o 8.8.4.4
    3. php start[Swoole].php -l 127.0.0.1#53 -f 1.1.1.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53
    4. php start[Swoole].php -l 127.0.0.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53 -f 1.1.1.1#53 -t 300 --ft 150 --fr 10
Option:
    [requisite]
    -f      string      Fake DNS Server Sock (only)
    
    [not requisite]
    -l      string      Listen DNS Server Sock (only)       default:udp://0.0.0.0#5353
    -i      string      Inside DNS Server Sock (array)      default:udp://223.5.5.5#53
    -o      string      Outside DNS Server Sock (array)     default:udp://8.8.8.8#53
    -t      int         Send DNS timeout (millisecond)      default:200
    -ft     int         Fake DNS timeout (millisecond)      default:100
    -fr     int         Fake DNS Retry times (>0)           default:6
    
    [other]
    --help  null        echo help menu
    --debug null        debug mode
EOF;
        exit;
    }

    /**
     * echo server info
     */
    protected function EchoInfo(){
        foreach (array(
                     'Listen'=>$this->ListenServer,
                     'FakeDns'=>$this->FakeDnsServer,
                     'InsideDns'=>$this->InsideDnsServer,
                     'OutsideDns'=>$this->OutsideDnsServer,
                 ) as $Key=>$Info){
            $SockStr='';
            foreach ($Info as $ID=>$SockArr)
                $SockStr.=sprintf("%s:%s/%s:%s  ",
                    $ID, self::DefaultSockType[$SockArr['SockType']],
                    $SockArr['IP'], $SockArr['Port']
                );
            printf("%s : %s \n",$Key,$SockStr);
        }
        foreach (array(
                     'Send DNS timeout'=>$this->SendDnsMsgTimeout,
                     'Fake DNS timeout'=>$this->FakeDnsTimeout,
                     'Fake DNS Retry times'=>$this->Retry
                 ) as $Key=>$Info){
            printf("%s : %s \n",$Key,implode(' ; ',(array)$Info));
        }
    }

    /**
     * NoDirtyDns constructor.protocol
     */
    public function __construct(){
        $Opt=getopt('l:i:o:f:t:',array('ft:','fr:','debug::','help::'));
        if (isset($Opt['help'])) $this->EchoHelpMsg();
        if (isset($Opt['debug'])) $this->Debug=true;

        if (!($Opt['l']??'')) $Opt['l']='udp://127.0.0.1#5355';
        if (!($Opt['i']??array())) $Opt['i']='udp://223.5.5.5#53';
        if (!($Opt['o']??array())) $Opt['o']='udp://8.8.8.8#53';
        if ($Opt['t']??'') $this->SendDnsMsgTimeout=$Opt['t'];
        if ($Opt['ft']??'') $this->FakeDnsTimeout=$Opt['ft'];
        if ($Opt['fr']??'') $this->Retry=$Opt['fr'];

        if (is_array($Opt['l'])) die("Listen IP mast only one!");
        if (is_array($Opt['f'])) die("Fake IP mast only one!");
        if (!($Opt['f']??'')) die("Fake IP is null!\n");

        $this->ListenServer=$this->CheckSock($Opt['l'],'Listen');
        $this->FakeDnsServer=$this->CheckSock($Opt['f'],'Fake');
        $this->InsideDnsServer=$this->CheckSock($Opt['i'],'Inside');
        $this->OutsideDnsServer=$this->CheckSock($Opt['o'],'Outside');
    }

    /**
     * @param array $Sock
     * @return string
     */
    protected function SockArrStr(array $Sock){
        return sprintf('%s://%s:%s',strtolower(
            substr(self::DefaultSockType[$Sock['SockType']],5)),
            $Sock['IP'],$Sock['Port']);
    }

    /**
     * @param string $DnsMsg
     * @param array $DnsSvrSocket
     * @param int $Timeout
     * @return false|string
     */
    protected function SendDnsMsg(string $DnsMsg,array $DnsSvrSocket,int $Timeout){
        $Msg='';
        for ($Times=0;$Times<$this->Retry;$Times++){
            $DnsSocket=stream_socket_client($this->SockArrStr($DnsSvrSocket), $Errno, $ErrStr, STREAM_SERVER_BIND);
            $this->ErrInfo=array($Errno, $ErrStr);
            stream_set_timeout($DnsSocket,0,$Timeout*1000);
            fwrite($DnsSocket,$DnsMsg);
            $Msg=fread($DnsSocket,1024);
            fclose($DnsSocket);
            if ($Msg) break;
        }
        return $Msg;
    }

    /**
     * @param string $DnsMsg
     * @return false|string
     * @throws Exception
     */
    protected function DiscernDns(string $DnsMsg){
        $IsFake=false;$UseCache=$UseCacheFake=true;
        if (($FakeMsg=$this->DnsCache($DnsMsg,current($this->FakeDnsServer),$this->FakeDnsTimeout,self::$DnsCacheFake,$UseCacheFake))){
            $Servers=$this->OutsideDnsServer;$IsFake=true;
        }else
            $Servers=$this->InsideDnsServer;
        foreach ($Servers as $Server)
            if ($TrueMsg=$this->DnsCache($DnsMsg,$Server,$this->SendDnsMsgTimeout,self::$DnsCache,$UseCache))
                break;
        $this->DebugInfo=array(
            'UseCache'=>$UseCache,'UseCacheFake'=>$UseCacheFake,'IsFake'=>$IsFake,'FakeMsg'=>$FakeMsg,'ErrInfo'=>$this->ErrInfo
        );
        return $TrueMsg??'';
    }

    /**
     * @param string $RecvMsg
     * @param string $ReturnMsg
     * @throws Exception
     */
    protected function Debug(string $RecvMsg,string $ReturnMsg){
        $RecvMsg=DnsParser::GetInstance()->ParserStr($RecvMsg);
        $ReturnMsg=DnsParser::GetInstance()->ParserStr($ReturnMsg);
        $FakeMsg=$this->DebugInfo['FakeMsg']?DnsParser::GetInstance()->ParserStr($this->DebugInfo['FakeMsg']):'';
        printf("No. %s :\n  RecvMsg: %s  ReturnMsg: %s ",$this->DebugNum++,$RecvMsg,$ReturnMsg);
        printf(" UseCache:%s UseCacheFake:%s",$this->DebugInfo['UseCache']?'Yes':'No',$this->DebugInfo['UseCacheFake']?'Yes':'No');
        printf(" IsFake:%s  FakeMsg:%s\n\n",$this->DebugInfo['IsFake']?'Yes':'No',$FakeMsg);
    }

    /**
     * start NoDirtyDns
     */
    public function Start(){
        try {
            $this->EchoInfo();
            foreach ($this->ListenServer as $ID=>$Info)
                $this->ServerSocket=stream_socket_server($this->SockArrStr($Info),$Errno, $ErrStr,STREAM_SERVER_BIND);

            do {
                $ClientMsg=stream_socket_recvfrom($this->ServerSocket,1024,0,$Peer);
                stream_socket_sendto($this->ServerSocket,$ReturnMsg=$this->DiscernDns($ClientMsg),0,$Peer);
                if ($this->Debug) $this->Debug($ClientMsg,$ReturnMsg);
            } while (true);
        }catch (Exception $Err){
            printf("\n !!Error %s (%s);In %s (Line:%s) \n\n",$Err->getMessage(),$Err->getCode(),$Err->getFile(),$Err->getLine());
        }
    }
}