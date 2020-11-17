<?php
class DnsParser{
    protected static $Object;
    static $Cache=array();

    /**
     * @var array
     */
    protected $Header=array();
    /**
     * @var array
     */
    protected $Flags=array();

    /**
     * @var int
     */
    protected $Offset=0;

    /**
     * @var array
     */
    protected $DnsData=array();

    /**
     * Dns Header
     */
    protected const DnsHeader=array(
        'TransactionID', 'Flags', 'Questions',
        'Answer', 'Authority', 'Additional'
    );

    /**
     * Dns Header flags bit length
     * QR(Response) Opcode(Option code) AA(Authoritative) TC(Truncated)
     * RD(Recursion Desired) RA(Recursion Available) Z(reserve) rcode(Reply code)
     */
    protected const DnsHeaderFlag=array(
        'QR'=>1, 'Opcode'=>4, 'AA'=>1, 'TC'=>1,
        'RD'=>1, 'RA'=>1, 'Z'=>3, 'Rcode'=>4
    );

    /**
     * Resource classes
     * see label 1 of file CodeTable.md
     */
    protected const Classes=array(
        'IN'=>1,'CH'=>3,'HS'=>4,'NONE'=>254,'ANY'=>255
    );

    /**
     * Resource data type
     * see label 5 of file CodeTable.md
     */
    protected const RDType=array(
        'A'=>1,'NS'=>2,'MD'=>3,'MF'=>4,'CNAME'=>5,'SOA'=>6,'MB'=>7,'MG'=>8,'MR'=>9,
        'NULL'=>10,'WKS'=>11,'PTR'=>12,'HINFO'=>13,'MINFO'=>14,'MX'=>15,'TXT'=>16,'RP'=>17,'AFSDB'=>18,'X25'=>19,
        'ISDN'=>20,'RT'=>21,'NSAP'=>22,'NSAP-PTR'=>23,'SIG'=>24,'KEY'=>25,'PX'=>26,'GPOS'=>27,'AAAA'=>28,'LOC'=>29,
        'NXT'=>30,'EID'=>31,'NIMLOC'=>32,'SRV'=>33,'ATMA'=>34,'NAPTR'=>35,'KX'=>36,'CERT'=>37,'A6'=>38,'DNAME'=>39,
        'SINK'=>40,'OPT'=>41,'APL'=>42,'DS'=>43,'SSHFP'=>44,'IPSECKEY'=>45,'RRSIG'=>46,'NSEC'=>47,'DNSKEY'=>48,'DHCID'=>49,
        'NSEC3'=>50,'NSEC3PARAM'=>51,'TLSA'=>52,'HIP'=>55,'NINFO'=>56,'RKEY'=>57,'TALINK'=>58,'CDS'=>59,'SPF'=>99,
        'ANY'=>255,'CAA'=>257
    );
    protected const OPT_TCP_KEEPALIVE = 11;

    protected function Init(){
        $this->Offset=0;
        $this->Header=$this->Flags=$this->DnsData=array();
    }

    /**
     * @return DnsParser
     * @throws Exception
     */
    static function GetInstance(){
        if(!(self::$Object instanceof self))
            self::$Object=new static();
        return self::$Object;
    }

    /**
     * @param string $Msg
     * @param int $Offset
     * @param int $Depth
     * @return array
     */
    protected function ParseLists(string $Msg,int &$Offset,int $Depth=127){
        $List=array();
        while (true){
            if (!isset($Msg[$Offset]))
                return array();
            if (($Length=ord($Msg[$Offset]))===0){
                $Offset += 1;
                break;
            }

            if (($Length & 0xc0) === 0xc0 && isset($Msg[$Offset + 1]) && $Depth) {
                $LabelOffset=($Length & ~0xc0)<<8 | ord($Msg[$Offset+1]);
                if ($LabelOffset>=$Offset)
                    return array();
                $Offset += 2;
                $NewList=$this->ParseLists($Msg,$LabelOffset,$Depth-1);
                if ($NewList === null)
                    return array();
                $List=array_merge($List,$NewList);
                break;
            }

            if ($Length & 0xc0 || !isset($Msg[$Offset+$Length-1]))
                return array();

            $List[]=substr($Msg,$Offset+1,$Length++);
            $Offset+=$Length;
        }
        return $List;
    }

