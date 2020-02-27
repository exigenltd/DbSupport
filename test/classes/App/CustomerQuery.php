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

    public function addLastNameFilter($name) {
        $bind_array = array("last_name" => $name);
        $this->addWhereClause("AND last_name=:last_name", $bind_array);
    }

}
