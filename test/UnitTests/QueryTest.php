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
        DbSetup::configure(DbRecord::ACCESSORS_GETTER_SETTER);
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

        $qty = 3;
        $val = 12;
        $total1 = $qty * $val;
        $vat1 = Util::calculateVat($total1);

        // Add Line item and check count
        $lineItem1 = new InvoiceLineItem();
        $lineItem1->setQuantity($qty);
        $lineItem1->setDescription("Pens");
        $lineItem1->setNetAmount($total1);
        $lineItem1->setVat($vat1);
        $invoice->appendLineItem($lineItem1);

        // Check number of line items
        $this->assertEquals(1, $invoice->getLineItemCount());

        // Add Line item and check count
        $qty = 2;
        $val = 44.23;
        $total2 = $qty * $val;
        $vat2 = Util::calculateVat($total2);

        $lineItem1 = new InvoiceLineItem();
        $lineItem1->setQuantity($qty);
        $lineItem1->setDescription("Rulers");
        $lineItem1->setNetAmount($total2);
        $lineItem1->setVat($vat2);
        $invoice->appendLineItem($lineItem1);

        // Check number of line items
        $this->assertEquals(2, $invoice->getLineItemCount());

        // Check the totals
        $total = $total1 + $total2;
        $this->assertEquals($total, $invoice->getNetTotal());

        // Total with vat
        $total += $vat1 + $vat2;
        $this->assertEquals($total, $invoice->getTotal());

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
