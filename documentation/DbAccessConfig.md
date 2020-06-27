# DbAccess Configuration Array

[Back to Home Page](Start.md)   

Option   | Mandatory | Purpose |
---      |---  |---
server   | YES | IP address or name of database server.
schema   | YES | The name of the database schema being used
user     | YES | MySQL user login
password | No  | MySQL user password. Can be left out if no password is set for the user.
port     | No  | Port for connection to MySql database.  The standard port, 3306, is used if no port specified.
options  | No  | Array value.  PDO options array that will be used when a PDO connection is created, e.g. "new PDO(...)" 



## PDO Options Example
When connecting to an encrypted database on Azure a certification file has to be passed to 
the PDO connector.  This can be included in options array, e.g. 
````
    "options"  => array(
        PDO::MYSQL_ATTR_SSL_CA => "/home/site/ssl/BaltimoreCyberTrustRoot.crt.pem"
    )
````
