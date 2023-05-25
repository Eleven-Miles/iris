<?php

namespace ElevenMiles\Iris\Helper;

define('IRIS_ASSET_PATH', get_site_url() . '/vendor/eleven-miles/iris/src/assets');
define('IRIS_ACTION_SCHEDULER_PATH', get_site_url() . '/vendor/eleven-miles/iris/libraries/action-scheduler/action-scheduler.php');

class IrisHelper
{

    public static function get_asset_path()
    {
        return IRIS_ASSET_PATH;
    }

    public static function get_action_scheduler_path()
    {
        return IRIS_ACTION_SCHEDULER_PATH;
    }
}
