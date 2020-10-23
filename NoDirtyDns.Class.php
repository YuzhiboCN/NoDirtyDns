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
     * @var bool
     */
    protected $Debug=false;
    protected $DebugNum=0;
    protected $DebugInfo=array();
    protected $ErrInfo;

    /**
     * @param string | array $Socks
     * @param string $Protocol
     * @param string $TypeStr
     * @return array
     */
    protected function CheckSock($Socks,string $Protocol,string $TypeStr=''){
        return array_map(function ($Sock)use($Protocol,$TypeStr){
            $SockArr=explode('#',$Sock);
            $IP=$SockArr[0]??'';
            $Port=(int)($SockArr[1]??53);
            if(!filter_var($IP,FILTER_VALIDATE_IP))
                die($TypeStr." IP is error!");
            if($Port<0 or $Port>65535)
                die($TypeStr." Port is error!");
            return sprintf('%s://%s:%s',$Protocol,$IP,$Port);
        },(array)$Socks);
    }

    /**
     * echo help menu
     */
    protected function EchoHelpMsg(){
        echo <<<EOF
Use: 
    1. php start.php -l 127.0.0.1 -f 1.1.1.1
    2. php start.php -l 127.0.0.1 -f 1.1.1.1 -i 223.5.5.5 -i 114.114.114.114 -o 8.8.8.8 -o 8.8.4.4
    3. php start.php -l 127.0.0.1#53 -f 1.1.1.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53
    4. php start.php -l 127.0.0.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53 -f 1.1.1.1#53 -t 300 -p udp --ft 150 --fr 10
Option:
    [requisite]
    -f      string      Fake DNS Server Sock (only)
    
    [not requisite]
    -l      string      Listen DNS Server Sock (only)       default:127.0.0.1#53
    -i      string      Inside DNS Server Sock (array)      default:223.5.5.5#53
    -o      string      Outside DNS Server Sock (array)     default:8.8.8.8#53
    -p      string      Use DNS protocol (udp|tcp|both)     default:udp
    -t      int         Send DNS timeout (millisecond)      default:300
    -ft     int         Fake DNS timeout (millisecond)      default:100
    -fr     int         Fake DNS Retry times (>0)           default:6
    
    [other]
    --help  null        echo help menu
    --debug null        debug mode
EOF;
        exit;
    }

    /**
     * NoDirtyDns constructor.protocol
     */
    public function __construct(){
        $Opt=getopt('l:i:o:f:p:t:',array('ft:','fr:','debug::','help::'));
        if (isset($Opt['help'])) $this->EchoHelpMsg();
        if (isset($Opt['debug'])) $this->Debug=true;

        if (!($Opt['l']??'')) $Opt['l']='127.0.0.1#53';
        if (!($Opt['i']??array())) $Opt['i']='223.5.5.5#53';
        if (!($Opt['o']??array())) $Opt['o']='8.8.8.8#53';
        if (!($Opt['p']??'')) $Opt['p']='udp';
        if ($Opt['t']??'') $this->SendDnsMsgTimeout=$Opt['t'];
        if ($Opt['ft']??'') $this->FakeDnsTimeout=$Opt['ft'];
        if ($Opt['fr']??'') $this->Retry=$Opt['fr'];

        if (is_array($Opt['l'])) die("Listen IP mast only one!");
        if (is_array($Opt['f'])) die("Fake IP mast only one!");
        if (!($Opt['f']??'')) die("Fake IP is null!\n");
        if (is_array($Opt['p'])) die("Protocol IP mast only one!");

        $this->ListenServer=$this->CheckSock($Opt['l'],$Opt['p'],'Listen');
        $this->FakeDnsServer=$this->CheckSock($Opt['f'],$Opt['p'],'Fake');
        $this->InsideDnsServer=$this->CheckSock($Opt['i'],$Opt['p'],'Inside');
        $this->OutsideDnsServer=$this->CheckSock($Opt['o'],$Opt['p'],'Outside');
    }

    /**
     * @param string $DnsSvrSocket
     * @param string $DnsMsg
     * @param int $Timeout
     * @return false|string
     */
    protected function SendDnsMsg(string $DnsSvrSocket,string $DnsMsg,int $Timeout){
        $Msg='';
        for ($Times=0;$Times<$this->Retry;$Times++){
            $DnsSocket=stream_socket_client($DnsSvrSocket, $Errno, $ErrStr, STREAM_SERVER_BIND);
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
     */
    protected function DiscernDns(string $DnsMsg){
        $IsFake=false;
        if (($FakeMsg=$this->SendDnsMsg(current($this->FakeDnsServer),$DnsMsg,$this->FakeDnsTimeout)) !=''){
            $Servers=$this->OutsideDnsServer;$IsFake=true;
        }else
            $Servers=$this->InsideDnsServer;
        foreach ($Servers as $Server)
            if ($TrueMsg=$this->SendDnsMsg($Server,$DnsMsg,$this->SendDnsMsgTimeout))
                break;
        $this->DebugInfo=array(
            'IsFake'=>$IsFake,'FakeMsg'=>$FakeMsg,'ErrInfo'=>$this->ErrInfo
        );
        return $TrueMsg??'';
    }

    /**
     * @param string $RecvMsg
     * @param string $ReturnMsg
     */
    protected function Debug(string $RecvMsg,string $ReturnMsg){
        echo sprintf("No. %s :\n",$this->DebugNum++);
        echo sprintf("RecvMsg: %s\n",$RecvMsg);
        echo sprintf("ReturnMsg: %s\n",$ReturnMsg);
        echo json_encode($this->DebugInfo,JSON_UNESCAPED_UNICODE)."\n";
    }

    /**
     * start NoDirtyDns
     */
    public function Start(){
        if (!$this->ServerSocket=stream_socket_server(current($this->ListenServer),$Errno, $ErrStr,STREAM_SERVER_BIND))
            die("$ErrStr ($Errno)");
        do {
            $ClientMsg=stream_socket_recvfrom($this->ServerSocket,1024,0,$Peer);
            stream_socket_sendto($this->ServerSocket,$ReturnMsg=$this->DiscernDns($ClientMsg),0,$Peer);
            if ($this->Debug) $this->Debug($ClientMsg,$ReturnMsg);
        } while (true);
    }
}