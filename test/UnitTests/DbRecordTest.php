<?php

use App\Customer;
use App\DbSetup;
use Exigen\DbSupport\DbRecord;

include_once __DIR__ . "/../vendor/autoload.php";

/**
 * DbRecord Test
 */
class DbRecordTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testCustomerBasic()
    {
        // Set up the database
        DbSetup::configure(DbRecord::METHODS_NONE);
        // Ensure the customer table is created
        $customer = new Customer();
        $customer->dbUpdate();

        // Save and retrieve item
        $name = "fred";
        $customer->firstName($name);
        try {
            $customer->save();
        } catch (Exception $e) {
            $this->assertTrue(false, "Unable to save customer");
        }
        $id = $customer->id();

        $new_customer = Customer::createFromDatabase($id);
        $this->assertEquals($id, $new_customer->id(), "Customer not saved");
        $this->assertEquals($name, $new_customer->firstName(), "First name not saved");

        // Retrieve using look up
        $new_customer = Customer::lookUpInDatabase($id);
        $this->assertNotNull($new_customer);
        if ($new_customer != null) {
            $this->assertEquals($id, $new_customer->id(), "Customer not saved");
            $this->assertEquals($name, $new_customer->firstName(), "First name not saved");
        }

        // Look up a non existent customer and checking nothing is found
        $new_customer = Customer::lookUpInDatabase($id + 100);
        $this->assertNull($new_customer);

        // Check methods don't exist

        // Check set method throws error
        $has_error = false;
        try {
            $customer->lastName();
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "No Error was thrown");

        $has_error = false;
        try {
            $customer->getLastName();
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "No Error was thrown");

        $has_error = false;
        try {
            $customer->setLastName("smith");
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "No Error was thrown");
    }

    public function testCustomerGetterSetter()
    {
        // Set up the database
        DbSetup::configure(DbRecord::METHODS_GETTER_SETTER);
        // Ensure the customer table is created
        $customer = new Customer();
        $customer->dbUpdate();

        $name = "Smith" . time();
        $customer->setLastName($name);
        try {
            $customer->save();
        } catch (Exception $e) {
            $this->assertTrue(false, "Unable to save customer");
        }
        $id = $customer->getId();

        $newCustomer = Customer::createFromDatabase($id);
        $this->assertEquals($name, $newCustomer->getLastName());

        // Check set method throws error if no parameter passed
        $has_error = false;
        try {
            $customer->setLastName();
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "No Error was thrown");

        // First name should be private access ensure cannot access
        $has_error = false;
        try {
            $customer->setFirstName("fred");
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "No Error was thrown");
        $has_error = false;
        try {
            $customer->getFirstName();
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "No Error was thrown");

        // Creation time is ready only
        $time = $customer->getCreatedAt();
        $this->assertGreaterThan(0, $time);
        $has_error = false;
        try {
            $customer->setCreatedAt(22);
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "Creation time was set, should be readonly");
        // Check hasn't been changed by set
        $this->assertEquals($time, $customer->getCreatedAt(), "Creation time has been updated");

    }

    public function testCustomerUpdate()
    {
        // Set up the database
        DbSetup::configure(DbRecord::METHODS_SINGLE);
        // Ensure the customer table is created
        $customer = new Customer();

        $name = "Smith" . time();
        $customer->lastName($name);
        try {
            $customer->save();
        } catch (Exception $e) {
            $this->assertTrue(false, "Unable to save customer");
        }
        $id = $customer->id();

        $newCustomer = Customer::createFromDatabase($id);
        $this->assertEquals($name, $newCustomer->lastName());

        // Check set method throws error
        $has_error = false;
        try {
            $customer->getLastName();
        } catch (Exception $e) {
            $has_error = true;
        }
        $this->assertTrue($has_error, "No Error was thrown");
    }
}