    /**
     * @param string $Msg
     * @param int $Offset
     * @return string
     */
    protected function ParseDomain(string $Msg,int &$Offset){
        $Lists=$this->ParseLists($Msg,$Offset);
        if($Lists===null) return '';
        return implode('.',
            array_map(function ($List){
                return addcslashes($List,"\0..\40.\177");
            },$Lists)
        );
    }

    /**
     * @param string $Msg
     * @param string $Type
     * @param int $RDLength
     * @param $Offset
     * @param $Expected
     * @return array|false|string
     */
    protected function ParseRDType(string $Msg,string $Type,int $RDLength,&$Offset,$Expected){
        $RData=null;
        switch ($Type){
            case self::RDType['A']:
                if ($RDLength === 4){
                    $RData=inet_ntop(substr($Msg,$Offset,$RDLength));
                    $Offset+=$RDLength;
                }
                break;
            case self::RDType['AAAA']:
                if ($RDLength === 16){
                    $RData=inet_ntop(substr($Msg,$Offset,$RDLength));
                    $Offset+=$RDLength;
                }
                break;
            case self::RDType['CNAME'] || self::RDType['PTR'] || self::RDType['NS']:
                $RData=$this->ParseDomain($Msg,$Offset);
                break;
            case self::RDType['TXT']:
                $RData=array();
                while($Offset<$Expected){
                    $Length=ord($Msg[$Offset]);
                    $RData[]=(string)substr($Msg,$Offset+1,$Length++);
                    $Offset+=$Length;
                }
                break;
            case self::RDType['MX']:
                if ($RDLength>2){
                    list($Priority)=array_values(unpack('n',substr($Msg,$Offset,2)));
                    $Offset+=2;
                    $Target=$this->ParseDomain($Msg,$Offset);
                    $RData=array(
                        'Priority'=>$Priority,'Target'=>$Target
                    );
                }
                break;
            case self::RDType['SRV']:
                if ($RDLength>6){
                    list($Priority,$Weight,$Port)=array_values(unpack('n*',substr($Msg,$Offset,6)));
                    $Offset+=6;
                    $Target=$this->ParseDomain($Msg,$Offset);
                    $RData=array(
                        'Priority'=>$Priority,'Weight'=>$Weight,'Port'=>$Port,'Target'=>$Target
                    );
                }
                break;
            case self::RDType['SSHFP']:
                if ($RDLength>2) {
                    list($Algorithm,$HASH)=array_values(unpack('C*',substr($Msg,$Offset,2)));
                    $Fingerprint=bin2hex(substr($Msg,$Offset+2,$RDLength-2));
                    $Offset+=$RDLength;
                    $RData=array(
                        'Algorithm'=>$Algorithm,'Type'=>$HASH,'Fingerprint'=>$Fingerprint
                    );
                }
                break;
            case self::RDType['SOA']:
                $MainDomain=$this->ParseDomain($Msg,$Offset);
                $RegDomain=$this->ParseDomain($Msg,$Offset);

                if ($MainDomain!==null && $RegDomain!==null && isset($Msg[$Offset+20-1])){
                    list($Serial,$Refresh,$Retry,$Expire,$TTL)=array_values(unpack('N*', substr($Msg,$Offset,20)));
                    $Offset+=20;
                    $RData=array(
                        'MainDomain'=>$MainDomain,'RegDomain'=>$RegDomain,'Serial'=>$Serial,
                        'Refresh'=>$Refresh,'Retry'=>$Retry,'Expire'=>$Expire,'TTL'=>$TTL
                    );
                }
                break;
            case self::RDType['OPT']:
                $RData=array();
                while (isset($Msg[$Offset+4-1])){
                    list($Code,$Length)=array_values(unpack('n*',substr($Msg, $Offset,4)));
                    $Value=(string)substr($Msg,$Offset+4,$Length);
                    if ($Code === self::OPT_TCP_KEEPALIVE && $Value === '') {
                        $Value = null;
                    } elseif ($Code === self::OPT_TCP_KEEPALIVE && $Length === 2) {
                        list($Value) = array_values(unpack('n',$Value));
                        $Value = round($Value * 0.1, 1);
                    } elseif ($Code === self::OPT_TCP_KEEPALIVE) {
                        break;
                    }
                    $RData[$Code]=$Value;
                    $Offset+=4+$Length;
                }
                break;
            case self::RDType['CAA']:
                if ($RDLength>3){
                    list($Flag,$TagLength)=array_values(unpack('C*',substr($Msg,$Offset,2)));
                    if ($TagLength>0 && $RDLength-2-$TagLength>0){
                        $Tag=substr($Msg,$Offset+2,$TagLength);
                        $Value=substr($Msg,$Offset+2+$TagLength,$RDLength-2-$TagLength);
                        $Offset+=$RDLength;
                        $RData = array(
                            'Flag'=>$Flag,'Tag'=>$Tag,'Value'=>$Value
                        );
                    }
                }
                break;
            default:
                $RData=substr($Msg,$Offset,$RDLength);
                $Offset+=$RDLength;
        }
        return $RData;
    }

