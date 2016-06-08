<?php

require_once EXTENSIONS . '/asset_pipeline/lib/ap.php';
require_once EXTENSIONS . '/asset_pipeline/lib/defs1.php';
require_once EXTENSIONS . '/asset_pipeline/lib/defs2.php';

use asset_pipeline\ap;

class extension_Asset_Pipeline extends Extension
{
    //static $instance;

    //public $settings;

/*    public function __construct()
    {
        parent::__construct();
        $this->settings = (object)Symphony::Configuration()->get(ap\ID);
        self::$instance = $this;
    }
*/
    public function modifyLauncher()
    {
        $sym_page = getCurrentPage();
        $starting = ap\OUTPUT_URL . '/';

        if (substr($sym_page, 0, strlen($starting)) == $starting) {
            $file = trim(substr($sym_page, strlen($starting)), '/');
            $ext = General::getExtension($file);
            if ($ext == 'css' || $ext == 'js') {
                $output_path_abs = ap\CACHE . '/' . $file;
            } else {
                $output_path_abs = ap\SOURCE_DIR . '/' . $file;
            }
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
            /*
            if ($ext == 'css') {
                header("Content-type:text/css");
                readfile(ap\CACHE . '/' . $path);
                exit;
            } elseif ($ext == 'js') {
                header("Content-type:text/javascript");
                readfile(ap\CACHE . '/' . $path);
                exit;
            } elseif (in_array($ext, array('gif', 'jpeg', 'jpg', 'png', 'svg', 'webm'))) {
                header("Content-type:image/$ext");
                readfile (ap\SOURCE_DIR . '/' . $path);
                exit;
            }*/
        }
    }

    /*
     * Before outputting XSLT page.
     */
    public function outputPreGenerate($context)
    {
        $page = $context['page'];
        $page->registerPHPFunction(array('asset_pipeline\ap\prepareAsset'));
        ap\registerPlugins();
        $doc = new DOMDocument();
        $doc->loadXML($context['xsl']);
        //$doc->formatOutput = TRUE;
        $doc->createAttributeNS('http://exslt.org/functions', 'func:function');
        $doc->createAttributeNS('http://Petertron.github.io/asset_pipeline', 'sym-ap:x');
        $doc->createAttributeNS('http://php.net/xsl', 'php:functionString');
        $stylesheet = $doc->firstChild;
        $stylesheet->setAttribute('extension-element-prefixes', 'func');
        $stylesheet->setAttribute('exclude-result-prefixes', 'func sym-ap');

        $func_function = $doc->createElement('func:function');
        $func_function->setAttribute('name', 'sym-ap:url-for');
        $doc_param = $doc->createElement('xsl:param');
        $doc_param->setAttribute('name', 'file');
        $func_function->appendChild($doc_param);
        $func_result = $doc->createElement('func:result');
        $select = "php:functionString('asset_pipeline\ap\prepareAsset', \$file)";
        $func_result->setAttribute('select', $select);
        $func_function->appendChild($func_result);
        $stylesheet->appendChild($func_function);

        $context['xsl'] = $doc->saveXML();
    }
}