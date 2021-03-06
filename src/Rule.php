<?php

/**
 * @author Caio Chami
 * @since 2020-10-19
 * 
 * Subject: 'Rules' 
 * 
 */

namespace Bundles;

use Bundles\DB;

use Bundles\Helper;

class Rule
{

   const MESSAGES = [
      'required' => 'O campo %s é obrigatório',
      'required_if' => 'O campo %s é obrigatório quando o campo %s conter o valor %s',
      'required_with' => 'O campo %s é obrigatório quando o campo %s estiver presente',
      'in' => 'O campo %s não corresponde com as opções disponíveis',
      'notIn' => 'O campo %s não deve corresponder com as opções',
      'string' => 'O campo %s deve ser uma string',
      'array' => 'O campo %s deve ser um array',
      'integer' => 'O campo %s deve ser um valor inteiro',
      'boolean' => 'O campo %s deve ser um valor boleano',
      'url' => 'O campo %s não é uma URL válida',
      'email' => 'O campo %s não é um endereço de e-mail válido',
      'date_format' => 'O campo %s deve possuir o formato %s',
      'after' => 'O campo %s deve ser uma data posterior a %s',
      'before' => 'O campo %s deve ser uma data anterior a %s',
      'before_or_equal' => 'O campo %s deve ser uma data anterior ou igual a data %s',
      'after_or_equal' => 'O campo %s deve ser uma data posterior ou igual a data %s',
      'digits' => 'O valor do campo %s deve contér até %d dígitos',
      'digits_between' => 'O valor do campo %s deve ser entre %d e %d',
      'minimum' => 'O campo %s deve possuir no mínimo %d caractéres',
      'maximum' => 'O campo %s deve possuir no máximo %d caractéres',
      'exists' => 'O valor do campo %s não existe nos nossos registros',
      'unique' => 'Registro existente nos nossos registros',
      'gte' => 'O tamanho do campo %s deve ser maior ou igual a %s',
      'lte' => 'O tamanho do campo %s deve ser menor ou igual a %s',
      'gt' => 'O tamanho do campo %s deve ser maior que %s',
      'lt' => 'O tamanho do campo %s deve ser menor que %s',
      'confirmed' => 'O campo %s não pôde ser confirmado'
   ];

   public static function getErrorMessage($ruleName, $params, $customMessages = [])
   {
      $message = self::MESSAGES[$ruleName];
      if (count($customMessages) && array_key_exists($ruleName, $customMessages)) {
         $message = $customMessages[$ruleName];
      }

      return vsprintf($message, $params);
   }

   public static function getValueSize($value)
   {
      $type = gettype($value);

      switch ($type) {
         case 'string':
            return strlen($value);
         case 'array':
            return count($value);
         case 'integer':
            return $value;
         default:
            return 0;
      }
   }

   public static function nullable()
   {
      return true;
   }

   public static function bail()
   {
      return true;
   }

   public static function required($value)
   {
      return !empty($value) && !is_null($value);
   }

   public static function required_with($value)
   {
      return self::required($value);
   }

   public static function required_if($value)
   {
      return self::required($value);
   }

   public static function in($value, array $array)
   {
      return in_array($value, $array);
   }

   public static function notIn($value, array $array)
   {
      return !in_array($value, $array);
   }

   public static function string($value)
   {
      return is_string($value);
   }

   public static function integer($value): bool
   {
      return is_int($value);
   }

   public static function digits($value, $params)
   {
      $length = intval($params[0]);
      return strlen((string)$value) <= $length;
   }

   public static function digits_between($value, $params)
   {
      $value = intval($value);
      $start = intval($params[0]);
      $end = intval($params[1]);
      return $value >= $start && $value <= $end;
   }

   public static function boolean($value): bool
   {
      return is_bool($value);
   }

   public static function email($value): bool
   {
      return filter_var($value, FILTER_VALIDATE_EMAIL) ? true : false;
   }

   public static function url($value): bool
   {
      return preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $value) ? true : false;
   }

   public static function before_or_equal($value, $params)
   {
      try {
         $dates = Helper::convertToCarbon($value, $params[0]);
         return $dates[0]->lte($dates[1]);
      } catch (\Throwable $th) {
         return false;
      }
   }

   public static function after_or_equal($value, $params)
   {
      try {
         $dates = Helper::convertToCarbon($value, $params[0]);
         return $dates[0]->gte($dates[1]);
      } catch (\Throwable $th) {
         return false;
      }
   }

   public static function before($value, $params)
   {
      try {
         $dates = Helper::convertToCarbon($value, $params[0]);
         return $dates[0]->lt($dates[1]);
      } catch (\Throwable $th) {
         return false;
      }
   }

   public static function after($value, $params)
   {
      try {
         $dates = Helper::convertToCarbon($value, $params[0]);
         return $dates[0]->gt($dates[1]);
      } catch (\Throwable $th) {
         return false;
      }
   }

   public static function date_format($value, $params)
   {
      return Helper::isDate($value, $params[0]);
   }





   public static function array($value)
   {
      return is_array($value);
   }

   public static function gt($value, $params)
   {
      $valueSize = self::getValueSize($value);
      $param = (int)$params[0];
      return $valueSize > $param;
   }

   public static function gte($value, $params)
   {

      $valueSize = self::getValueSize($value);
      $param = (int)$params[0];
      return $valueSize >= $param;
   }

   public static function lte($value, $params)
   {
      $valueSize = self::getValueSize($value);
      $param = (int)$params[0];
      return $valueSize <= $param;
   }

   public static function lt($value, $params)
   {
      $valueSize = self::getValueSize($value);
      $param = (int)$params[0];
      return $valueSize < $param;
   }

   public static function size($value, $params)
   {
      $valueSize = self::getValueSize($value);
      $param = (int)$params[0];
      return $valueSize === $param;
   }

   public static function minimum($value, $params)
   {
      return strlen($value) >= $params[0];
   }

   public static function maximum($value, $params)
   {
      return strlen($value) <= $params[0];
   }

   public static function confirmed($value, $params)
   {
      return $value === $params[0];
   }

   public static function unique($value, $params)
   {
      return !self::exists($value, $params);
   }

   public static function exists($value, $params)
   {
      [$table, $column, $connection, $ignore] = $params;

      $exists = DB::use($connection)
         ->table($table)
         ->select([$column]);

      if ($ignore) {
         $exists = $exists->where($column, "!=", $ignore);
      }

      $exists =
         $exists->where($column, $value)
         ->retrieve()
         ->exists();

      if ($exists) {
         return true;
      }

      return false;
   }
}
