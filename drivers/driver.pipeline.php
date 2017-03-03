<?php

// Driver for pipeline output.

use AssetPipeline as AP;

class Extension_Asset_pipeline extends Extension
{
    public function modifyLauncher()
    {
        define('SYMPHONY_LAUNCHER', 'asset_pipeline_serve_asset');

        function asset_pipeline_serve_asset()
        {
            $file = trim(getCurrentPage(), '/');
            $output_path_abs = AP\ASSET_CACHE . '/' . $file;
            $mimetypes = array(
                'txt'   => 'text/plain',
                'css'   => 'text/css',
                'csv'   => 'text/csv',
                'js'    => 'text/javascript',
                'pdf'   => 'application/pdf',
                'doc'   => 'application/msword',
                'docx'  => 'application/msword',
                'xls'   => 'application/vnd.ms-excel',
                'ppt'   => 'application/vnd.ms-powerpoint',
                'eps'   => 'application/postscript',
                'swf'   => 'application/x-shockwave-flash',
                'zip'   => 'application/zip',
                'bmp'   => 'image/bmp',
                'gif'   => 'image/gif',
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'png'   => 'image/png',
                'mp3'   => 'audio/mpeg',
                'mp4a'  => 'audio/mp4',
                'aac'   => 'audio/x-aac',
                'aif'   => 'audio/x-aiff',
                'aiff'  => 'audio/x-aiff',
                'wav'   => 'audio/x-wav',
                'wma'   => 'audio/x-ms-wma',
                'mpeg'  => 'video/mpeg',
                'mpg'   => 'video/mpeg',
                'mp4'   => 'video/mp4',
                'mov'   => 'video/quicktime',
                'avi'   => 'video/x-msvideo',
                'wmv'   => 'video/x-ms-wmv',
            );

            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Cache-Control: no-cache');
            header('Content-type: ' . $mimetypes[General::getExtension($file)]);
            readfile($output_path_abs);
            exit;
        }
    }
}
