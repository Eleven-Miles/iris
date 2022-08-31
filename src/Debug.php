<?php

namespace ElevenMiles\Iris;

class Debug
{

    /**
     * Debug function to help test and return file meta
     *
     * Creates a iris.log file in project root
     * Add env varibales
     * WP_DEBUG='true'
     * WP_DEBUG_LOG='true'
     * 
     */

    public static function debug($info)
    {
        $message = null;

        if (is_string($info) || is_int($info) || is_float($info)) {
            $message = $info;
        } else {
            $message = var_export($info, true);
        }

        if ($fh = fopen(ABSPATH . '/iris.log', 'a')) {
            fputs($fh, date('Y-m-d H:i:s') . " $message\n");
            fclose($fh);
        }
    }
}
