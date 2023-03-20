<?php

namespace ElevenMiles\Iris\IrisConvert;

use ElevenMiles\Iris\Debug;
use WP_CLI;

class IrisConvert
{

    private $file_path;
    private $file_dirname;
    private $file_ext;
    private $file_name_no_ext;
    private $image_metadata;
    private $image_id;
    private $progress;
    private $array_of_sizes_to_be_converted = [];
    private $array_of_sizes_to_be_deleted   = [];
    private static $allowed_mime_type = ['image/jpeg', 'image/jpg', 'image/png'];

    public function __construct($attachment_id, $metadata, $progress)
    {
        $this->file_path = get_attached_file($attachment_id);
        $this->file_dirname = pathinfo($this->file_path, PATHINFO_DIRNAME);
        $this->file_ext = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
        $this->file_name_no_ext = pathinfo($this->file_path, PATHINFO_FILENAME);
        $this->image_metadata = $metadata;
        $this->image_id = $attachment_id;
        $this->progress = $progress;
        add_action('cli_init', [__CLASS__, 'generateCLI']);
    }

    public function check_file_exists($attachment_id)
    {
        $file = get_attached_file($attachment_id);

        if (!file_exists($file)) {
            return;
            $message = 'The uploaded file does not exist on the server. Encoding not possible.';
            Debug::debug('The uploaded file,' . $file . ' does exist on the server. Encoding not possible.', 1);
        }
    }

    public function create_array_of_sizes_to_be_converted($metadata)
    {
        // // check if meta data image sizes is empty, if it is then run a wp thumbnail regenerate then continue
        if (isset($metadata['sizes']) && empty($metadata['sizes'])) {
            $this->regenerate_to_convert_array_of_images($metadata);
        }

        // push original file to the array
        array_push($this->array_of_sizes_to_be_converted, $this->file_path);

        // push all created sizes of the file to the array
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $value) {
                if (in_array($value->mime_type, self::$allowed_mime_type)) {
                    array_push($this->array_of_sizes_to_be_converted, $this->file_dirname . '/' . $value['file']);
                }
            }
        }
    }



    public function regenerate_to_convert_array_of_images($metadata)
    {
        $attachment_id = $this->image_id;
        $options = [
            'launch'     => false,
            'exit_error' => true,
            'return'     => false,
            'parse'      => false,
        ];

        array_push($this->array_of_sizes_to_be_converted, $this->file_path);

        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $value) {
                WP_CLI::runcommand("media regenerate $value->ID", $options);
                array_push($this->array_of_sizes_to_be_converted, $this->file_dirname . '/' . $value['file']);
                $this->progress->tick();
            }
        }
    }


    public function convert_array_of_sizes()
    {
        switch ($this->file_ext) {
            case 'webp':
                break;
            case 'jpeg':
            case 'jpg':

                foreach ($this->array_of_sizes_to_be_converted as $key => $value) {
                    $image = imagecreatefromjpeg($value);
                    if (0 === $key) {

                        $webp_file = $this->file_dirname . '/' . $this->file_name_no_ext . '.webp';
                        imagewebp($image, $webp_file, 80);
                        update_post_meta($this->image_id, '_wp_attached_file', wp_generate_attachment_metadata($this->image_id, $webp_file));
                        $this->progress->tick();
                    } else {

                        $current_size = getimagesize($value);
                        $webp_file = $this->file_dirname . '/' . $this->file_name_no_ext . '-' . $current_size[0] . 'x' . $current_size[1] . '.webp';
                        imagewebp($image, $webp_file, 80);
                        update_post_meta($this->image_id, '_wp_attached_file', wp_generate_attachment_metadata($this->image_id, $webp_file));
                        $this->progress->tick();
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
                        update_post_meta($this->image_id, '_wp_attached_file', wp_update_attachment_metadata($this->image_id, $webp_file));
                        $this->progress->tick();
                    } else {

                        $current_size = getimagesize($value);
                        $webp_file = $this->file_dirname . '/' . $this->file_name_no_ext . '-' . $current_size[0] . 'x' . $current_size[1] . '.webp';
                        imagewebp($image, $webp_file, 80);
                        update_post_meta($this->image_id, '_wp_attached_file', wp_update_attachment_metadata($this->image_id, $webp_file));
                        $this->progress->tick();
                    }
                }
                break;

            default:
                return false;
        }
    }

    public function create_array_of_sizes_to_be_deleted($attachment_id)
    {

        $this->attachment_metadata_of_file_to_be_deleted = wp_get_attachment_metadata($attachment_id);
        array_push($this->array_of_sizes_to_be_deleted, $this->file_dirname . '/' . $this->file_name_no_ext . '.webp');

        foreach ($this->attachment_metadata_of_file_to_be_deleted['sizes'] as $value) {

            $this->value_file_name_no_ext = pathinfo($value['file'], PATHINFO_FILENAME);

            array_push($this->array_of_sizes_to_be_deleted, $this->file_dirname . '/' . $this->value_file_name_no_ext . '.webp');
        }
    }

    public function delete_array_of_sizes()
    {
        foreach ($this->array_of_sizes_to_be_deleted as $key => $value) {
            unlink($value);
        }
    }
}
