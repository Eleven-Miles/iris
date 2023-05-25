<?php

namespace ElevenMiles\Iris\WordPress\CLI;

use WP_CLI;

class Register
{
    public static function registerCLI()
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('iris', CLI::class);
            WP_CLI::add_command('force-iris', [CLI::class, 'irisConvert']);
        }
    }
}
