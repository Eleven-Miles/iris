<?php

namespace ElevenMiles\Iris\WordPress\Admin;

use DateTime;
use ElevenMiles\Iris\Helper\IrisHelper;
use Timber\Timber;
use Timber\PostQuery;

class AdminPanel
{
    const WP_API_NAMESPACE = 'wyke/v1';

    public function __construct()
    {
        add_action('admin_menu', [__CLASS__, 'addOptionsPage']);
        add_action('admin_head', [__CLASS__, 'irisDashicon']);
    }

    public static function addOptionsPage()
    {
        add_menu_page(
            'Iris',
            'Iris',
            'manage_options',
            'iris',
            [__CLASS__, 'render'],
            'dashicons-iris',
            66
        );
    }

    public static function irisDashicon()
    {
        echo '
            <style>
                .dashicons-iris {
                    background-image: url("' . IrisHelper::get_asset_path() . '/images/dashicon-iris.png");
                    background-repeat: no-repeat;
                    background-position: center; 
                }
            </style>
        ';
    }

    public static function render()
    {
        $context = Timber::get_context();

        if ($lastRun = get_option('last_run')) {
            $lastRun = new DateTime('@' . $lastRun);
        }

        $context['iris_scheduled'] = get_option('force_iris');
        $context['is_processing'] = get_option('processing');
        $context['iris_in_progress'] =  $context['iris_scheduled'] || $context['is_processing'];
        $context['namespace'] = self::WP_API_NAMESPACE;
        $context['iris'] = [
            'total_images' => get_option('total_images'),
            'skipped' => get_option('skipped'),
            'failed' => get_option('failed'),
            'updated' => get_option('updated'),
            'last_run' => $lastRun ? $lastRun->format('d/m/Y h:ia') : 'Never',
        ];

        Timber::render(__DIR__ . "/views/actions.twig", $context);
    }

    public static function forceIris()
    {
        update_option('force_iris', true);
    }

    public static function canForceIris()
    {
        return current_user_can('manage_options');
    }
}
