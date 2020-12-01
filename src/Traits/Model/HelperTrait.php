<?php

namespace Bundles\Traits\Model;

use Bundles\Collection;
use Exception;

/**
 * Helper Methods for Model Class
 */
trait HelperTrait
{
    private static function clearParams()
    {
        $class = self::class;
        //self::$with = [];

        $class::$where = [];

        $class::$orWhere = [];

        $class::$whereBetween = [];

        $class::$whereIn = [];

        $class::$orderBy = [];

        $class::$limit = "";

        $class::$groupBy = [];

        $class::$customJoins = [];
    }

    private static function checkIfStaticPropertiesExists()
    {
        $class = self::class;
        foreach (["tableName", "columns", "joins", "key"] as $mandatoryProperty) {

            if (!isset(static::$$mandatoryProperty)) {
                throw new Exception('Class ' . get_called_class() . ' failed to define static ' . $mandatoryProperty . ' property');
            }

            $class::$$mandatoryProperty = static::$$mandatoryProperty;
        }
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


        $relationships = get_called_class()::$with;

        foreach ($relationships as $relationship) {
            if (method_exists($instance, $relationship)) {

                $instance->{$relationship}();
            }
        }

        return $instance;
    }

    private static function columns()
    {
        $class = self::class;
        if ($class::$columns === "*") {
            $unformattedColumns = self::tableColumns();
        } else {
            $unformattedColumns = explode(",", $class::$columns ?? "");
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

    private static function filterData(array $data): array
    {
        $params = [];

        $columns = self::tableColumns();


        foreach ($data as $key => $value) {
            if (in_array($key, $columns)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    protected static function getKeyName()
    {
        $class = self::class;

        $arr =  explode('.', $class::$key);
        if (count($arr) > 1) {
            return $arr[1];
        }

        return $arr[0];
    }

    protected static function getTableName()
    {
        $class = self::class;
        return explode(' ', $class::$tableName)[0];
    }

    protected static function getTableAlias()
    {
        $class = self::class;
        $tableNameArr = explode(' ', $class::$tableName);
        return count($tableNameArr) > 1 ? $tableNameArr[1] : null;
    }

    //sets table alias to column if it was not specified
    private static function formatColumn(string $column): string
    {
        $class = self::class;
        if (in_array($column, $class::ALLOWED_MYSQL_FUNCTIONS)) {
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
        $class = self::class;
        if (!in_array($value, $class::ALLOWED_MYSQL_FUNCTIONS)) {
            if (gettype($value) === "string") {
                $value = "'" . $value . "'";
            } else {
                $value = (int) $value;
            }
        }

        return $value;
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
}
