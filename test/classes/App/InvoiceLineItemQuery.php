<?php
/**
 * InvoiceLineItemQuery
 */
namespace App;

use Exigen\DbSupport\SqlQueryBuilder;

class InvoiceLineItemQuery extends SqlQueryBuilder
{
    public function __construct()
    {
        $table_list = array();
        $create = function () {
            return new InvoiceLineItem();
        };
        parent::__construct("l", "invoice_line_items", $create, $table_list);
    }

    public function addInvoiceIdFilter($invoice_id)
    {
        $bind_array = array("invoice_id" => $invoice_id);
        $this->addWhereClause("AND invoice_id=:invoice_id", $bind_array);
    }
}
