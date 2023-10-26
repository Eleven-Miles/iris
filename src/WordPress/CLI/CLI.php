<?php

namespace ElevenMiles\Iris\WordPress\CLI;

use ElevenMiles\Iris\Debug;
use Timber\Post;
use Timber\PostQuery;
use WP_CLI;


class CLI
{
    private static $allowed_mime_type = ['image/jpeg', 'image/jpg', 'image/png'];

    public function __invoke()
    {
        WP_CLI::log('Running Iris');

        $images = new PostQuery([
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => self::$allowed_mime_type,
            'posts_per_page' => -1,
            'orderby' => 'post_date',
            'order' => 'asc',
        ]);

        // check main image and all sizes and their mime types
        foreach ($images as $image) {
            $attachment_id = $image->ID;
            as_enqueue_async_action('webp_bulk_process_schedule', [$attachment_id], 'webpBulkConverterTask');
        }

        WP_CLI::log('Iris complete.');
    }

    public static function convertById($id)
    {
        $image_id = $id[0];

        $image = new PostQuery([
            'p' => $image_id,
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => self::$allowed_mime_type,
            'posts_per_page' => 1,
        ]);

        $attachment_id = $image[0]->ID;

        as_enqueue_async_action('webp_bulk_process_schedule', [$attachment_id], 'webpBulkConverterTask');
        WP_CLI::log('Iris complete.');
    }
}