    /**
     * @param string $Msg
     * @param int $Offset
     */
    protected function SetHeader(string $Msg,int &$Offset){
        $this->Header=array_combine(
            self::DnsHeader,
            unpack('n*',substr($Msg,0,$Offset+=12))
        );
        if ($this->Header['Questions']==256) $this->Header['Questions']=1;
    }

    /**
     *
     */
    protected function SetFlags(){
        $BitOffset=16;
        foreach (self::DnsHeaderFlag as $Key=>$Bit){
            $this->Flags[$Key]=(
                ($this->Header['Flags']>>($BitOffset-=$Bit)) & (pow(2,$Bit)-1)
            );
        }
    }

    /**
     * @param string $Msg
     * @param int $Offset
     * @return array
     */
    protected function ParserQuestion(string $Msg,int &$Offset){
        $NameArr=$this->ParseLists($Msg,$Offset);
        list($Type,$Class)=array_values(unpack('n*',substr($Msg,$Offset,4)));
        $Offset+=4;
        return array(
            'Domain'=>$NameArr, 'Type'=>$Type, 'Class'=>$Class,
        );
    }

    /**
     * @param string $Msg
     * @param int $Offset
     * @return array
     */
    protected function ParserRecord(string $Msg,int &$Offset){
        $Domain=$this->ParseDomain($Msg,$Offset);
        if ($Domain === null || !isset($Msg[$Offset+10-1])) return array();

        list($Type,$Class)=array_values(unpack('n*',substr($Msg,$Offset,4)));
        $Offset+=4;
        list($TTL)=array_values(unpack('N',substr($Msg,$Offset,4)));
        $Offset+=4;
        list($RDLength) = array_values(unpack('n', substr($Msg,$Offset,2)));
        $Offset+=2;

        // TTL is a UINT32 that must not have most significant bit set for BC reasons
        if ($TTL<0 || $TTL>=1<<31) $TTL=0;
        if (!isset($Msg[$Offset+$RDLength-1])) return array();
        $Expected=$Offset+$RDLength;
        $RData=$this->ParseRDType($Msg,$Type,$RDLength,$Offset,$Expected);
        // ensure parsing record data consumes expect number of bytes indicated in record length
        if ($Offset!==$Expected || $RData===null) return array();

        return array('Domain'=>$Domain,'Type'=>$Type,'Class'=>$Class,'TTL'=>$TTL,'RData'=>$RData);
    }

    /**
     * @param string $Msg
     * @return array
     */
    public function Parser(string $Msg){
        if (!$Msg) return array();
        $this->Init();
        $this->SetHeader($Msg,$this->Offset);
        $this->SetFlags();
        for ($Num=$this->Header['Questions'];$Num>0;$Num--)
            $this->DnsData['Queries'][]=$this->ParserQuestion($Msg,$this->Offset);
        foreach (array('Answer','Authority','Additional') as $Key){
            for ($Num=$this->Header[$Key];$Num>0;$Num--)
                $this->DnsData[$Key][]=$this->ParserRecord($Msg,$this->Offset);
        }
        return array($this->Header,$this->Flags,$this->DnsData);
    }

