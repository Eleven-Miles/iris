# Eleven Miles: Iris

## Requirements

This package can be used with WordPress 5.8 and forward, due to WebP support being introduced. This package enables servers to directly process and convert JPEG or PNG files on upload as well as bulk and single image conversions via WP CLI.

## Setup
This package is installed using Composer.

Run `composer require eleven-miles/iris` to install.

## CLI Commands

### Bulk Conversion

Use `wp iris` to run a full media library conversion. This will run through all JPG and PNG attachments and convert them to webp. This process uses Action Scheduler to queue up each conversion and wait until each is complete to progress.

### Single Conversion

Use `wp iris-id $id` to run a single image conversion. This command takes an attachment ID as an argument and then runs a webp conversion on a the single image.

## Debugging

For debugging: `Debug::debug($image_meta);`

## Dependancies

Iris requires the two following plugins to be installed and activated.

### Action Scheduler

Iris uses [Action Scheduler](https://actionscheduler.org/usage/) to run webp convertion at scheduled intervals. This functionality helps when converting media libraies, and when images are uploaded in bulk. The GD image processing can become hit memory issues when bulk uploading images and return failed conversion. Action Scheduler helps to ensure that all images are converted by queuing up the conversions.

### Offload Media

Iris uses [Offload Media](https://deliciousbrains.com/wp-offload-media/) to store converted images in the cloud. During the Iris process the following Offload method - `Media_Library_Item::get_by_source_id` - is used to check if the attachement exists in the relevant storage system (eg AWS S3 bucket).