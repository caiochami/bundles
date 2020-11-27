<?php

/**
 * Model 
 * 
 * @author Caio Chami
 * @since 2020-09-03
 */

namespace Bundles;

use \PDO;

use Bundles\Collection;

use \Exception;

class Model
{

    /**
     * @var PDO $conn
     */

    protected static $conn;

    /**
     * @var array $with
     */

    protected static $with = [];

    protected static $where = [];

    protected static $orWhere = [];

    protected static $whereBetween = [];

    protected static $whereIn = [];

    protected static $orderBy = [];

    protected static $limit = null;

    protected static $customJoins = [];

    private static $tableName = null;

    private static $columns = null;

    private static $joins = null;

    private static $key = null;

    private static $groupBy = [];

    const ALLOWED_MYSQL_FUNCTIONS = ["NOW()", "CURRENT_DATE()", "CURRENT_TIMESTAMP()"];

    public function __construct($connection = null)
    {
        if ($connection) {
            self::$conn = $connection;
        }
    }

    public static function getSql($conditions = "", $sort = "", $limit = "")
    {
        $sql =
            "SELECT 
        " . self::renderSelect() . "
        FROM 
        " . self::$tableName . "
        " . self::renderJoins() . "
        " . self::renderConditions() . "
        " . self::renderGroupBy() . "
        " . self::renderOrderBy() . "
        " . self::renderLimit() . "
        {$conditions} {$sort} {$limit};";

        return $sql;
    }

    private static function checkIfStaticPropertiesExists()
    {
        foreach (["tableName", "columns", "joins", "key"] as $mandatoryProperty) {

            if (!isset(static::$$mandatoryProperty)) {
                throw new Exception('Class ' . get_called_class() . ' failed to define static ' . $mandatoryProperty . ' property');
            }

            self::$$mandatoryProperty = static::$$mandatoryProperty;
        }
    }

    public static function setConnection(PDO $conn)
    {
        self::checkIfStaticPropertiesExists();
        self::$conn = $conn;
        self::clearParams();
    }

    public static function all(PDO $conn, $conditions = "")
    {
        self::setConnection($conn);
        return new Collection(self::fetch($conditions));
    }

    //define which database connection to use
    public static function use(PDO $conn)
    {
        self::setConnection($conn);
        return new static;
    }

    //sets table alias to column if it was not specified
    private static function formatColumn(string $column): string
    {
        if (in_array($column, self::ALLOWED_MYSQL_FUNCTIONS)) {
            return $column;
        }

        $tableAlias = self::getTableAlias();

        $formattedTableAlias = "";

        if ($tableAlias) {
            $formattedTableAlias = self::getTableAlias() . '.';
        }

        $exploded = explode('.', $column);
        return count($exploded) > 1 ? $column : $formattedTableAlias . $column;
    }

    private static function formatValue($value)
    {
        if (!in_array($value, self::ALLOWED_MYSQL_FUNCTIONS)) {
            if (gettype($value) === "string") {
                $value = "'" . $value . "'";
            } else {
                $value = (int) $value;
            }
        }

        return $value;
    }

    public static function table(string $tableName)
    {
        self::$tableName = $tableName;
        return new static;
    }

    private static function formatJoinArgs(array $args): string
    {
        $length = count($args);

        if ($length === 3) {
            [$joinableTable, $firstColumn, $secondColumn] = $args;
            return $joinableTable . " ON " . $firstColumn . " = " . $secondColumn;
        } else if ($length === 4) {
            [$joinableTable, $firstColumn, $operator, $secondColumn] = $args;
            return $joinableTable . " ON " . $firstColumn . " " . $operator . " " . $secondColumn;
        }

        return "";
    }

    public static function join()
    {
        $args = func_get_args();

        $join = self::formatJoinArgs($args);

        self::$customJoins[] = "INNER JOIN " . $join;

        return new static;
    }

    public static function leftJoin()
    {
        $args = func_get_args();

        $join = self::formatJoinArgs($args);

        self::$customJoins[] = "LEFT JOIN " . $join;

        return new static;
    }

    public static function rightJoin()
    {
        $args = func_get_args();

        $join = self::formatJoinArgs($args);

        self::$customJoins[] = "RIGHT JOIN " . $join;

        return new static;
    }

    private static function renderJoins()
    {
        if (count(self::$customJoins)) {
            return implode(" ", self::$customJoins);
        }
        return self::$joins;
    }

