<?php

// Driver for frontend.

require_once EXTENSIONS . '/asset_pipeline/lib/ap.php';

//require_once EXTENSIONS . '/asset_pipeline/lib/prepro.php';

use asset_pipeline\AP;

class extension_Asset_Pipeline extends Extension
{
/*
    public function __construct()
    {
        parent::__construct();
        $this->settings = (object)Symphony::Configuration()->get(ap::ID);
    }
*/

    /*
     * Before outputting XSLT page.
     */
    public function outputPreGenerate($context)
    {
        $page = $context['page'];
        AP::initialise();
        AP::registerPlugins();
        $page->registerPHPFunction(array('asset_pipeline\AP::prepareAsset'));
        $doc = new DOMDocument();
        $doc->loadXML($context['xsl']);
        //$doc->formatOutput = TRUE;
        $doc->createAttributeNS('http://exslt.org/functions', 'func:function');
        $doc->createAttributeNS('http://Petertron.github.io/asset_pipeline', 'ap:url-for');
        $doc->createAttributeNS('http://php.net/xsl', 'php:functionString');
        $stylesheet = $doc->firstChild;
        $stylesheet->setAttribute('extension-element-prefixes', 'func');
        $stylesheet->setAttribute('exclude-result-prefixes', 'func ap');

        $func_function = $doc->createElement('func:function');
        $func_function->setAttribute('name', 'ap:url-for');
        $doc_param = $doc->createElement('xsl:param');
        $doc_param->setAttribute('name', 'file');
        $func_function->appendChild($doc_param);
        $func_result = $doc->createElement('func:result');
        $select = "php:functionString('asset_pipeline\AP::prepareAsset', \$file)";
        $func_result->setAttribute('select', $select);
        $func_function->appendChild($func_result);
        $stylesheet->appendChild($func_function);

        $context['xsl'] = $doc->saveXML();
        //echo htmlspecialchars($context['xsl']);die;
    }
}