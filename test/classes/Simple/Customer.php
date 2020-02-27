<?php
namespace Simple;

use Exigen\DbSupport\DbRecord;

/**
 * Customer
 *
 * @method getId()
 * @method lastName($name = null)
 * @method getLastName()
 * @method setLastName($name)
 * @method getFirstName()
 * @method setFirstName($name)
 * @method getCreatedAt()
 * @method setCreatedAt($name)
 *
 *
 */
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
