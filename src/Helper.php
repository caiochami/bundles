<?php

namespace Bundles;

use \DateTime;

use Carbon\Carbon;

class Helper
{
    public static function isDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function convertToCarbon()
    {
        $args = func_get_args();

        foreach ($args as &$arg) {
            $arg = Carbon::parse($arg);
        }

        return $args;
    }
}
