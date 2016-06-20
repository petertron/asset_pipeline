<?php

namespace asset_pipeline\def;

use Symphony;

function define_here($name, $value)
{
    define(__NAMESPACE__.'\\'.$name, $value);
}

define_here('ID', 'asset_pipeline');

//echo constant('asset_pipeline\def\ER'); die;
$settings = Symphony::Configuration()->get('asset_pipeline');

define_here('INSTALLATION_COMPLETE', (bool)$settings);

if (INSTALLATION_COMPLETE) {
    define_here('INPUT_DIRECTORIES', MANIFEST . '/asset_pipeline/input-drectories.php');
    define_here('FILES_PRECOMPILED', MANIFEST . '/asset_pipeline/files-compiled.php');

    $output_dir = trim($settings['output_directory'], '/');
    $use_docroot = ($settings['output_parent_directory'] == 'docroot');
    define_here('OUTPUT_DIR', $use_docroot ? DOCROOT : WORKSPACE) . '/' . $output_dir;
    define_here('OUTPUT_URL', $use_docroot ? '/' : '/workspace/') . $output_dir;
}