    /**
     * @param string $Msg
     * @return string
     */
    public function ParserStr(string $Msg){
        $this->Parser($Msg);
        $RDType=array_flip(self::RDType);
        $Classes=array_flip(self::Classes);
        foreach (array('Queries','Answer','Authority','Additional') as $Key){
            isset($this->DnsData[$Key]) && $this->DnsData[$Key]=array_map(function ($Value)use($RDType,$Classes){
                $Value['Type']=$RDType[$Value['Type']]??'NULL';
                $Value['Class']=$Classes[$Value['Class']]??'NULL';
                return $Value;
            },$this->DnsData[$Key]);
        }

        if ($this->Flags['QR']) $DnsStr='Answer:';
        else $DnsStr='Queries:';
        foreach ($this->DnsData['Queries']??array() as $Value)
            $DnsStr.=sprintf('%s %s %s  ',$this->Header['TransactionID'],$Value['Type'],implode('.',$Value['Domain']));
        foreach ($this->DnsData['Answer']??array() as $Value)
            $DnsStr.=sprintf('%s %s  ',$Value['Type'],$Value['RData']);
        return $DnsStr."\n";
    }

    /**
     * @param string $Data
     * @return array
     * @throws Exception
     */
    function GetPath(string $Data){
        $this->Parser($Data);
        $Queries=$this->DnsData['Queries'][0];
        return array_merge(array('#'.$Queries['Type']),$Queries['Domain']);
    }

    /**
     * @param string $Data
     * @return int
     * @throws Exception
     */
    function GetTTL(string $Data){
        $TTL=-1;
        $this->Parser($Data);
        foreach ($this->DnsData['Answer']??array() as $Answer){
            $TTL=$Answer['TTL']??-1;break;
        }
        return $TTL;
    }

    /**
     * @param array $Tree
     * @param mixed $Data
     * @param array $Path
     */
    static protected function TreeInput(array &$Tree,$Data,array $Path){
        if ($Key=array_pop($Path)){
            if (array_count_values($Path)){
                if (!isset($Tree[$Key])) $Tree[$Key]=array();
            }else
                $Tree[$Key]=$Data;
            self::TreeInput($Tree[$Key],$Data,$Path);
        }
    }

    /**
     * @param array $Tree
     * @param array $Path
     * @return array
     */
    static protected function TreeOutput(array &$Tree,array $Path){
        $Key=array_pop($Path);
        if (!isset($Tree[$Key])) return array();
        if (array_count_values($Path))
            return self::TreeOutput($Tree[$Key],$Path);
        else
            return ($Key=='*')?($Tree??array()):($Tree[$Key]??array());
    }

    /**
     * @param string $QueriesData
     * @param string $AnswerData
     * @param array|null $Cache
     * @throws Exception
     */
    static function CacheWrite(string $QueriesData,string $AnswerData,array &$Cache=null){
        if ($Cache===null) $Tree=&self::$Cache;
        else $Tree=&$Cache;
        self::TreeInput($Tree,
            array(
                'Time'=>time(),'TTL'=>self::GetInstance()->GetTTL($AnswerData),
                'Data'=>substr($AnswerData,2,strlen($AnswerData))
            ),
            self::GetInstance()->GetPath($QueriesData)
        );
    }

    /**
     * @param string $QueriesData
     * @param array|null $Cache
     * @return string|null
     * @throws Exception
     */
    static function CacheRead(string $QueriesData,array &$Cache=null){
        if ($Cache===null) $Tree=&self::$Cache;
        else $Tree=&$Cache;
        $CacheData=null;
        $QueriesID=substr($QueriesData,0,2);
        $CacheArr=self::TreeOutput($Tree,self::GetInstance()->GetPath($QueriesData));
        if (($CacheArr['TTL']??-1)<0 or $CacheArr['Time']+$CacheArr['TTL']>time())
            $CacheData=$CacheArr['Data']??null;
        return $CacheData?$QueriesID.$CacheData:$CacheData;
    }
}