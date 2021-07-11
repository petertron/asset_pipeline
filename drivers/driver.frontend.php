<?php

require EXTENSIONS . '/asset_pipeline/lib/stream/stream-setup.php';

use AssetPipeline as AP;

class extension_Asset_pipeline extends Extension
{
    private static $precompiled_files = array();

    public function outputPreGenerate($context)
    {
        if (is_file(AP\PRECOMPILED_FILES)) {
            self::$precompiled_files = include(AP\PRECOMPILED_FILES);
        }

        $asset_functions = array(
            array(
                'name' => 'url',
                'php_function' => 'AssetPipeline\url',
                'params' => array('file')
            ),
            array(
                'name' => 'css-url',
                'php_function' => 'AssetPipeline\css_url',
                'params' => array('file')
            ),
            array(
                'name' => 'js-url',
                'php_function' => 'AssetPipeline\js_url',
                'params' => array('file')
            ),
            array(
                'name' => 'data',
                'php_function' => 'AssetPipeline\base64_data',
                'params' => array('file')
            )
        );

        Symphony::ExtensionManager()->notifyMembers(
            'RegisterAssetFunctions', '/extension/asset_pipeline/', array('functions' => &$asset_functions)
        );

        $page = $context['page'];
        $php_functions = array_column($asset_functions, 'php_function');
        if (method_exists($page, 'registerPHPFunction')) {
            $page->registerPHPFunction($php_functions); // Symphony 2
        } else {
            $page->Proc->registerPHPFunction($php_functions); // Symphony 3
        }

        $doc = new DOMDocument();
        $doc->loadXML($context['xsl']);
        //$doc->formatOutput = TRUE;
        $doc->createAttributeNS('http://exslt.org/functions', 'func:_');
        $doc->createAttributeNS('http://php.net/xsl', 'php:_');
        $doc->createAttributeNS('http://git.io/sym-asset-pipeline', 'ap:_');
        $stylesheet = $doc->firstChild;
        $stylesheet->setAttribute('extension-element-prefixes', 'func');
        #$stylesheet->setAttribute('exclude-result-prefixes', 'func');

        foreach ($asset_functions as $item) {
            $func_function = $doc->createElement('func:function');
            $func_function->setAttribute('name', 'ap:' . $item['name']);
            $select = "php:functionString('{$item['php_function']}'";

            foreach ($item['params'] as $name) {
                $param = $doc->createElement('xsl:param');
                $param->setAttribute('name', $name);
                $func_function->appendChild($param);
                $select .= ",\$$name";
            }
            $select .= ")";
            $func_result = $doc->createElement('func:result');
            $func_result->setAttribute('select', $select);
            $func_function->appendChild($func_result);
            $stylesheet->appendChild($func_function);
        }
        $context['xsl'] = $doc->saveXML();

        AP\clear_directory(AP\ASSET_CACHE);
        //echo htmlspecialchars($context['xsl']);die;
    }

    public static function findPrecompiledFile($file)
    {
        return isset(self::$precompiled_files[$file]) ? self::$precompiled_files[$file] : null;
    }
}
