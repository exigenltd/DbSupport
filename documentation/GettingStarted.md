# Getting Started

[Back to Home Page](Start.md)   

1. Get from composer.

2. Configure Database Connection 

3. Create a DB Record.            

## Get from composer
composer require exigen/dbsupport

## Configure Database Connection
Create connection details in an array:
````
$config_array = array(
                    "server"   => "the_db_host",
                    "user"     => "the_db_user",
                    "password" => "the_db_pass",
                    "port"     => "the_db_port",
                    "schema"   => "the_db_name",
                ),
````
See [DB Access Configuration Page](DbAccessConfig.md) for full list of options.

Pass the configuration to the database. 
````
DbAccess::config($db_array);
````
Database connection uses lazy instantiate so connection will only be attempted the first time the app uses 
the database.

## Create a DB Record.

A database record is created by inheriting from the DbRecord class and defining the database table and
fields within the constructor. 

````
class Customer extends DbRecord
{
    public function __construct()
    {
        $fieldList = array(
            'first_name'      => array(
                "type"    => DbRecord::DB_TYPE_STRING,
                "db_type" => 'varchar(100)'
            ),
            'last_name'       => array(
                "type"    => DbRecord::DB_TYPE_STRING,
                "db_type" => 'varchar(100)'
            ),
            'email_address'   => array(
                "type"    => DbRecord::DB_TYPE_STRING,
                "db_type" => 'varchar(100)'
            ),
            'customer_status' => array(
                "type"    => DbRecord::DB_TYPE_NUMERIC,
                "db_type" => 'int'
            ),
            'created_at'      => array(
                'type'    => DbRecord::DB_TYPE_DATE_TIME,
                'db_type' => 'datetime'
            ),
        );
        parent::__construct("customers", "id", $fieldList);
    }
}
`````

