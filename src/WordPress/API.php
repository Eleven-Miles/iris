<?php

namespace ElevenMiles\Iris\WordPress;

use ElevenMiles\Iris\WordPress\Admin\AdminPanel;

class API
{
    const WP_API_NAMESPACE = 'wyke/v1';

    public static function registerAccessRoute()
    {
        register_rest_route(self::WP_API_NAMESPACE, '/iris', [
            'methods' => 'POST',
            'callback' => [AdminPanel::class, 'forceIris'],
            'permission_callback' => [AdminPanel::class, 'canForceIris'],
        ]);
    }
}
