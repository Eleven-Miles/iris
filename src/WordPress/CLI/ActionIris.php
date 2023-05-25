<?php

namespace ElevenMiles\Iris\WordPress\CLI;




use ElevenMiles\Iris\Debug;
use WP_Error;
use Timber\Timber;
use Timber\PostQuery;
use WP_CLI;

class ActionIris
{
    const DEFAULT_CHUNK_SIZE = 10;
    protected $creator;
    private static $allowed_mime_type = ['image/jpeg', 'image/jpg', 'image/png'];
    protected $chunkSize = self::DEFAULT_CHUNK_SIZE;

    public function __construct()
    {
       
    }

    public function webpConverter(Type $var = null)
    {
        # code...
    }
}
