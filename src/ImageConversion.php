<?php

namespace ElevenMiles\Iris;

/**
 * Class ImageConversion
 */

class ImageConversion
{
    /**
     * ImageConversion constructor 
     */
    public function __construct()
    {
        add_filter('wp_image_editors', [__CLASS__, 'defaultToGD']);
        add_filter('image_editor_output_format', [__CLASS__, 'createWebPThumbnails']);
        apply_filters('image_editor_default_mime_type', [__CLASS__, 'imageEditorDefaultMimeType']);
    }


    /**
     * Change the default image editor class to WP_Image_Editor_GD.
     *
     * @param string[] 
     * Array of available image editor class names. 
     * Defaults are 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD'.
     */

    public static function defaultToGD()
    {
        return ['WP_Image_Editor_GD', 'WP_Image_Editor_Imagick'];
    }

    /**
     * Filters the image editor output format mapping to webp.
     *
     * @param string[] $formats 
     * 
     */

    public static function createWebPThumbnails($formats)
    {
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png'] = 'image/webp';

        return $formats;
    }

    /**
     * Sets default mine type to webp.
     *
     * @param string[] $formats 
     * 
     */

    public static function imageEditorDefaultMimeType($mine_type)
    {
        $mine_type = 'image/webp';

        return $mine_type;
    }
}
