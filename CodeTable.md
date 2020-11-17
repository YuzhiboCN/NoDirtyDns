0 quote

    https://www.cnblogs.com/549294286/p/5172448.html

1 DNS Classes

    Decimal     Name                        Reference
    0           Reserved	                RFC6895
    1           Internet(IN)	            RFC1035
    2           Unassigned	 
    3           Chaos(CH)	 
    4           Hesiod(HS)	 
    5-253	    Unassigned	 
    254	        QCLASS NONE	                RFC2136
    255	        QCLASS * (ANY)	            RFC1035
    256-65279	Unassigned	 
    65280-65534	Reserved for Private Use	RFC6895
    65535	    Reserved	                RFC6895

2 DNS OpCodes

    OpCode	    Name	                    Reference
    0	        Query	                    RFC1035
    1	        IQuery(Inverse Query)	    RFC3425
    2	        Status	                    RFC1035
    3	        Unsassined	 
    4	        Notify	                    RFC1996
    5	        Update	                    RFC2136
    6-15	    Unassigned	 
 

3 DNS RCODEs

    Rcode	    Name	        Description	                            Reference
    0	        NoError	        No Error	                            RFC1035
    1	        FormErr	        Format Error	                        RFC1035
    2	        ServFail	    Server Failure	                        RFC1035
    3	        NXDomain	    Non-Existent Domain	                    RFC1035
    4	        NotImp	        Not Implemented	                        RFC1035
    5	        Refused	        Query Refused	                        RFC1035
    6	        YXDomain	    Name Exists when it should not	        RFC2136
    7	        YXRRSet	        RR Set Exists when it should not	    RFC2136
    8	        NXRRSet	        RR set that should exist does not	    RFC2136
    9	        NotAuth	        Server Not Authoritative for zone	    RFC2136
    10	        NotZone	        Not AUthorized	                        RFC2845
    11-15   	Unassigned	 	 
    16	        BADVERS	        Bad OPT Version	                        RFC6891
    16	        BADSIG	        TSIG Signature Failure	                RFC2845
    17	        BADKEY	        Key not revognized	                    RFC2845
    18	        BADTIME	        Signature out of time window	        RFC2845
    19	        BADMODE	        Bad TKEY Mode	                        RFC2930
    20	        BADNAME     	Duplicate key name	                    RFC2930
    21	        BADALG	        Algorithm not supported	                RFC2930
    22	        BADTRUNC	    Bad Truncation	                        RFC4635
    23-3840	    Unassigned	 	 
    3841-4095	Reserved for Private Use	 	 
    4096-65534	Unassigned	 	 
    65535	    Reserved	 	 
 

4 DNS Label Types

    DNS label的最高两位来标识该label的类型
    
    Value       Type                                                Status      Reference
    00	        普通label. 低6位是该label的长度	                    Standard	RFC1035
    11	        压缩label. 低6位和接下来的8位标识它相对于包头的偏移量	Standard	RFC1035
    01	        扩展类型的label. 低6位表示label的类型那个	            Standard	RFC1035
    10	        未分配	 	 
 
