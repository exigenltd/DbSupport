<?php
namespace App;

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
                "db_type" => 'varchar(100)',
                "access"  => DbRecord::ACCESS_NONE
            ),
            'last_name'       => array(
                "type"    => DbRecord::DB_TYPE_STRING,
                "db_type" => 'varchar(100)',
                "access"  => DbRecord::ACCESS_FULL
            ),
            'email'           => array(
                "type"    => DbRecord::DB_TYPE_STRING,
                "db_type" => 'varchar(100)',
                "access"  => DbRecord::ACCESS_FULL
            ),
            'reference'       => array(
                "type"    => DbRecord::DB_TYPE_STRING,
                "db_type" => 'varchar(100)',
                "access"  => DbRecord::ACCESS_FULL
            ),
            'email_sent_flag' => array(
                "type"    => DbRecord::DB_TYPE_BOOLEAN,
                "db_type" => 'int',
                "access"  => DbRecord::ACCESS_FULL
            ),
            'customer_status' => array(
                "type"    => DbRecord::DB_TYPE_NUMERIC,
                "db_type" => 'int',
                "access"  => DbRecord::ACCESS_FULL
            ),
            'created_at'      => array(
                'type'    => DbRecord::DB_TYPE_DATE_TIME,
                'db_type' => 'datetime',
                "access"  => DbRecord::ACCESS_READ_ONLY
            ),
            'guid'      => array(
                'type'    => DbRecord::DB_TYPE_STRING,
                'db_type' => 'varchar(32)',
                "access"  => DbRecord::ACCESS_READ_ONLY
            ),
        );
        parent::__construct("customers", "id", $fieldList);

        if ($this->fieldValue("created_at") == 0) {
            $this->fieldValue("created_at", time());
        }
        if ($this->fieldValue("guid") == "") {
            $this->fieldValue("guid", uniqid("cust-"));
        }
    }

    public function firstName($name = null)
    {
        return $this->fieldValue("first_name", $name, func_num_args());
    }

}
