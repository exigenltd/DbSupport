<?php

use App\DbSetup;
use Exigen\DbSupport\DbRecord;

include_once __DIR__ . "/../vendor/autoload.php";

/**
 * DbRecord Test
 */
class RecordAccessTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testInvoiceAccess()
    {
        // Set up the database
        DbSetup::configure(DbRecord::ACCESSORS_GETTER_SETTER);

        // Get an invoice
        $invoice = new \App\Invoice();

        $invoice_number = "a1";
        $final_number = "000000A1";
        // Check invoice number is updated on save
        $invoice->setInvoiceNumber($invoice_number);
        $this->assertEquals($final_number, $invoice->getInvoiceNumber());
    }

    public function testNoAccess()
    {
        // Set up the database
        DbSetup::configure(DbRecord::ACCESSORS_GETTER_SETTER);

        // Get an invoice
        $invoice = new \App\Invoice();

        $status = "St" . time();

        // Ensure there is no access to the "invoice_status" field via get or set methods
        $access = true;
        try {
            $invoice->setInvoiceStatus($status);
        } catch (Exception $e) {
            $access = false;
        }
        $this->assertFalse($access, "Invoice status should not be available");

        $access = true;
        try {
            $invoice->getInvoiceStatus();
        } catch (Exception $e) {
            $access = false;
        }
        $this->assertFalse($access, "Invoice status should not be available");

        $invoice->setStatus($status);
        $id = $invoice->save();
        $new_invoice = \App\Invoice::lookUpInDatabase($id);
        $this->assertEquals($status, $invoice->getStatus());
    }
}