    private static function renderSelect()
    {
        return self::$columns;
    }

    public static function select(array $columnsSet): self
    {
        $columns = [];

        foreach ($columnsSet as $set) {

            if (gettype($set) === "array" && count($set) === 1) {
                foreach ($set as $column => $alias) {
                    $columns[] = $column . " " . $alias;
                }
            } else {
                $columns[] = $set;
            }
        }

        self::$columns = implode(', ', $columns);
        return new static;
    }

    public static function groupBy(array $groupBy): self
    {
        self::$groupBy = $groupBy;
        return new static;
    }

    private static function renderGroupBy()
    {
        return count(self::$groupBy) ? " GROUP BY " . implode(",", self::$groupBy) : "";
    }

    public static function where(string $column, $value)
    {
        $totalArgs = func_num_args();
        $args = func_get_args();

        $column = self::formatColumn($column);

        $operator = "=";

        //sets default operator to equals if middle argument is omitted

        if ($totalArgs > 2) {
            $operator = $args[1];
            $value = $args[2];
        }

        self::$where[] = $column . ' ' . $operator . ' ' . self::formatValue($value);

        return new static;
    }

    public static function orWhere(string $column, string $value)
    {

        $totalArgs = func_num_args();
        $args = func_get_args();

        $column = self::formatColumn($column);

        $operator = "=";

        if ($totalArgs > 2) {
            $operator = $args[1];
            $value = $args[2];
        }

        self::$orWhere[] = $column . ' ' . $operator . ' ' . self::formatValue($value);

        return new static;
    }

    public static function whereIn(string $column, array $values)
    {

        $wrappedValues = [];

        $length = count($values);

        foreach ($values as $key => $value) {
            $wrappedValues[] = self::formatValue($value);
        }

        self::$whereIn[] = self::formatColumn($column) . " IN (" . implode(",", $wrappedValues) . ")";
        return new static;
    }

    private static function tableColumns(): array
    {
        $sql = "SHOW COLUMNS FROM " . self::getTableName() . "; ";
        $stmt = self::$conn->prepare($sql);
        $stmt->execute();
        $data = [];
        while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $resource["Field"];
        }
        $stmt->closeCursor();

