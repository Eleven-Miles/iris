<?php

namespace ElevenMiles\Iris\Helper;

define('IRIS_ASSET_PATH', get_site_url() . '/vendor/eleven-miles/iris/src/assets');

class IrisHelper
{

    public static function get_asset_path()
    {
        return IRIS_ASSET_PATH;
    }
}
