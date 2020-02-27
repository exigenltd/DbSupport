<?php
/**
 * Db Record
 */
namespace Exigen\DbSupport;

use Exception;
use PDO;
use PDOStatement;

/**
 * Db Record Class
 */
abstract class DbRecord
{
    const DB_TYPE_NUMERIC = "number";
    const DB_TYPE_STRING = "string";
    const DB_TYPE_DATE_TIME = "datetime";
    const DB_TYPE_BOOLEAN = "boolean";

    const ACCESS_NONE = "none";
    const ACCESS_READ_ONLY = "read";
    const ACCESS_FULL = "full";

    const ACCESSORS_NONE = "none";
    const ACCESSORS_GETTER_SETTER = "getSet";
    const ACCESSORS_SINGLE = "single";

    /**  @var string */
    private static $accessMethod = self::ACCESSORS_GETTER_SETTER;

    /**  @var string */
    private $tableName;
    /**  @var string */
    private $primaryKey;

    private $fieldList = array();
    private $columnDef = array();
    private $valArray = array();
    private $accessArray = array();

    private $trim_flag = true;
    // This item is a new row, unless data retrieved in fill from database method below.
    private $new_db_row = true;

    public static function methodType($type = null)
    {
        if (func_num_args() > 0) {
            self::$accessMethod = $type;
        }
        return self::$accessMethod;
    }

    /**
     * Creation of DB mapping object.
     * The first item in the field list has to be the primary key field.
     * If no creator value is passed an empty object is created.
     * If an integer is passed, tries to look up corresponding record in database.
     * If an array is passed, then this is used as value array for this object,
     * i.e. sets a pre populated record.
     *
     * @param string $tableName
     * @param string $primaryKey
     * @param array  $fieldList (type, db_type, references)
     *
     * @throws Exception
     */
    protected function __construct($tableName, $primaryKey, $fieldList)
    {
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;

        // NOTE - there are three fields in a field, only the first two are used at present
        // 1. type       => model-level type (DB_TYPE_NUMERIC or similar)
        // 2. db_type    => concrete type used in the database
        // 3. references => table-name for foreign-keys (only used as documentation at present)
        foreach ($fieldList as $field => $type) {
            if (strtolower($field) == strtolower($primaryKey)) {
                // We've previously had some really subtle bugs as a result of people inadvertently adding
                // the primary key field to the field list. This should never be allowed.
                throw (new Exception("The primary field cannot be included in table field list"));
            }
            if (is_array($type)) {
                if (isset($type["type"])) {
                    $this->fieldList[$field] = $type["type"];
                }
                if (isset($type["db_type"])) {
                    $this->columnDef[$field] = $type["db_type"];
                }
                if (isset($type["access"])) {
                    $this->accessArray[$field] = $type["access"];
                }
            } else {
                $this->fieldList[$field] = $type;
            }
        }
    }

    /**
     * Factory method to create a database mapping object
     * prefilled with data from database.
     * Always creates object, even if not found in database.
     *
     * @param int $id
     *
     * @return static
     */
    public static function createFromDatabase($id)
    {
        $class = get_called_class();

        /* @var $object \exigen\DbSupport\DbRecord */
        $object = new $class;

        $object->fillFromDatabase($id);

        // Create new object, regardless of if it has been saved from database.
        return $object;
    }

    /**
     * @param $id
     *
     * @return static
     */
    public static function lookUpInDatabase($id)
    {
        if (!empty($id)) {
            $class = get_called_class();

            /* @var $object \exigen\DbSupport\DbRecord */
            $object = new $class;

            // Only return object if found in database, otherwise null.
            if ($object->fillFromDatabase($id)) {
                return $object;
            }
        }
        return null;
    }

