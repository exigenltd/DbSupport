<?php

use App\CustomerQuery;
use App\DbSetup;
use Exigen\DbSupport\DbRecord;
use Simple\Customer;

include_once __DIR__ . "/../vendor/autoload.php";

/**
 * DbRecord Test
 */
class SupportDocumentTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testCodeUsedInDocumentation()
    {
        // Set up the database
        DbSetup::configure(DbRecord::ACCESSORS_GETTER_SETTER);

        // Ensure the customer table is created
        $customer = new Customer();
        try {
            $customer->dbUpdate();
        } catch (Exception $e) {
            $this->assertTrue(false, "Unable to update the customer database");
        }
        // Ensure there is a record in db to lookup.
        $customer->setFirstName("Fred");
        $id = $customer->save();

        // Save and retrieve item
        $customer = Customer::lookUpInDatabase($id);
        $this->assertNotNull($customer);

        // Getter Setters
        $customer->setFirstName("Fred");
        $first_name = $customer->getFirstName();
        $customer->setLastName("Smith");
        $last_name = $customer->getLastName();

        $this->assertEquals($first_name, "Fred");
        $this->assertEquals($last_name, "Smith");

        // Delete
        $customer->delete();
        $new = Customer::lookUpInDatabase($id);
        $this->assertNull($new);

        // Setter override
        $customer = new \Simple\CustomerWithSetter();
        $customer->setLastName("fred");

        $this->assertEquals("Fred", $customer->getLastName());
    }

    public function testQueryUsedInDocumentation() {

        // Set up the database
        DbSetup::configure(DbRecord::ACCESSORS_GETTER_SETTER);

        $customer = new \App\Customer();
        $customer->setLastName("Smith");
        $customer->save();

        // Query
        $query = new CustomerQuery();
        $query->addLastNameFilter("smith");
        $customer_list = $query->getList();
    }

}
