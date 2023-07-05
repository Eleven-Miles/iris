# Eleven Miles: Iris

## Requirements

This package can be used with WordPress 5.8 and forward, due WebP support being introduced. This package enables servers to directly process and convert JPEG or PNG files on upload.

This package is installed using Composer.

## Setup

Run `composer require eleven-miles/iris` to install.

### Debugging

For debugging: `Debug::debug($image_meta);`

## Action Scheduler

Iris uses [Action Scheduler](https://actionscheduler.org/usage/) to run webp convertion at scheduled intervals. This functionality helps when converting media libraies, and when images are uploaded in bulk. The GD image processing can become hit memory issues when bulk uploading images and return failed conversion. Action Scheduler helps to ensure that all images are converted by queuing up the conversions.
