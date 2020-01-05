<?php
/**
 * CustomerFilter
 */
namespace App;

use Exigen\DbSupport\SqlQueryBuilder;

class CustomerQuery  extends SqlQueryBuilder
{
    public function __construct()
    {
        $table_list = array();
        $create = function () {
            return new Customer();
        };
        parent::__construct("c", "customers", $create, $table_list);
    }

}
