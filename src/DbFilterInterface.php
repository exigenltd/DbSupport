<?php

namespace exigen\DbSupport;
/**
 * Database Filter
 *
 */
interface DbFilterInterface
{
    /**
     * SQL
     *
     * @return string
     */
    public function getSql();


    public function getBindList();

    /**
     * @return DbRecord
     */
    public function getListObject();


}

