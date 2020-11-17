<?php
/**
 * www.baidu.com A IN
 */
$Request=hex2bin('0000010000010000000000000377777705626169647503636f6d0000010001');
/**
 * www.baidu.com A IN
 * www.baidu.com CNAME IN 332 www.a.shifen.com
 * www.a.shifen.com A IN 279 220.181.38.149
 * www.a.shifen.com A IN 279 220.181.38.150
 */
$Response=hex2bin('0000818000010003000000000377777705626169647503636f6d0000010001c00c000500010000014c000f0377777701610673686966656ec016c02b00010001000001170004dcb52695c02b00010001000001170004dcb52696');

include "../Class/DnsParser.Class.php";
try {
    print_r(DnsParser::GetInstance()->Parser($Request));
    print_r(DnsParser::GetInstance()->Parser($Response));
    echo DnsParser::GetInstance()->ParserStr($Request);
    echo DnsParser::GetInstance()->ParserStr($Response);
}catch (Exception $Err){
    echo $Err->getMessage();
}