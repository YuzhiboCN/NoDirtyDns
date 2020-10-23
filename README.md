# NoDirtyDns

无污染Dns是为解决中国大陆用户的Dns污染而开发，建议该模块作为Dns缓存的上游Dns服务器使用。

Install
-------

* Linux / Unix
    
    * OpenWRT
    
            opkg update
            opkg install php7-cli
            
    * Centos
    
        1.Install yum-utils and enable EPEL repository  
               
               [root@vps ~]# yum install epel-release yum-utils -y
               
        2.Download and Install remirepo using yum command
        
               [root@vps ~]# yum install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
               
        3.Based on your requirement, configure the PHP 7.x repository
                
               [root@vps ~]# yum-config-manager --enable remi-php72
               
        4.Install PHP 7.4 along with dependencies
                
               [root@vps ~]# yum install php php-common php-opcache php-mcrypt php-cli php-gd php-curl php-mysql -y

* Windows

        download php7 from https://windows.php.net/download
        release file and configuration
        add bin to path of system variables 

Usage
-----
Option

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

Example

   * OpenWRT / Linux / Unix
    
            1. php-cli ./start.php -l 127.0.0.1 -f 1.1.1.1
            2. php-cli ./start.php -l 127.0.0.1 -f 1.1.1.1 -i 223.5.5.5 -i 114.114.114.114 -o 8.8.8.8 -o 8.8.4.4
            3. php-cli ./start.php -l 127.0.0.1#53 -f 1.1.1.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53
            4. php-cli ./start.php -l 127.0.0.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53 -f 1.1.1.1#53 -t 300 -p udp --ft 150 --fr 10

   * windows
   
            1. php.exe start.php -l 127.0.0.1 -f 1.1.1.1
            2. php.exe start.php -l 127.0.0.1 -f 1.1.1.1 -i 223.5.5.5 -i 114.114.114.114 -o 8.8.8.8 -o 8.8.4.4
            3. php.exe start.php -l 127.0.0.1#53 -f 1.1.1.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53
            4. php.exe start.php -l 127.0.0.1#53 -i 223.5.5.5#53 -o 8.8.8.8#53 -f 1.1.1.1#53 -t 300 -p udp --ft 150 --fr 10
            