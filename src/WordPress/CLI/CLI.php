<?php

namespace ElevenMiles\Iris\WordPress\CLI;

use ElevenMiles\Iris\Debug;
use ElevenMiles\Iris\Iris;
use ElevenMiles\Iris\WordPress\CLI\RunIris;
use Timber\PostQuery;
use WP_CLI;


class CLI
{
    private static $allowed_mime_type = ['image/jpeg', 'image/jpg', 'image/png'];

    public function __invoke($args, $assoc_args)
    {
        WP_CLI::log('');

        $images = new PostQuery([
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => self::$allowed_mime_type,
            'posts_per_page' => 20,
            'orderby' => 'post_date',
            'order' => 'desc',
        ]);
        $progress = WP_CLI\Utils\make_progress_bar('Running Iris', $images);
        // check main image and all sizes and their mime types
        foreach ($images as $image) {

            $metadata = wp_get_attachment_metadata($image->ID);
            $attachment_id = $image->ID;
            Iris::iris_webp_converter($metadata, $attachment_id, $progress);
        }

        WP_CLI::log('Iris complete.');
    }

    // public function iris_convert()
    // {
    //     \WP_CLI::log('Iris is converting images...');

    //     $images = new PostQuery([
    //         'post_type' => 'attachment',
    //         'post_status'    => 'inherit',
    //         'post_mime_type' => ['image/jpeg', 'image/png'],
    //         'posts_per_page' => -1,
    //         'orderby' => 'post_date',
    //         'order' => 'asc',
    //     ]);

    //     // check main image and all sizes and their mime types
    //     foreach ($images as $image) {

    //         $file = wp_get_original_image_path($image->id);
    //         $image_mime = wp_getimagesize($file)['mime'];
    //         $original_image = $image->_wp_attachment_metadata['original_image'];

    //         if ($image_mime != 'image/webp') {
    //             Iris::iris_webp_converter($image->_wp_attachment_metadata, $image->id);
    //         }
    //     }

    //     WP_CLI::log('Iris complete.');
    // }

    public function runIris()
    {
        WP_CLI::log('Starting...');
        $progress = WP_CLI\Utils\make_progress_bar('Running Iris', 0);

        $results = (new RunIris())->iris(
            function ($total) use (&$progress) {
                $progress->setTotal($total);
            },
            function () use (&$progress) {
                $progress->tick();
            },
            $assoc_args['force'] ?? false
        );

        \WP_CLI::log('Iris finished.');
        \WP_CLI::log('Skipped: ' . $results['skipped'] ?? 0);
        \WP_CLI::log('Failed: ' . $results['failed'] ?? 0);
        \WP_CLI::log('Updated: ' . $results['updated'] ?? 0);
    }
}
