<?php

namespace App;

use Exigen\DbSupport\DbRecord;

/**
 * Invoice Line Item
 *
 * @method setInvoiceId($val);
 * @method setQuantity($val);
 * @method setDescription($val);
 * @method setNetAmount($val);
 * @method getVat()
 * @method setVat($val)
 * @method getNetAmount()
 * @method getGuid()
 */
class InvoiceLineItem extends DbRecord
{
    public function __construct()
    {
        $fieldList = array(
            'invoice_id'  => array("type" => DbRecord::DB_TYPE_NUMERIC, "db_type" => 'int not null'),
            'quantity'    => array("type" => DbRecord::DB_TYPE_NUMERIC, "db_type" => 'int not null'),
            'description' => array("type" => DbRecord::DB_TYPE_STRING, "db_type" => 'varchar(100)'),
            'net_amount'  => array("type" => DbRecord::DB_TYPE_NUMERIC, "db_type" => 'decimal(13,4)'),
            'vat'         => array("type" => DbRecord::DB_TYPE_NUMERIC, "db_type" => 'decimal(13,4)'),
            // Creation time and guid are read only properties....
            'guid'        => array(
                "type"    => DbRecord::DB_TYPE_STRING,
                "db_type" => 'varchar(32)',
                "access"  => DbRecord::ACCESS_READ_ONLY
            ),
            'created_at'  => array(
                'type'    => DbRecord::DB_TYPE_DATE_TIME,
                'db_type' => 'datetime',
                "access"  => DbRecord::ACCESS_READ_ONLY
            ),
        );
        parent::__construct("invoice_line_items", "id", $fieldList);

        // Ensure item has creation time (i.e. set on creation)
        if ($this->fieldValue("created_at") == 0) {
            $this->fieldValue("created_at", time());
        }
        // Ensure item has a guid (i.e. set on creation)
        if ($this->fieldValue("guid") == "") {
            $this->fieldValue("guid", uniqid("li-"));
        }
    }
}
