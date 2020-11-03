<?php

/**
 * @author Caio Chami
 * this class provides methods to deal with arrays
 */

namespace Bundles;

class Collection
{
    private $collection = [];

    public function __construct(array $data)
    {
        $this->collection = $data;
    }

    public static function create(array $array, $convertEmptyStringsToNull = false) : self
    {
        if ($convertEmptyStringsToNull) {
            $array = self::convertElements($array, function ($value) {
                if ($value === "") {
                    $value = null;
                }
                return $value;
            });
        }

        return new self($array);
    }


    public static function convertElements(array &$array, \Closure $closure, bool $deep = true) : array
    {
        foreach ($array as $key => &$value) {
            if (gettype($value) === "array" && $deep) {
                self::convertElements($value, $closure);
            } else {
                $array[$key] = $closure($array[$key], $key, $array);
            }
        }

        return $array;
    }

    /**
     * Get the value of array by path
     * 
     * usage: $data =  [
     *     "patient" => [
     *         "name" => "Caio CHami"
     *     ]
     * ];
     * 
     * $collection->getValueByPath(["patient", "name"]);
     * 
     * output: "Caio Chami"
     *
     */
    public function getValueByPath(array $path)
    {
        $getArrayPath = function (array $path, array $array) {
            $callable = function (array $stack, $item) {
                return (
                array_key_exists($item, $stack)
              ) ? $stack[$item] : null;
            };

            return array_reduce($path, $callable, $array);
        };

        return $getArrayPath($path, $this->collection);
    }

    public function find(\Closure $closure)
    {
        foreach ($this->collection as $key => $value) {
            if ($closure($value, $key)) {
                return $value;
            }
        }

        return false;
    }

    public function filterBy($idx, $val, string $operator = "eq") : self
    {
        $callable = [
            "present" => static function($item, $prop, $value) : bool {
                return isset($item[$prop]);
            },
            "eq" => static function ($item, $prop, $value): bool {
                return $item[$prop] === $value;
            },
            "gt" => static function ($item, $prop, $value): bool {
                return $item[$prop] > $value;
            },
            "ge" => static function ($item, $prop, $value): bool {
                return $item[$prop] >= $value;
            },
            "gte" => static function ($item, $prop, $value): bool {
                return $item[$prop] >= $value;
            },
            "lt" => static function ($item, $prop, $value): bool {
                return $item[$prop] < $value;
            },
            "le" => static function ($item, $prop, $value): bool {
                return $item[$prop] <= $value;
            },
            "lte" => static function ($item, $prop, $value): bool {
                return $item[$prop] <= $value;
            },
            "ne" => static function ($item, $prop, $value): bool {
                return $item[$prop] !== $value;
            },
            "whereCountIsGreaterThan" => static function ($item, $prop, $value = 0): bool {
                return count((array) $item[$prop]) > $value;
            },
            "contains" => static function ($item, $prop, $value): bool {
                return \in_array($item[$prop], (array) $value, true);
            },
            "notContains" => static function ($item, $prop, $value): bool {
                return !\in_array($item[$prop], (array) $value, true);
            },
            "newer" => static function ($item, $prop, $value): bool {
                return \strtotime($item[$prop]) > \strtotime($value);
            },
            "older" => static function ($item, $prop, $value): bool {
                return \strtotime($item[$prop]) < \strtotime($value);
            },
        ];

        $array = array_values(
            array_filter(
                $this->collection,
                function ($item) use (
                    $idx,
                    $val,
                    $callable,
                    $operator
                ) {
                    return $callable[$operator]($item, $idx, $val);
                }
            )
        );

        return self::create($array);
    }

    public function first($idx = null)
    {
        $element = array_shift($this->collection);
        
        if (!is_null($idx) && gettype($element) === "array") {
            $idxType = gettype($idx);
            if ($idxType === "string") {
                return $element[$idx];
            } elseif ($idxType === "array") {
                $arr = [];
                foreach ($element as $key => $value) {
                    if (in_array($key, $idx)) {
                        $arr[$key] = $value;
                    }
                }
                return $arr;
            }
        }

        return $element;
    }

    public function exists()
    {
        return $this->first() ? true : false;
    }

    public function pluck(string $column, $args = []) : array
    {
        $customIndex = $args["custom_index"] ?? null;
        $unique = $args["unique"] ?? false;

        $array = [];

        foreach ($this->collection as $key => $item) {
            $type =  gettype($item);

            $value = $type === "object" ? $item->{$column} : $value = $item[$column];
            $index = $customIndex ? ($type === "object" ? $item->{$customIndex} : $value = $item[$customIndex]) : $key;
            
            $array[$index] = $value;
        }

        if($unique){
            return array_unique($array);
        }
       
        return $array;
    }

    public function count(\Closure $callable = null)
    {
        if ($callable) {
            return array_reduce($this->collection, $callable);
        }

        return count($this->collection);
    }

    public function get()
    {
        return $this->collection;
    }
}
