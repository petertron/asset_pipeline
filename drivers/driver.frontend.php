<?php

require_once EXTENSIONS . '/asset_pipeline/lib/ap.php';

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
        //$starting = '/workspace/assets/';
        $starting = ap\OUTPUT_URL . '/';

        if (substr($sym_page, 0, strlen($starting)) == $starting) {
            $path = trim(substr($sym_page, strlen($starting)), '/');
            $ext = General::getExtension($path);

            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache");
            if ($ext == 'css') {
                header("Content-type:text/css");
                readfile(ap\CACHE . '/' . $path);
                exit;
            } elseif ($ext == 'js') {
                header("Content-type:text/javascript");
                readfile(ap\CACHE . '/' . $path);
                exit;
            } elseif (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webm'))) {
                header("Content-type:image/$ext");
                readfile (ap\SOURCE_DIR . '/' . $path);
                exit;
            }
        }
    }

    /*
     * Before outputting XSLT page.
     */
    public function outputPreGenerate($context)
    {
        $page = $context['page'];
        $page->registerPHPFunction(array('asset_pipeline\ap\AP::prepareAsset'));

        Symphony::ExtensionManager()->notifyMembers('RegisterPlugins', '/extension/asset_pipeline/');

        $doc = new DOMDocument();
        $doc->loadXML($context['xsl']);
        //$doc->formatOutput = TRUE;
        $doc->createAttributeNS('http://exslt.org/functions', 'func:function');
        $doc->createAttributeNS('http://Petertron.github.io/asset_pipeline', 'sym-ap:x');
        $doc->createAttributeNS('http://php.net/xsl', 'php:functionString');
        $doc->firstChild->setAttribute('extension-element-prefixes', 'func');
        $doc->firstChild->setAttribute('exclude-result-prefixes', 'func sym-ap');

        $func_function = $doc->createElement('func:function');
        $func_function->setAttribute('name', 'sym-ap:url-for');
        $doc_param = $doc->createElement('xsl:param');
        $doc_param->setAttribute('name', 'file');
        $func_function->appendChild($doc_param);
        $func_result = $doc->createElement('func:result');
        $select = "php:functionString('asset_pipeline\ap\AP::prepareAsset', \$file)";
        $func_result->setAttribute('select', $select);
        $func_function->appendChild($func_result);
        $doc->firstChild->appendChild($func_function);
        $context['xsl'] = $doc->saveXML();
    }

}