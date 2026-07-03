<?php

use App\Http\Middleware\ValidateUploadBytes;
use UniSharp\LaravelFilemanager\Handlers\ConfigHandler;

return [
    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */

    // Include to pre-defined routes from package or not. Middlewares
    'use_package_routes' => true,

    // Middlewares which should be applied to all package routes.
    // For laravel 5.1 and before, remove 'web' from the array.
    // ValidateUploadBytes byte-inspects every uploaded file (magic-byte MIME
    // sniff) before it reaches the upload controller, rejecting SVG and
    // mislabeled polyglots (FEATURE_MATRIX §21 / §7).
    'middlewares' => ['web', 'auth', ValidateUploadBytes::class],

    // The url to this package. Change it if necessary.
    'url_prefix' => 'filemanager',

    /*
    |--------------------------------------------------------------------------
    | Multi-User Mode
    |--------------------------------------------------------------------------
    */

    // If true, private folders will be created for each signed-in user.
    'allow_multi_user' => false,
    // If true, share folder will be created when allow_multi_user is true.
    'allow_share_folder' => false,

    // Flexible way to customize client folders accessibility
    // If you want to customize client folders, publish tag="lfm_handler"
    // Then you can rewrite userField function in App\Handler\ConfigHander class
    // And set 'user_field' to App\Handler\ConfigHander::class
    // Ex: The private folder of user will be named as the user id.
    'user_field' => ConfigHandler::class,

    /*
    |--------------------------------------------------------------------------
    | Storage driver (FEATURE_MATRIX §7 — swappable storage)
    |--------------------------------------------------------------------------
    |
    | Every media read/write routes through this filesystem disk (LFM resolves
    | it via Storage::disk(config('lfm.disk'))). This is the single storage
    | abstraction point: the default is the local-backed "public" disk, and
    | setting MEDIA_DISK=s3 (with the AWS_* env vars) swaps all media to S3 /
    | a CDN with no code change. The disk name must exist in
    | config/filesystems.php.
    |
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Working Directory
    |--------------------------------------------------------------------------
    */

    // Which folder to store files in project, fill in 'public', 'resources', 'storage' and so on.
    // You should create routes to serve images if it is not set to public.
    'base_directory' => 'public/uploads',

    'images_folder_name' => '',
    'files_folder_name' => '',

    'shared_folder_name' => 'images',
    'thumb_folder_name' => 'thumbs',

    /*
    |--------------------------------------------------------------------------
    | Startup Views
    |--------------------------------------------------------------------------
    */

    // The default display type for items.
    // Supported: "grid", "list"
    'images_startup_view' => 'grid',
    'files_startup_view' => 'list',

    /*
    |--------------------------------------------------------------------------
    | Upload / Validation
    |--------------------------------------------------------------------------
    */

    // If true, the uploaded file will be renamed to uniqid() + file extension.
    'rename_file' => true,

    // If rename_file set to false and this set to true, then non-alphanumeric characters in filename will be replaced.
    'alphanumeric_filename' => false,

    // If true, non-alphanumeric folder name will be rejected.
    'alphanumeric_directory' => false,

    // If true, the uploading file's size will be verified for over than max_image_size/max_file_size.
    'should_validate_size' => true,

    'max_image_size' => 50000,
    'max_file_size' => 50000,

    // If true, the uploading file's mime type will be valid in valid_image_mimetypes/valid_file_mimetypes.
    'should_validate_mime' => true,

    // available since v1.3.0
    // NOTE: image/svg+xml is intentionally omitted — SVG is an XSS/polyglot
    // vector and is rejected outright (see App\Support\SecureUpload and the
    // ValidateUploadBytes middleware, FEATURE_MATRIX §21).
    'valid_image_mimetypes' => [
        'image/jpeg',
        'image/pjpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ],

    // If true, image thumbnails would be created during upload
    'should_create_thumbnails' => true,

    // Create thumbnails automatically only for listed types.
    'raster_mimetypes' => [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
    ],

    // permissions to be set when create a new folder or when it creates automatically with thumbnails
    'create_folder_mode' => 0644,

    // permissions to be set on file upload.
    'create_file_mode' => 0644,

    // If true, it will attempt to chmod the file after upload
    'should_change_file_mode' => true,

    // available since v1.3.0
    // only when '/laravel-filemanager?type=Files'
    // SVG omitted on purpose — see the note on valid_image_mimetypes above.
    'valid_file_mimetypes' => [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image / Folder Setting
    |--------------------------------------------------------------------------
    */

    'thumb_img_width' => 200,
    'thumb_img_height' => 200,

    /*
    |--------------------------------------------------------------------------
    | File Extension Information
    |--------------------------------------------------------------------------
    */

    'file_type_array' => [
        'gif' => 'GIF Image',
        'jpg' => 'JPEG Image',
        'jpeg' => 'JPEG Image',
        'png' => 'PNG Image',
    ],

    'file_icon_array' => [
        'gif' => 'fa-file-image-o',
        'jpg' => 'fa-file-image-o',
        'jpeg' => 'fa-file-image-o',
        'png' => 'fa-file-image-o',
    ],

    /*
    |--------------------------------------------------------------------------
    | php.ini override
    |--------------------------------------------------------------------------
    |
    | These values override your php.ini settings before uploading files
    | Set these to false to ingnore and apply your php.ini settings
    |
    | Please note that the 'upload_max_filesize' & 'post_max_size'
    | directives are not supported.
    */
    'php_ini_overrides' => [
        'memory_limit' => '256M',
    ],

];
