<?php

namespace ElevenMiles\Iris\WordPress\CLI;

use ElevenMiles\Iris\Debug;
use WP_Error;
use Timber\Timber;
use Timber\PostQuery;
use WP_CLI;

class RunIris
{
    const DEFAULT_CHUNK_SIZE = 10;
    protected $creator;
    private static $allowed_mime_type = ['image/jpeg', 'image/jpg', 'image/png'];
    protected $chunkSize = self::DEFAULT_CHUNK_SIZE;

    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'schedules']);

        if (!wp_next_scheduled('force_run_iris')) {
            wp_schedule_event(time(), 'every_2m', 'force_run_iris');
        }

        add_action('force_run_iris', [$this, 'runIris']);
    }

    public function setChunkSize(int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        $this->chunkSize = $chunkSize;
    }

    public function schedules($schedules)
    {
        $schedules['every_2m'] = [
            'interval' => 2 * MINUTE_IN_SECONDS,
            'display' => 'Every 2 minutes',
        ];

        return $schedules;
    }

    public function runIris()
    {
        $shouldRun = get_option('force_iris');

        if ($shouldRun) {
            $this->iris();
        }
    }

    public function iris(?callable $onStart = null, ?callable $onProgress = null, $force = false)
    {
        set_time_limit(0);
        update_option('force_iris', false);
        update_option('processing', true);

        try {
            $total = 0;
            $from = 0;
            $results = [
                'total' => 0,
                'skipped' => 0,
                'failed' => 0,
                'updated' => 0,
            ];

            do {
                $data = $this->getImages();

                if (!$data) {
                    $from += $this->chunkSize;
                    continue;
                }

                if ($from == 0) {
                    $total = $results['total'] = $data['total'] ?? 0;

                    if (is_callable($onStart)) {
                        call_user_func($onStart, $total);
                    }
                }

                $this->process($data, $results, $onProgress, $force);
                $from += $this->chunkSize;

            } while ($from <= $total);

            update_option('skipped', $results['skipped']);
            update_option('failed', $results['failed']);
            update_option('updated', $results['updated']);
            update_option('last_run', time());

            return $results;
        } finally {
            update_option('running', false);
        }
    }

    public function createChunk($images, &$onProgress = null, $force = false)
    {
        $this->getImages($images);

        $results = [];
        foreach ($images as $image) {
            $results[] = $image;

            if (is_callable($onProgress)) {
                call_user_func($onProgress);
            }
        }

        return $results;
    }

    public function needsConverting($image)
    {

        // check if the image needs converting ??
        // add a meta field to an updated attachment
    }

    protected function process($images, &$results, &$onProgress, $force)
    {
        $options = [
            'launch'     => false,
            'exit_error' => true,
            'return'     => false,
            'parse'      => false,
        ];

        $chunkResults = array_chunk($images, self::DEFAULT_CHUNK_SIZE);

        $ids = [];
        foreach ($images as $value) {
            $progress = WP_CLI\Utils\make_progress_bar('Media regenerate', count($chunkResults));
            Debug::debug($progress);
            dump($value->ID);
            WP_CLI::runcommand("media regenerate $value->ID", $options);
            // $ids[] = $value->ID;
            $progress->tick();
        }
        $progress->finish();
        // $ids = implode(" ", $ids);


        // WP_CLI::runcommand("media regenerate $ids", $options);


        foreach ($chunkResults as $result) {
            if ($result === false) {
                $results['skipped'] += 1;
            } elseif ($result instanceof WP_Error) {
                $results['failed'] += 1;
            } else {
                $results['updated'] += 1;
            }
        }
    }

    /**
     * Get images to convert to webp
     *
     * @return array
     */

    protected function getImages(): array
    {
        $images = [];
        get_option('total_images');

        // env variable for process number  // default of 20
        if (filter_var(getenv('IRIS_DEBUG'), FILTER_VALIDATE_BOOLEAN) === true) {
            $getImages = Timber::get_posts([
                'post_type' => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => ['image/jpeg', 'image/png'],
                'posts_per_page' => 20,
                'orderby' => 'post_date',
                'order' => 'asc',
            ]);

            $totalImages = sizeof($images);
            update_option('total_images', $totalImages);

            foreach ($getImages as $image) {
                $file = wp_get_original_image_path($image->ID);
                $image_mime = wp_getimagesize($file)['mime'];

                // meta field for attachment has been complete iris process
                // meta field => when was last processing completed

                // 

                if (in_array($image_mime, self::$allowed_mime_type)) {
                    $images[] = $image;
                }
            }

            return $images;
        }

        return $images;
    }
}
