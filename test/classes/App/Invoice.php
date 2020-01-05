<?php
namespace App;

use Exigen\DbSupport\DbRecord;

/**
 * Invoice
 *
 */
class Invoice extends DbRecord
{
    /* @var InvoiceLineItem[] $line_item_list */
    private $line_item_list = null;

    public function __construct()
    {
        $fieldList = array(
            'company'    => array("type" => DbRecord::DB_TYPE_STRING, "db_type" => 'varchar(100)'),
            'created_at' => array(
                'type'    => DbRecord::DB_TYPE_DATE_TIME,
                'db_type' => 'datetime',
                "access"  => DbRecord::ACCESS_READ_ONLY
            ),
        );
        parent::__construct("invoices", "id", $fieldList);

        if ($this->fieldValue("created_at") == 0) {
            $this->fieldValue("created_at", time());
        }
    }

    public function getTotal()
    {
        return $this->getNetTotal() + $this->getVat();
    }

    public function getNetTotal()
    {
        $this->ensureLineItemListExists();
        return array_reduce($this->line_item_list, function ($carry, $item) {
            /* @var \App\InvoiceLineItem $item */
            $carry += $item->getNetAmount();
            return $carry;
        });
    }

    public function getVat()
    {
        $this->ensureLineItemListExists();
        return array_reduce($this->line_item_list, function ($carry, $item) {
            /* @var \App\InvoiceLineItem $item */
            return ($carry + $item->getVat());
        });
    }

    public function appendLineItem(InvoiceLineItem $lineItem)
    {
        $this->ensureLineItemListExists();
        $this->line_item_list[$lineItem->getGuid()] = $lineItem;
    }

    public function getLineItemCount()
    {
        $this->ensureLineItemListExists();
        return count($this->line_item_list);
    }

    public function save()
    {
        $id = parent::save();

        if ($this->line_item_list != null And $id > 0) {
            foreach ($this->line_item_list as $lineItem) {
                $lineItem->setInvoiceId($id);
                $lineItem->save();
            }
        }
        return $id;
    }

    private function ensureLineItemListExists()
    {
        if ($this->line_item_list == null) {
            $filter = new InvoiceLineItemQuery();
            $filter->addInvoiceIdFilter($this->id());
            $this->line_item_list = $filter->getList();
        }
    }
}
