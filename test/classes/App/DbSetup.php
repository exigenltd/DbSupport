<?php
namespace App;

use Exigen\DbSupport\DbAccess;
use Exigen\DbSupport\DbRecord;

/**
 * DbSetup
 */
class DbSetup
{
    public static function configure($accessMethods)
    {
        static $st_configured = false;
        static $st_start = true;

        if ($st_configured) {
            if (DbRecord::methodType() == $accessMethods) {
                return;
            }
        }
        $st_configured = true;
        $database = array(
            "server"   => "localhost",
            "schema"   => "exigen_dbsupport",
            "user"     => "usrDbSupport",
            "password" => "dbsupport",
            "methods"  => $accessMethods,
        );
        DbAccess::config($database);

        if ($st_start) {
            $customer = new Customer();
            $customer->dbUpdate();

            $invoice = new Invoice();
            $invoice->dbUpdate();

            $invoice = new InvoiceLineItem();
            $invoice->dbUpdate();
        }
        $st_start = false;
    }
}
