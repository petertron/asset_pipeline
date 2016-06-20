<?php

// Driver for pipeline output.

class extension_Asset_Pipeline extends Extension
{
    public function modifyLauncher()
    {
        define('SYMPHONY_LAUNCHER', 'renderer_serve_asset');

        function renderer_serve_asset()
        {
            $file = trim(getCurrentPage(), '/');
            $output_path_abs = MANIFEST . '/asset_pipeline/cache/' . $file;
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
            header('Content-type: ' . $mimetype[$ext]);
            readfile($output_path_abs);
            exit;
        }
    }
}

/*
        $ext = General::getExtension($file);
        if ($ext == 'css' || $ext == 'js') {
            $output_path_abs = MANIFEST . '/asset_pipeline/cache/' . $file;
        } else {
            $output_path_abs = AP::getSourceDir() . '/' . $file;
            $output_path_abs = AP::getSourceDir() . '/' . $file;
        }
*/