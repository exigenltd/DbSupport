<?php
/**
 * SqlQueryBuilder
 */
namespace Exigen\DbSupport;

use Closure;
use Exception;

class SqlQueryBuilder implements DbQueryInterface
{
    private $distinct = false;
    private $where = array();
    private $bind_array = array();
    private $order_by = array();
    private $table_list = array();
    private $used_tables = array();

    private $limit = 200;

    /* @var Closure $create_closure */
    private $create_closure;

    private $alias;
    private $table;

    protected function __construct($alias, $table, $object_create_closure, array $table_list)
    {
        $this->alias = $alias;
        $this->table = $table;
        $this->create_closure = $object_create_closure;
        $this->table_list = $table_list;
    }

    protected function setDistinct($flag)
    {
        $this->distinct = $flag;
    }

    public function getSQL()
    {
        $field_list = $this->alias . ".*";
        return $this->getSQLWithField($field_list);
    }

    private function getSQLWithField($field_list)
    {
        $distinct = ($this->distinct ? "DISTINCT " : "");
        $sql = "SELECT " . $distinct . $field_list . " FROM " . $this->table . " " . $this->alias;

        // Add any tables that are needed
        foreach ($this->table_list as $alias => $join) {
            if (isset($this->used_tables[$alias])) {
                $sql .= " " . $join;
            }
        }

        // Where conditions
        if (count($this->where) > 0) {
            $where = implode(" ", $this->where);
            $pos = strpos($where, " ");
            if ($pos !== false) {
                $sql .= " WHERE " . substr($where, $pos);
            }
        }

        // Order by conditions
        if (count($this->order_by) > 0) {
            $sql .= " ORDER BY " . implode(",", $this->order_by);
        }

        if ($this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
        }
        return $sql;
    }

    protected function addWhereClause($where_str, array $bind_parameters)
    {
        $this->where[] = trim($where_str);
        $this->bind_array = array_merge($this->bind_array, $bind_parameters);
    }

    protected function addOrderBy($order_by)
    {
        $this->order_by[] = $order_by;
    }

    protected function useTable($alias)
    {
        $this->used_tables[$alias] = true;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    protected function getScalar($field, $default = 0)
    {
        // Get SQL using given field as SQL field list
        $sql = $this->getSQLWithField($field);

        $list = DbAccess::getArrayFromSQL($sql, $this->bind_array);
        // List is
        if (count($list) > 0) {
            foreach ($list[0] as $result) {
                return $result;
            }
        }
        return $default;
    }

    /**
     * Get list of objects
     *
     * @return array
     * @throws Exception
     */
    public function getList()
    {
        return DbAccess::getListFromFilter($this);
    }

    public function getFirst()
    {
        $limit = $this->limit;
        $this->limit = 1;
        $list = $this->getList();

        $this->limit = $limit;

        return (count($list) > 0 ? $list[0] : null);
    }

    public function getListObject()
    {
        $func = $this->create_closure;
        return $func();
    }

    public function getBindList()
    {
        return $this->bind_array;
    }

}
