<?php
class NoDirtyDnsSwoole extends NoDirtyDns {
    /**
     * @var array
     */
    protected static $DnsCache=array();
    /**
     * @var array
     */
    protected static $DnsCacheFake=array();
    /**
     * @param string $DnsMsg
     * @param array $DnsSvrSocket
     * @param int $Timeout
     * @return mixed|string
     */
    protected function SendDnsMsg(string $DnsMsg,array $DnsSvrSocket,int $Timeout){
        $Msg='';
        $Client=new Swoole\Client(SWOOLE_SOCK_UDP);
        $Client->connect($DnsSvrSocket['IP'],$DnsSvrSocket['Port'],$Timeout/1000);
        for ($Times=0;$Times<$this->Retry;$Times++){
            $Client->send($DnsMsg);
            if ($Msg=@$Client->recv()) break;
        }
        $Client->close();
        return $Msg;
    }

    /**
     * start NoDirtyDnsSwoole
     */
    public function Start(){
        try {
            $this->EchoInfo();
            $this->ServerSocket=new Swoole\Server('0.0.0.0',0,SWOOLE_PROCESS,1);
            foreach ($this->ListenServer as $ID=>$Info)
                $this->ServerSocket->addListener($Info['IP'],$Info['Port'],$Info['SockType']);

            $this->ServerSocket->on('Receive', function (){});
            $this->ServerSocket->on('Packet',function ($Server,$ClientMsg,$Client){
                $ReturnMsg=$this->DiscernDns($ClientMsg);
                $Server->sendTo($Client['address'],$Client['port'],$ReturnMsg?$ReturnMsg:'null!');
                if ($this->Debug) $this->Debug($ClientMsg,$ReturnMsg);
            });
            $this->ServerSocket->start();
        }catch (Exception $Err){
            printf("\n !!Error %s (%s);In %s (Line:%s) \n\n",$Err->getMessage(),$Err->getCode(),$Err->getFile(),$Err->getLine());
        }
    }
}