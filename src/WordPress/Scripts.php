<?php

namespace ElevenMiles\Iris\WordPress;

use ElevenMiles\Iris\Helper\IrisHelper;

class Scripts
{

    /**
     * Enqueue a script in the WordPress admin on edit.php.
     *
     * @param int $hook Hook suffix for the current admin page.
     */

    public static function enqueue_admin_script($hook)
    {
        if ('toplevel_page_iris' != $hook) {
            return;
        }

        if (is_admin()) {

            wp_enqueue_style('iris', IrisHelper::get_asset_path() . '/styles/iris.scss');
            wp_enqueue_script('run-iris', IrisHelper::get_asset_path() . '/js/run-iris.js');
            wp_localize_script('run-iris', 'wpApiSettings', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);
        }
    }
}
