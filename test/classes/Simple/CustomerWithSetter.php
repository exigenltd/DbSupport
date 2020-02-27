<?php
/**
 * CustomerWithGetter
 */
namespace Simple;

class CustomerWithSetter extends Customer
{
    public function setLastName($name)
    {
        return $this->fieldValue("last_name", ucfirst($name));
    }

}
