<?php

namespace ElevenMiles\Iris\Helper;

define('IRIS_ASSET_PATH', get_template_directory_uri() . '/vendor/eleven-miles/iris/src/assets');

class IrisHelper
{

    public static function get_asset_path()
    {
        return IRIS_ASSET_PATH;
    }
}
