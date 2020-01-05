<?php
/**
 * Util
 */
namespace App;

class Util
{
    /**
     * Calculate VAT for a given value.
     * @param $amount
     * @return float
     */
    public static function calculateVat($amount)
    {
        return round($amount * 0.2, 2);
    }

}
