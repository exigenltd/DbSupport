<?php

namespace exigen\DbSupport;
/**
 * Database Filter
 *
 */
interface DbQueryInterface
{
    /**
     * @return string
     */
    public function getSql();

    /**
     * @return array
     */
    public function getBindList();

    /**
     * @return DbRecord
     */
    public function getListObject();
}

