<?php
/**
 * DbAccess
 */
namespace Exigen\DbSupport;

use Exception;
use PDO;
use PDOException;
use PDOStatement;

class DbAccess
{
    const PRIVATE_FIELD_NAME = "name";
    const PRIVATE_FIELD_VALUE = "value";
    const PRIVATE_FIELD_TYPE = "type";

    /* @var PDO $dbConnection */
    private static $dbConnection = null;

    private static $config_array = array();

    /**
     * @param array $config_settings
     * @throws Exception
     */
    public static function config(array $config_settings)
    {
        // Verify the required connection details
        $error = self::verifyConfiguration($config_settings);
        if ($error != "") {
            throw (new Exception ($error));
        }

        self::$config_array = $config_settings;

        // Set the magic method type use by the DB records
        $methods = isset($config_settings["methods"]) ? $config_settings["methods"] : "";
        $method_list = array(DbRecord::ACCESSORS_GETTER_SETTER, DbRecord::ACCESSORS_SINGLE, DbRecord::ACCESSORS_NONE);
        $type = in_array($methods, $method_list) ? $methods : DbRecord::ACCESSORS_GETTER_SETTER;
        DbRecord::methodType($type);
    }

    private static function verifyConfiguration(array $config_settings)
    {
        $server = isset($config_settings["server"]) ? $config_settings["server"] : "";
        $user = isset($config_settings["user"]) ? $config_settings["user"] : "";
        $schema = isset($config_settings["schema"]) ? $config_settings["schema"] : "";

        if ($server == "") {
            return "Database initialisation (DbRecord) - 'server' field is missing from configuration array";
        }
        if ($user == "") {
            return "Database initialisation (DbRecord) - 'user' field is missing from configuration array";
        }

        if ($schema == "") {
            return "Database initialisation (DbRecord) - 'schema' field is missing from configuration array";
        }
        return "";
    }

    /**
     * @throws Exception
     */
    private static function checkConnected()
    {
        // Do nothing if already connected.
        if (self::$dbConnection != null) {
            return;
        }
        // Verify the required connection details
        $error = self::verifyConfiguration(self::$config_array);
        if ($error != "") {
            throw (new Exception ($error));
        }
        // Database connection settings...
        $server = isset(self::$config_array["server"]) ? self::$config_array["server"] : "";
        $user = isset(self::$config_array["user"]) ? self::$config_array["user"] : "";
        $port = isset(self::$config_array["port"]) ? self::$config_array["port"] : "";
        $schema = isset(self::$config_array["schema"]) ? self::$config_array["schema"] : "";
        $password = isset(self::$config_array["password"]) ? self::$config_array["password"] : "";
        $additional_options = isset(self::$config_array["options"]) ? self::$config_array["options"] : "";

        // Set up connection options
        $options = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_PERSISTENT         => false
        );
        if (is_array($additional_options) And (count($additional_options) > 0)) {
            foreach ($additional_options as $key => $val) {
                $options [$key] = $val;
            }
        }

        // Connection string
        $connection_str = 'mysql:host=' . $server . ";";
        $connection_str .= ($port == "") ? "" : 'port=' . $port . ";";
        $connection_str .= 'dbname=' . $schema . ";";

        // And connect...
        self::$dbConnection = new PDO($connection_str, $user, $password, $options);
    }

    /**
     * @param       $sql
     * @param array $bind_parameter_array
     * @return PDOStatement
     * @throws Exception
     */
    private static function runQuery($sql, array $bind_parameter_array = array())
    {
        self::checkConnected();
        //prepare the statement
        $statement = self::$dbConnection->prepare($sql);

        foreach ($bind_parameter_array as $fieldInfo) {
            $statement->bindParam(
                ":" . $fieldInfo[self::PRIVATE_FIELD_NAME], $fieldInfo[self::PRIVATE_FIELD_VALUE],
                $fieldInfo[self::PRIVATE_FIELD_TYPE]
            );
        }

        try {
            if ($statement->execute() === false) {
                if (self::testMode()) {
                    print "<pre>";
                    print_r($statement->errorInfo());
                    print_r($statement->debugDumpParams());
                    if (is_array($bind_parameter_array)) {
                        print_r($bind_parameter_array);
                    }
                    print "</pre>";
                    die;
                }
                throw new Exception(__METHOD__ . ':' . 'execute failed: ' . implode(', ', $statement->errorInfo()));
            } else {
                return $statement;
            }
        } catch (PDOException $e) {
            throw new Exception(__METHOD__ . ':' . $e->getMessage());
        }
    }

    /**
     * @param $sql
     * @param $field_detail_array
     * @return int|string
     * @throws Exception
     */
    public static function runUpdateQuery($sql, $field_detail_array = array())
    {
        $statement = self::runQuery($sql, $field_detail_array);
        if ($statement === false) {
            return -1;
        }
        return self::$dbConnection->lastInsertId();
    }

    public static function testMode($flag = null)
    {
        static $st_test_mode = false;
        if (func_num_args() > 0) {
            $st_test_mode = $flag;
        }
        return $st_test_mode;
    }

    public static function schema()
    {
        return isset(self::$config_array["schema"]) ? self::$config_array["schema"] : "";
    }

    /**
     * Gets list of objects.
     * The class name for type of objects to be created
     * is passed.
     * The sql that returns data for object list is passed.
     *
     * @param DbQueryInterface $filter
     * @param string           $key_field
     *
     * @return array of objects
     * @throws Exception
     */
    public static function &getListFromFilter(DbQueryInterface $filter, $key_field = "")
    {
        $objectArray = array();
        try {
            $sql = $filter->getSql();
            $bind_parameter_array = array();

            // convert bind list from filter to the required bind parameter list format
            $filterBindArray = $filter->getBindList();
            if (is_array($filterBindArray)) {
                $bind_parameter_array = self::getBindParameterArray($filterBindArray);
            }

            $statement = DbAccess::runQuery($sql, $bind_parameter_array);

            if ($statement !== false) {
                foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $dbObject = $filter->getListObject();
                    $dbObject->fillFromArray($row);

                    if ($key_field == "") {
                        $objectArray[] = $dbObject;
                    } else {
                        $objectArray[$row[$key_field]] = $dbObject;
                    }
                }
            }
        } catch (PDOException $e) {
            die($e->getMessage());
        }
        return $objectArray;
    }

    /**
     * @param       $sql
     * @param array $bind_array
     * @return array
     * @throws Exception
     */
    public static function getArrayFromSQL($sql, $bind_array = array())
    {
        $bind_parameter_array = self::getBindParameterArray($bind_array);
        $statement = DbAccess::runQuery($sql, $bind_parameter_array);
        if ($statement !== false) {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        return array();
    }

    private static function getBindParameterArray(array $filterBindArray)
    {
        $bind_parameter_array = array();

        foreach ($filterBindArray as $key => $value) {

            // Determine type of value passed
            $type = PDO::PARAM_STR;
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            }
            if (is_bool($value)) {
                $type = PDO::PARAM_INT;
            }
            if (is_null($value)) {
                $type = PDO::PARAM_NULL;
            }

            $bind_parameter_array[] = array(
                self::PRIVATE_FIELD_NAME  => $key,
                self::PRIVATE_FIELD_VALUE => $value,
                self::PRIVATE_FIELD_TYPE  => $type
            );
        }
        return $bind_parameter_array;
    }
}