        return $data;
    }

    private static function columns()
    {
        if (self::$columns === "*") {
            $unformattedColumns = self::tableColumns();
        } else {
            $unformattedColumns = explode(",", static::$columns ?? "");
        }


        $columns = [];

        foreach ($unformattedColumns as $unformattedColumn) {
            $unformattedColumn = str_replace([" as ", " AS "], ["AS", "AS"], $unformattedColumn);
            $formattedColumn = explode("AS", $unformattedColumn);
            $column = trim($formattedColumn[0]);
            $dismemberedColumn = explode(".", $column);
            $fieldName = $dismemberedColumn[1] ?? $dismemberedColumn[0];

            $columns[$fieldName] = [
                "field_name" =>  $fieldName,
                "column" => $column,
                "alias" => $formattedColumn[1] ?? null
            ];
        }

        return Collection::create($columns);
    }

    private static function isParamAColumn(string $param): bool
    {
        $isColumn = self::columns()->find(function ($value) use ($param) {
            return $value["column"] === $param;
        });

        return !is_null($isColumn);
    }

    public static function whereBetween(string $column, string $startsAt, string $endsAt)
    {
        if (!self::isParamAColumn($startsAt)) {
            $startsAt = "'{$startsAt}'";
            $endsAt = "'{$endsAt}'";
        }

        self::$whereBetween[] = self::formatColumn($column) . " BETWEEN {$startsAt} AND {$endsAt}";
        return new static;
    }

    public static function orderBy(string $column, string $direction = "ASC")
    {
        self::$orderBy[] = self::formatColumn($column) . " " . $direction;
        return new static;
    }

    private static function renderConditions()
    {
        $WHERE_LENGTH = count(self::$where);
        $WHERE = $WHERE_LENGTH ? " AND " . implode(' AND ', self::$where) : "";

        $OR_WHERE_LENGTH = count(self::$orWhere);
        $OR_WHERE = $OR_WHERE_LENGTH ? " AND " . implode(' AND ', self::$orWhere) : "";

        $WHERE_BETWEEN_LENGTH = count(self::$whereBetween);
        $WHERE_BETWEEN = $WHERE_BETWEEN_LENGTH ? " AND " . implode(' AND ', self::$whereBetween) : "";

        $WHERE_IN_LENGTH = count(self::$whereIn);
        $WHERE_IN = $WHERE_IN_LENGTH ? " AND " . implode(' AND ', self::$whereIn) : "";

        $CHECK_IF_HAS_OPTIONS = array_reduce([
            $WHERE_LENGTH,
            $WHERE_BETWEEN_LENGTH,
            $OR_WHERE_LENGTH,
            $WHERE_IN_LENGTH,
        ], function ($haystack, $length) {
            return $haystack + $length;
        });

        $OPTIONS = $CHECK_IF_HAS_OPTIONS ? ("WHERE 1=1 " . $WHERE . $OR_WHERE . $WHERE_IN . $WHERE_BETWEEN) : "";

        return $OPTIONS;
    }

    private static function renderLimit()
    {
        return self::$limit ?? "";
    }

    private static function renderOrderBy()
    {
        return count(self::$orderBy) ? "ORDER BY " . implode(",", self::$orderBy) : "";
    }

    public static function limit($value)
    {
        self::$limit = $value;


        if (gettype($value) === "integer") {
            self::$limit = "LIMIT " . $value;
        }

        return new static;
    }

    public static function retrieve($options = ['debug' => false, 'show_query' => false])
    {
        return new Collection(self::fetch("", "", "", $options));
    }

    public static function with(array $relationships)
    {
        self::$with = $relationships;

        return new static(self::$conn);
    }

    public static function find(PDO $conn, int $id, $options = [])
    {
        self::setConnection($conn);
        $id = htmlspecialchars(strip_tags($id));

        $collection = self::fetch('WHERE ' . static::$key . ' = ' . $id, '', 'LIMIT 0,1', $options);
        return count($collection) ? $collection[0] : null;
    }


    private static function setProperties($instance, array $data)
    {
        if (defined("CONNECTION_ID")) {
            $instance->connection_id = intval(CONNECTION_ID);
        }

        if (defined("CONNECTION_NAME")) {
            $instance->connection_name = CONNECTION_NAME;
        }

        foreach ($data as $key => $value) {

            $mutator = "set" . ucfirst($key) . "Attribute";

            if (method_exists(static::class, $mutator)) {

                $value = $instance->{$mutator}($value) ?? null;
            }

            if (\preg_match("/\_id/i", $key) || $key === "id" || $key === self::getKeyName()) {
                $value = intval($data[$key]);
                $instance->{$key} = $value > 0 ? $value : null;
            } else {
                $instance->{$key} = $value;
            }
        }


        $relationships = self::$with;

        foreach ($relationships as $relationship) {
            if (method_exists($instance, $relationship)) {

                $instance->{$relationship}();
            }
        }

        return $instance;
    }

    private static function clearParams()
    {
        //self::$with = [];

        self::$where = [];

        self::$orWhere = [];

        self::$whereBetween = [];

        self::$whereIn = [];

        self::$orderBy = [];

        self::$limit = "";

        self::$groupBy = [];

        self::$customJoins = [];
    }

    private static function fetch($conditions = "", $sort = "", $limit = "", $options = ['debug' => false, 'show_query' => false])
    {
        if (!self::$conn) {
            throw new Exception('Connection was not set');
        }

        $sql = self::getSql($conditions, $sort, $limit);

        return self::execute(function ($stmt, $options) {

            $collection = [];

            if ($stmt->execute() && $stmt->rowCount()) {
                while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $self = self::setProperties(new static($options['connection']), $resource);
                    array_push($collection, $self);
                }

                $stmt->closeCursor();
            }

            return $collection;
        }, array_merge($options, ['sql' => $sql]));
    }

    public static function createOrUpdate(PDO $conn, array $data)
    {
        self::setConnection($conn);

        $data = self::filterData($data);

        $updateParams = [];
        $insertColumns = [];
        $insertValues = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== static::$key) {
                $updateParams[] = "{$key} = ?";
                $insertColumns[] = $key;
                $insertValues[] = "?";

                $values[] = addSlashes($value);
            }
        };

        $params = [];

        foreach ($data as $key => $value) {
            if ($key !== self::$key) $params[] = "{$key} = {$value}";
        };

        $sql =
            "INSERT INTO " . self::getTableName() . " (" . implode(",", $insertColumns) . ")
        VALUES ( " . implode(",", $insertValues) . " ) ON DUPLICATE KEY UPDATE " . implode(",", $updateParams);

        $stmt = self::$conn->prepare($sql);

        if ($stmt->execute(array_merge($values, $values))) {
            $id = self::lastInsertedId();
            return self::find(self::$conn, $id);
        }

        var_dump($stmt->errorInfo());

        return false;
    }

    public static function update(PDO $conn, int $id, array $data, array $options = [])
    {
        self::setConnection($conn);

        $data = self::filterData($data);
        $params = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== static::$key) {
                $params[] = "{$key} = ?";
                $values[] = $value;
            }
        };

        $sql = "UPDATE " . self::getTableName() . " SET " . implode(", ", $params) . " WHERE  " . self::getKeyName() . " = " . $id;

        return self::execute(function ($stmt, $options) {
            if ($stmt->execute($options['values'])) {
                return self::find($options['connection'], $options['id']);
            }

            return false;
        }, array_merge($options, [
            'values' => $values,
            'sql' => $sql,
            'id' => $id
        ]));
    }

    protected static function getKeyName()
    {
        $arr =  explode('.', self::$key);
        if (count($arr) > 1) {
            return $arr[1];
        }

        return $arr[0];
    }

    protected static function getTableName()
    {
        return explode(' ', self::$tableName)[0];
    }

    protected static function getTableAlias()
    {
        $tableNameArr = explode(' ', self::$tableName);
        return count($tableNameArr) > 1 ? $tableNameArr[1] : null;
    }

    

    

    private static function execute(\Closure $closure, array $options = [])
    {
        $connection = self::$conn;

        $stmt = $connection->prepare($options['sql']);

        $execution = $closure($stmt, array_merge($options, ['connection' => $connection]));

        if (isset($options['debug']) && boolval($options['debug'])) {
            var_dump($stmt->errorInfo());
        }

        if (isset($options['show_query']) && boolval($options['show_query'])) {
            var_dump($options['sql']);
        }

        return $execution;
    }

    private static function lastInsertedId()
    {
        $sql = "SELECT MAX(" . self::getKeyName() . ") AS last_inserted_id FROM " . self::getTableName() . "; ";

        return self::execute(function ($stmt) {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $row['last_inserted_id'];
        }, ['sql' => $sql]);
    }

    private static function filterData(array $data): array
    {
        $params = [];

        $columns = self::tableColumns();
       

        foreach ($data as $key => $value) {
            if(in_array($key, $columns)){
                $params[$key] = $value;
            }
        }

        return $params;
    }

    public static function create(PDO $conn, array $data, $options = [])
    {
        self::setConnection($conn);

        $params = self::filterData($data);

        $values =  array_values($params);

        $columns = array_keys($params);

        $tableName = self::getTableName();

        $sql = "INSERT INTO " . $tableName . " (" . implode(",", $columns) . ")";

        $bindValues = array_map(function () {
            return "?";
        }, $values);

        $sql .= "VALUES (" . implode(",", $bindValues) . ")";

        return self::execute(
            function ($stmt, $options) {

                if ($stmt->execute($options['values'])) {
                    $id = self::lastInsertedId();
                    $stmt->closeCursor();
                    return self::find($options['connection'], $id);
                }

                return false;
            },
            array_merge($options, [
                'sql' => $sql,
                'values' => $values
            ])
        );
    }

    public static function destroy(PDO $conn, int $id, array $options = [])
    {

        self::setConnection($conn);

        $sql =
            "DELETE FROM  
        " . self::getTableName() . "
        WHERE " . self::getKeyName() . " = ?;";

        return self::execute(function ($stmt, $options) {

            $stmt->bindParam('1', $options['id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        }, array_merge($options, ['sql' => $sql, 'id' => $id]));
    }

    public function delete()
    {
        return self::destroy(self::$conn, $this->id);
    }

    private function copycat(self $instance): void
    {
        foreach (get_object_vars($instance) as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function save(array $options = [])
    {
        $params = [];

        foreach (get_object_vars($this) as $key => $value) {
            $params[$key] = $value;
        }

        $connection = self::$conn;

        if (isset($this->id)) {
            $newInstance = self::update($connection, $this->id, $params, $options);
        } else {
            $newInstance = self::create($connection, $params, $options);
        }

        if ($newInstance) {
            $this->copycat($newInstance);
        }
        
        return $newInstance;
    }

    
}