5 DNS Resource type

    TYPE	    Value	    Meaning	                                    Reference
    A	        1	        a host address	                            [RFC1035]
    NS	        2	        an authoritative name server	            [RFC1035]
    MD	        3	        a mail destination(OBSOLETE - use MX)	    [RFC1035]
    MF	        4	        a mail forwarder(OBSOLETE - use MX) 	    [RFC1035]
    CNAME	    5	        the canonical name for an alias	            [RFC1035]
    SOA	        6	        marks the start of a zone of authority	    [RFC1035]
    MB	        7	        a mailbox domain name (EXPERIMENTAL)	    [RFC1035]
    MG	        8       	a mail group member (EXPERIMENTAL)	        [RFC1035]
    MR	        9       	a mail rename domain name (EXPERIMENTAL)	[RFC1035]
    NULL       	10      	a null RR (EXPERIMENTAL)                    [RFC1035]	 
    WKS     	11      	a well known service description	        [RFC1035]
    PTR     	12	        a domain name pointer	                    [RFC1035]
    HINFO   	13	        host information	                        [RFC1035]
    MINFO   	14      	mailbox or mail list information	        [RFC1035]
    MX	        15      	mail exchange	                            [RFC1035]
    TXT     	16      	text strings	                            [RFC1035]
    RP      	17      	for Responsible Person	                    [RFC1183]
    AFSDB   	18      	for AFS Data Base location	                [RFC1183][RFC5864]
    X25	        19	        for X.25 PSDN address	                    [RFC1183]
    ISDN    	20	        for ISDN address	                        [RFC1183]
    RT	        21      	for Route Through	                        [RFC1183]
    NSAP    	22      	for NSAP address, NSAP style A record	    [RFC1706]
    NSAP-PTR	23	        for domain name pointer, NSAP style	        [RFC1348][RFC1637][RFC1706]
    SIG	        24	        for security signature	 
    KEY	        25	        for security key	 
    PX	        26	        X.400 mail mapping information	            [RFC2163]
    GPOS	    27	        Geographical Position	                    [RFC1712]
    AAAA	    28      	IP6 Address	                                [RFC3596]
    LOC     	29	        Location Information	                    [RFC1876]
    NXT     	30	        Next Domain (OBSOLETE)	                    [RFC3755][RFC2535]
    EID	        31	        Endpoint Identifier	 
    NIMLOC  	32	        Nimrod Locator	 
    SRV	        33	        Server Selection	                        [RFC2782]
    ATMA	    34	        ATM Address	 
    NAPTR	    35	        Naming Authority Pointer	                [RFC2915][RFC2168][RFC3403]
    KX	        36	        Key Exchanger	                            [RFC2230]
    CERT    	37      	CERT	                                    [RFC4398]
    A6	        38	        A6 (OBSOLETE - use AAAA)	                [RFC3226][RFC2874][RFC6563]
    DNAME   	39	        DNAME	                                    [RFC6672]
    SINK	    40      	SINK	 
    OPT	        41      	OPT	                                        [RFC6891][RFC3225]
    APL	        42	        APL	                                        [RFC3123]
    DS	        43	        Delegation Signer	                        [RFC4034][RFC3658]
    SSHFP	    44	        SSH Key Fingerprint	                        [RFC4255]
    IPSECKEY	45      	IPSECKEY	                                [RFC4025]
    RRSIG	    46	        RRSIG	                                    [RFC4034][RFC3755]
    NSEC	    47      	NSEC	                                    [RFC4034][RFC3755]
    DNSKEY	    48      	DNSKEY	                                    [RFC4034][RFC3755]
    DHCID	    49      	DHCID	                                    [RFC4701]
    NSEC3	    50      	NSEC3	                                    [RFC5155]
    NSEC3PARAM	51	        NSEC3PARAM	                                [RFC5155]
    TLSA	    52	        TLSA	                                    [RFC6698]
    Unassigned	53-54	 	 
    HIP	        55	        Host Identity Protocol	                    [RFC5205]
    NINFO	    56	        NINFO	                                    [JimReid]
    RKEY	    57	        RKEY	                                    [JimReid]
    TALINK	    58	        Trust Anchor LINK	                        [WouterWijngaards]
    CDS	        59      	Child DS	                                [GeorgeBarwood]
    Unassigned	60-98	 	 
    SPF	        99	                                                    [RFC-ietf-spfbis-4408bis-21]	 
    UINFO	    100     	                                            [IANA-Reserved]	 
    UID	        101     	                                            [IANA-Reserved]	 
    GID	        102     	                                            [IANA-Reserved]	 
    UNSPEC	    103	                                                    [IANA-Reserved]	 
    NID	        104     	                                            [RFC6742]	 
    L32	        105	                                                    [RFC6742]	 
    L64	        106	                                                    [RFC6742]	 
    LP	        107	                                                    [RFC6742]	 
    EUI48	    108	        an EUI-48 address	                        [RFC7043]
    EUI64	    109	        an EUI-64 address	                        [RFC7043]
    Unassigned	110-248	 	 
    TKEY	    249	        Transaction Key                             [RFC2930]	 
    TSIG	    250     	Transaction Signature	                    [RFC2845]
    IXFR    	251	        incremental transfer	                    [RFC1995]
    AXFR    	252	        transfer of an entire zone	                [RFC1035][RFC5936]
    MAILB   	253     	mailbox-related RRs (MB, MG or MR)	        [RFC1035]
    MAILA	    254	        mail agent RRs (OBSOLETE - see MX)	        [RFC1035]
    *	        255	        all records the server/cache has available	[RFC1035][RFC6895]
    URI	        256     	URI	[PatrikFaltstrom]
    CAA     	257	        Certification Authority Restriction	        [RFC6844]
    Unassigned	258-32767	 	 
    TA	        32768	    DNSSEC Trust Authorities	 
    DLV	        32769	    DNSSEC Lookaside Validation	                [RFC4431]
    Unassigned	32770-65279	 	 
    Private use	65280-65534	 	 
    Reserved	65535	 	 
 
6 EDNS Version

    Range	    Description	        Reference
    0	        EDNS version 0	    RFC6891
    1-255	    Unassigned	 
 

7 DNS EDNS0 Option Codes (OPT)

    Value	    Name	                            Status	    Reference
    0	        Reserved	 	                                [RFC6891]
    1	        LLQ	                                On-hold	    [http://files.dns-sd.org/draft-sekar-dns-llq.txt]
    2	        UL	                                On-hold	    [http://files.dns-sd.org/draft-sekar-dns-ul.txt]
    3	        NSID	                            Standard	[RFC5001]
    4	        Reserved	 	 
    5	        DAU	                                Standard	[RFC6975]
    6	        DHU	                                Standard	[RFC6975]
    7	        N3U	                                Standard	[RFC6975]
    8	        edns-client-subnet	                Optional	 
    9-65000	    Unassigned	 	 
    65001-65534	Reserved for Local/Experimental Use	 	        [RFC6891]
    65535	    Reserved for future expansion	 	            [RFC6891]