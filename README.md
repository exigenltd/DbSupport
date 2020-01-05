# DbSupport

The purpose of this package is to provide a quick, easy mechanism to access a mySQL database which is robust enough 
to use in larger projects.

There are two primary classes, a DB record class which is used to represent a database table record.  
And a SQL Query builder class which is used to help retrieve lists of DB record items.

DB Record
A class to represent a Db Table record. 
The table is defined within the constructor class.


     class ExampleClass extends exigen\DbSupport\DbRecord
     {
         public function __construct()
         {
              $fieldList = array(
                   'customer_name' => array('type'=>DbAccess::DB_TYPE_NUMERIC, 'db_type'=>'int'),
                   'invoice_id' => array('type'=>DbAccess::DB_TYPE_STRING, 'db_type'=>'VARCHAR(128)'),
                   ...
               );
         
         parent::__construct("the_table_name", 'the_table_id', $fieldList);
         }
    }


The following Static method are then available:
 * ExampleClass::lookUpInDatabase($id);  // Gives instance of Example class matching Id value or null if not found in DB.
 * ExampleClass::createFromDatabase($id);  // Gives instance of Example class matching Id value or new instance if not found in DB.

The following methods are available on any instance of a Db Record:
 * $instance->save();  // Save the record to the database.
 * $instance->delete();    // Delete the record from the database.
 * $instance->dbUpdate(); // Update/create table in database.
 * $instance->id()

And for the example above, the following methods would automatically be available depending access option selected:
 * $instance->getId()
 * $instance->getCustomerName();
 * $instance->setCustomerName($name);
 * $instance->getInvoiceId();
 * $instance->setInvoiceId($id);
OR
 * $instance->customerName();
 * $instance->customerName($name);
 * $instance->invoiceId();
 * $instance->invoiceId($id);

Usage
Two steps:
    1. Configure Database connection details, required once.
    2. Create class derived from DbRecord with table definition within the constructor.

