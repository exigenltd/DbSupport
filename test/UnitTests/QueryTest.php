<?php

use App\Customer;
use App\CustomerQuery;
use App\DbSetup;
use App\Invoice;
use App\InvoiceLineItem;
use App\Util;
use Exigen\DbSupport\DbRecord;

include_once __DIR__ . "/../vendor/autoload.php";

/**
 * Initial Test
 */
class QueryTest extends PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        // Set up the database
        DbSetup::configure(DbRecord::METHODS_GETTER_SETTER);
        try {
            // Ensure the customer table is created
            $customer = new Customer();
            $customer->dbUpdate();

            $filter = new CustomerQuery();
            $filter->setLimit(2);

            $list = $filter->getList();
            $this->assertGreaterThan(0, count($list), "No items returned from filter");
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            die;
        }
    }

    public function testInvoiceLineItems()
    {
        // Create an invoice and add line items
        $invoice = new Invoice();

        // Check number of line items
        $this->assertEquals(0, $invoice->getLineItemCount());

        // Add Line item and check count
        $lineItem1 = new InvoiceLineItem();
        $lineItem1->setQuantity(3);
        $lineItem1->setDescription("Pens");
        $lineItem1->setNetAmount(12);
        $lineItem1->setVat(Util::calculateVat($lineItem1->getNetAmount()));
        $invoice->appendLineItem($lineItem1);

        // Check number of line items
        $this->assertEquals(1, $invoice->getLineItemCount());

        // Add Line item and check count
        $lineItem1 = new InvoiceLineItem();
        $lineItem1->setQuantity(2);
        $lineItem1->setDescription("Rulers");
        $lineItem1->setNetAmount(44.23);
        $lineItem1->setVat(Util::calculateVat($lineItem1->getNetAmount()));
        $invoice->appendLineItem($lineItem1);

        // Check number of line items
        $this->assertEquals(2, $invoice->getLineItemCount());

        try {
            $id = $invoice->save();
        } catch (Exception $e) {
            echo "Save failed: " . $e->getMessage();
            return;
        }

        $retrieved_invoice = Invoice::lookUpInDatabase($id);
        $this->assertNotNull($retrieved_invoice, "Invoice not found");

        $this->assertEquals(2, $retrieved_invoice->getLineItemCount());

    }
}
