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

    /**  @var string */
    private static $schema = "";

    /**
     * @param array $config_settings
     * @throws Exception
     */
    public static function config(array $config_settings)
    {
        $server = isset($config_settings["server"]) ? $config_settings["server"] : "";
        $user = isset($config_settings["user"]) ? $config_settings["user"] : "";
        $schema = isset($config_settings["schema"]) ? $config_settings["schema"] : "";
        $password = isset($config_settings["password"]) ? $config_settings["password"] : "";
        $methods = isset($config_settings["methods"]) ? $config_settings["methods"] : "";

        if ($server == "") {
            throw (new Exception (
                "Database initialisation (DbRecord) - 'server' field is missing from configuration array"));
        }
        if ($user == "") {
            throw (new Exception (
                "Database initialisation (DbRecord) - 'user' field is missing from configuration array"));
        }

        if ($schema == "") {
            throw (new Exception (
                "Database initialisation (DbRecord) - 'schema' field is missing from configuration array"));
        }

        self::$schema = $schema;

        $options = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_PERSISTENT         => false
        );

        $dsn = "mysql:host=" . $server . ";dbname=" . $schema;
        self::$dbConnection = new PDO($dsn, $user, $password, $options);

        $method_list = array(DbRecord::METHODS_GETTER_SETTER, DbRecord::METHODS_SINGLE, DbRecord::METHODS_NONE);
        $type = in_array($methods, $method_list) ? $methods : DbRecord::METHODS_GETTER_SETTER;
        DbRecord::methodType($type);
    }

    /**
     * @param      $sql
     * @param array $bind_parameter_array
     * @return PDOStatement
     * @throws Exception
     */
    public static function runQuery($sql, array $bind_parameter_array = array())
    {
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
    public static function runUpdateQuery($sql, $field_detail_array)
    {
        $statement = self::runQuery($sql, $field_detail_array);
        if ($statement === false) {
            return -1;
        }
        return self::$dbConnection->lastInsertId();
    }

    private static function testMode($flag = null)
    {
        static $st_test_mode = false;
        if (func_num_args() > 0) {
            $st_test_mode = $flag;
        }
        return $st_test_mode;
    }

    public static function schema()
    {
        return self::$schema;
    }

    /**
     * Gets list of objects.
     * The class name for type of objects to be created
     * is passed.
     * The sql that returns data for object list is passed.
     *
     * @param DbFilterInterface $filter
     * @param string            $key_field
     *
     * @return array of objects
     * @throws Exception
     */
    public static function &getListFromFilter(DbFilterInterface $filter, $key_field = "")
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
                $data = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($data as $row) {
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