    public function id()
    {
        $id = 0;
        if (isset($this->valArray[$this->primaryKey])) {
            $id = $this->valArray[$this->primaryKey];
        }
        return $id;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function save()
    {
        $field_detail_array = $this->fieldUpdateList();

        if (count($field_detail_array) == 0) {
            return 0;
        }

        // Insert or update record
        if ($this->new_db_row) {
            $field_name_list = "";
            $field_param_list = "";
            $separator = "";

            // Create field list and value list for insert
            foreach ($field_detail_array as $field_info) {
                $field_name_list .= $separator . $field_info[DbAccess::PRIVATE_FIELD_NAME];
                $field_param_list .= $separator . ":" . $field_info[DbAccess::PRIVATE_FIELD_NAME];
                $separator = ",";
            }
            $sql = "INSERT INTO " . $this->tableName . " (" . $field_name_list . ") ";
            $sql .= "VALUES (" . $field_param_list . ")";

        } else {
            $update_list = "";
            $separator = "";

            // Create field list and value list for insert
            foreach ($field_detail_array as $field_info) {
                // we don't want to update primary key
                if ($field_info[DbAccess::PRIVATE_FIELD_NAME] == $this->primaryKey) {
                    continue;
                }
                $update_list .= $separator . $field_info[DbAccess::PRIVATE_FIELD_NAME];
                $update_list .= "=:" . $field_info[DbAccess::PRIVATE_FIELD_NAME];
                $separator = ",";
            }

            // Add the id to bind array
            $field_detail_array[] = array(
                DbAccess::PRIVATE_FIELD_NAME  => $this->primaryKey,
                DbAccess::PRIVATE_FIELD_VALUE => $this->valArray[$this->primaryKey],
                DbAccess::PRIVATE_FIELD_TYPE  => PDO::PARAM_INT
            );

            $sql = "UPDATE " . $this->tableName . " SET " . $update_list . " WHERE "
                . $this->primaryKey . "=:" . $this->primaryKey;
        }

        $update_id = DbAccess::runUpdateQuery($sql, $field_detail_array);
        if ($update_id == -1) {
            return 0;
        }
        if ($this->new_db_row) {
            $this->valArray[$this->primaryKey] = $update_id;
        }
        return $this->valArray[$this->primaryKey];
    }

    /**
     * Fill
     *
     * @param string $id
     *
     * @return boolean
     */
    private function fillFromDatabase($id)
    {
        $filled = false;

        if (!empty($id)) {
            // Field list
            $fieldList = $this->getFieldListAsString();

            $sql = "SELECT $fieldList FROM " . $this->tableName . " WHERE " . $this->primaryKey . "=:id";
            /* @var $statement PDOStatement */
            $bind = array("id" => $id);
            try {
                $result = DbAccess::getArrayFromSQL($sql, $bind);
            } catch (Exception $e) {
                return false;
            }
            if (count($result) != 0) {
                $this->valArray = &$result[0];
                $this->new_db_row = false;
                $filled = true;
            }
        }
        if (!$filled) {
            $this->valArray = array(); // set to empty array
        }
        return $filled;
    }

    /**
     * Get field list string
     *
     * @param string $prefix
     *
     * @return string
     */
    private function getFieldListAsString($prefix = "")
    {
        if ($prefix != "") {
            $prefix .= ".";
        }

        $fieldList = $prefix . $this->primaryKey;
        foreach ($this->fieldList as $key => $val) {
            $fieldList .= ", " . $prefix . $key;
        }
        return $fieldList;
    }

    protected function getFieldList()
    {
        return array_keys($this->fieldList);
    }

    protected function fieldExists($field_key)
    {
        return isset ($this->fieldList[$field_key]);
    }

    /**
     * Get/Set value of given field.
     *
     * @param string $field_key The name of the field
     * @param null   $new_val
     *
     * @param bool   $update_flag
     * @return bool|int|string [type]            Returns the value
     */
    protected function fieldValue($field_key, $new_val = null, $update_flag = true)
    {
        // If second parameter is passed, then normally this is update value.
        // BUT, a third value can be used to indicate if value is bing updated.
        $update_flag = (func_num_args() > 2) ? $update_flag : (func_num_args() > 1);

        $type = (isset ($this->fieldList[$field_key])) ? $this->fieldList[$field_key] : self::DB_TYPE_STRING;
        // Set the value
        if ($update_flag) {
            switch ($type) {
                case self::DB_TYPE_DATE_TIME:
                    $new_val = intval($new_val);
                    $this->valArray[$field_key] = gmdate("Y-m-d H:i:s", $new_val); // NOTE - always UTC
                    break;
                case self::DB_TYPE_BOOLEAN:
                    $this->valArray[$field_key] = ($new_val ? 1 : 0);
                    break;
                default:
                    $this->valArray[$field_key] = $new_val;
                    break;
            }
        }

        // Always return the field value
        // check field type
        $val = (isset($this->valArray[$field_key]) ? $this->valArray[$field_key] : null);

        switch ($type) {
            case self::DB_TYPE_DATE_TIME:
                // NOTE - always UTC, assumes no existing timezone
                $result = (is_null($val)) ? 0 : strtotime($val . '+00:00');
                break;
            case self::DB_TYPE_BOOLEAN:
                $result = (is_null($val)) ? false : ($val != 0);
                break;
            case self::DB_TYPE_NUMERIC:
                $result = (is_null($val)) ? 0 : $val;
                break;
            default:
                $result = (is_null($val)) ? "" : ($this->trim_flag ? trim($val) : $val);
                break;
        }

        return $result;
    }

    private function fieldUpdateList()
    {
        $usedFields = array();

        // check through the table fields
        foreach ($this->fieldList as $field => $type) {
            // If no value has been set for this field, then skip
            if (!isset($this->valArray[$field])) {
                continue;
            }

            // Ignore primary key
            if ($field == $this->primaryKey) {
                continue;
            }

            $field_value = $this->valArray[$field];
            $pdo_type = null;
            // Check data type for fields to update
            switch ($type) {
                case self::DB_TYPE_STRING:
                    $pdo_type = PDO::PARAM_STR;
                    break;
                case self::DB_TYPE_NUMERIC:
                    if (!is_numeric($field_value)) {
                        $field_value = 0;
                    }
                    $pdo_type = PDO::PARAM_INT;
                    break;
                case self::DB_TYPE_BOOLEAN:
                    $field_value = ($this->valArray[$field]) ? 1 : 0;
                    $pdo_type = PDO::PARAM_INT;
                    break;
                case self::DB_TYPE_DATE_TIME:
                    if ($field_value == "") {
                        $field_value = null;
                        $pdo_type = PDO::PARAM_NULL;
                    } else {
                        $pdo_type = PDO::PARAM_STR;
                    }
                    break;
                default:
                    continue;
                    break;
            }

            $usedFields[] = array(
                DbAccess::PRIVATE_FIELD_NAME  => $field,
                DbAccess::PRIVATE_FIELD_VALUE => $field_value,
                DbAccess::PRIVATE_FIELD_TYPE  => $pdo_type
            );
        }

        return $usedFields;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function dbUpdate()
    {
        if (sizeof($this->columnDef) == 0) {
            return false;
        }

        $table_exists = $this->checkIfTableExists($this->tableName);

        // Create table if it does not exist
        if (!$table_exists) {
            return $this->createDbTable();
        }
        $sql = "SHOW columns FROM " . $this->tableName;

        // Get existing columns
        $existing_tbl_def = array();
        foreach (DbAccess::getArrayFromSQL($sql) as $row) {
            $existing_tbl_def[] = $row;
        }

        // Now check if column we require exist.
        foreach ($this->columnDef as $field => $type) {
            // Check if exist
            $exists = false;
            foreach ($existing_tbl_def as $existing_col_def) {
                if ($existing_col_def['Field'] == $field) {
                    $exists = true;
                    break;
                }
            }
            // already exists?
            if ($exists) {
                continue;
            }

            $sql = "ALTER TABLE " . $this->tableName . " ADD $field $type";
            DbAccess::runUpdateQuery($sql);
        }
        return true;
    }

    /**
     * Creates a Statement to create a new table and its fields
     * runs the statement
     *
     * @return bool PDOStatement
     */
    private function createDbTable()
    {
        $sql = "CREATE TABLE " . $this->tableName . " ( ";
        // primary key
        if (!array_key_exists($this->primaryKey, $this->columnDef)) {
            $sql .= $this->primaryKey . " INT NOT NULL AUTO_INCREMENT,";

            foreach ($this->columnDef as $field => $type) {
                $sql .= $field . " " . $type . ", ";
            }
            $sql .= "PRIMARY KEY (`" . $this->primaryKey . "`) ";
            $sql .= ")";

            try {
                DbAccess::runUpdateQuery($sql);
            } catch (Exception $e) {
                print $e->getMessage();
                print_r($e->getTrace());
                die();
            }
            return true;
        }
        return false;
    }

    private function checkIfTableExists($tableName)
    {
        // Check the table exists
        $table_name = strtolower($tableName);

        // Get the schema tables
        $sql = "SHOW Tables;";
        $field_key = "Tables_in_" . DbAccess::schema();

        try {
            $data = DbAccess::getArrayFromSQL($sql);
            foreach ($data as $row) {
                if (strtolower($row[$field_key]) == $table_name) {
                    return true;
                }
            }
        } catch (Exception $e) {
            print $e->getMessage();
            die();
        }
        return false;
    }

    /**
     * Magic setter and getter method
     *
     * @param string $method
     * @param array  $arguments
     * @return false|int|mixed
     * @throws Exception
     */
    public function __call($method, $arguments)
    {
        $set_value_flag = false;
        $field_key = "";

        if (self::$accessMethod == self::ACCESSORS_GETTER_SETTER) {
            // Check if setter or getter
            $setter = substr($method, 0, 3);
            if (($setter == 'get') || ($setter == 'set')) {
                $name = strToLower(substr($method, 3));
                $set_value_flag = ($setter == "set");

                foreach (array_keys($this->fieldList) as $key) {
                    $cleanKey = str_replace("_", "", $key);
                    if ($cleanKey == $name) {
                        $field_key = $key;
                        break;
                    }
                }
                // Also allow a get on id field
                if ($field_key == "") {
                    if (strtolower($method) == strtolower("get" . $this->primaryKey)) {
                        return $this->id();
                    }
                }
            }
        } else {
            if (self::$accessMethod == self::ACCESSORS_SINGLE) {
                $name = strToLower($method);
                $set_value_flag = (sizeof($arguments) > 0);

                foreach (array_keys($this->fieldList) as $key) {
                    $cleanKey = str_replace("_", "", $key);
                    if ($cleanKey == $name) {
                        $field_key = $key;
                        break;
                    }
                }
            }
        }

        // If method name not found then throw error.
        if ($field_key == "") {
            $class = get_called_class();
            throw new Exception('DB Record Class "' . $class . '" does not have a method "' . $method . '".');
        } else {
            $access = isset($this->accessArray[$field_key]) ? $this->accessArray[$field_key] : DbRecord::ACCESS_FULL;
            if ($access == DbRecord::ACCESS_NONE) {
                $class = get_called_class();
                throw new Exception('DB Record Class "' . $class . '" does not have a method "' . $method . '".');
            }
            // Set the value
            if ($set_value_flag) {
                if ($access == DbRecord::ACCESS_READ_ONLY) {
                    $class = get_called_class();
                    throw new Exception('DB Record Class "' . $class . '" does not have a method "' . $method . '".');
                }
                return $this->fieldValue($field_key, $arguments[0]);
            }
            return $this->fieldValue($field_key);
        }
    }

    /**
     * Fill object from existing array
     *
     * @param $aValueArray array
     *
     * @return bool
     */
    public function fillFromArray($aValueArray)
    {
        $this->valArray = array();

        // copy primary key
        if (isset($aValueArray[$this->primaryKey])) {
            $this->valArray[$this->primaryKey] = $aValueArray[$this->primaryKey];
            $this->new_db_row = false;
        }

        // Add value from array passed only if the field is defined.
        foreach ($this->fieldList as $field => $type) {
            // Add field value to array
            if (isset($aValueArray[$field])) {
                $this->valArray[$field] = $aValueArray[$field];
            }
        }
        return true;
    }

    /**
     * Deletes record from database.
     *
     * @return void
     * @throws Exception
     */
    public function delete()
    {
        $sql = "DELETE FROM " . $this->tableName . " WHERE " . $this->primaryKey . "=:$this->primaryKey";
        $bind_parameter_array[] = array(
            DbAccess::PRIVATE_FIELD_NAME  => $this->primaryKey,
            DbAccess::PRIVATE_FIELD_VALUE => $this->id(),
            DbAccess::PRIVATE_FIELD_TYPE  => PDO::PARAM_STR
        );
        $statement = DbAccess::runUpdateQuery($sql, $bind_parameter_array);

        // clear flag to indicate this not saved to database
        if ($statement != -1) {
            $this->new_db_row = true;
        }
    }
}
