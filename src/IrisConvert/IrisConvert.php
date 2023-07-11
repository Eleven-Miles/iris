<?php

namespace ElevenMiles\Iris\IrisConvert;

use DeliciousBrains\WP_Offload_Media\Items\Media_Library_Item;
use ElevenMiles\Iris\Debug;

class IrisConvert
{

    private $file_path;
    private $file_dirname;
    private $file_ext;
    private $file_name_no_ext;
    private $image_metadata;
    private $image_id;
    private $array_of_sizes_to_be_converted = [];
    private $image_to_regenerate = [];
    private static $allowed_mime_type = ['image/jpeg', 'image/jpg', 'image/png'];

    /**
     * @param $attachment_id
     * @param $metadata
     */

    public function __construct($attachment_id, $metadata)
    {
        $this->file_path = get_attached_file($attachment_id);
        $this->file_dirname = pathinfo($this->file_path, PATHINFO_DIRNAME);
        $this->file_ext = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
        $this->file_name_no_ext = pathinfo($this->file_path, PATHINFO_FILENAME);
        $this->image_metadata = $metadata;
        $this->image_id = $attachment_id;
    }

    /**
     * @param $attachment_id
     * @return bool
     */

    public function checkFileExists($attachment_id)
    {

        $as3cf_item = Media_Library_Item::get_by_source_id($attachment_id);

        if (!empty($as3cf_item) && $as3cf_item->is_verified()) {
            return true;
        }

        $file = get_attached_file($attachment_id);

        if (!file_exists($file)) {
            $message = 'The uploaded file does not exist on the server. Encoding not possible.';
            Debug::debug('The uploaded file,' . $file . ' does exist on the server. Encoding not possible.', 1);
            return false;
        }

        return true;
    }

    /**
     * @param $attachment_id
     * @return bool
     */

    public function checkFileType($attachment_id)
    {
        $file = get_attached_file($attachment_id);

        if ($this->file_ext === 'webp') {
            $message = 'This file is already webp format.';
            Debug::debug('This file,' . $file . ' is already converted to webp.', 1);
            return;
        }
    }

    /**
     * @param $metadata
     * @return array
     */

    public function createArrayOfSizesToBeConverted($metadata)
    {
        array_push($this->array_of_sizes_to_be_converted, $this->file_path);

        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $value) {
                if (in_array($value->mime_type, self::$allowed_mime_type)) {
                    array_push($this->array_of_sizes_to_be_converted, $this->file_dirname . '/' . $value['file']);
                }
            }
        } else {
            array_push($this->image_to_regenerate, $this->file_path);
        }
    }

    /**
     * @param $metadata
     * @return array
     */

    public function convertArrayOfSizes()
    {
        switch ($this->file_ext) {

            case 'jpeg':
            case 'jpg':

                foreach ($this->array_of_sizes_to_be_converted as $key => $value) {
                    $image = imagecreatefromjpeg($value);
                    if (0 === $key) {
                        $webp_file = $this->file_dirname . '/' . $this->file_name_no_ext . '.webp';
                        imagewebp($image, $webp_file, 80);
                        wp_update_attachment_metadata($this->image_id, '_wp_attached_file', wp_generate_attachment_metadata($this->image_id, $webp_file));
                        Debug::debug($this->file_name_no_ext . ' has been converted. This jpg is now webp!', 1);
                    } else {

                        $current_size = getimagesize($value);
                        $webp_file = $this->file_dirname . '/' . $this->file_name_no_ext . '-' . $current_size[0] . 'x' . $current_size[1] . '.webp';
                        imagewebp($image, $webp_file, 80);
                        wp_update_attachment_metadata($this->image_id, '_wp_attached_file', wp_generate_attachment_metadata($this->image_id, $webp_file));
                        Debug::debug($this->file_name_no_ext . ' has been converted. This jpg is now webp!', 1);
                    }
                }
                break;

            case 'png':
                foreach ($this->array_of_sizes_to_be_converted as $key => $value) {
                    $image = imagecreatefrompng($value);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);

                    if (0 === $key) {
                        $webp_file = $this->file_dirname . '/' . $this->file_name_no_ext . '.webp';
                        imagewebp($image, $webp_file, 80);
                        wp_update_attachment_metadata($this->image_id, '_wp_attached_file', wp_update_attachment_metadata($this->image_id, [$webp_file]));
                        Debug::debug($this->file_name_no_ext . ' has been converted. This png is now webp!', 1);
                    } else {

                        $current_size = getimagesize($value);
                        $webp_file = $this->file_dirname . '/' . $this->file_name_no_ext . '-' . $current_size[0] . 'x' . $current_size[1] . '.webp';
                        imagewebp($image, $webp_file, 80);
                        wp_update_attachment_metadata($this->image_id, '_wp_attached_file', wp_update_attachment_metadata($this->image_id, $webp_file));
                        Debug::debug($this->file_name_no_ext . ' has been converted. This png is now webp!', 1);
                    }
                }
                break;

            default:
                return false;
        }
    }
}
