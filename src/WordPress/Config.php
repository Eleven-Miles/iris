<?php

namespace ElevenMiles\Iris\WordPress;

/**
 * WordPress configuration for Iris package
 *
 */

class Config
{

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
     * @param array
     * @return array
     */

    public static function setWebpAsDefaultMimeType($formats)
    {
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png'] = 'image/webp';
        return $formats;
    }

    /**
     * Sets default mine type to webp.
     *
     * @param string 
     * @return string
     */

    public static function imageEditorDefaultMimeType($mime_type)
    {
        $mime_type = 'image/webp';

        return $mime_type;
    }

    /**
     * Ensure all network sites include WebP support.
     *
     * @param string $filetypes
     * @return string
     */
    public static function ensureMultisitesSupportWebp($filetypes)
    {
        $filetypes = explode(' ', $filetypes);
        if (!in_array('webp', $filetypes, true)) {
            $filetypes[] = 'webp';
        }
        $filetypes   = implode(' ', $filetypes);

        return $filetypes;
    }
}
