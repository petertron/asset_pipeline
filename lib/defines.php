<?php

namespace asset_pipeline\AP;

use Symphony;

function define_here($name, $value)
{
    define(__NAMESPACE__.'\\'.$name, $value);
}

define_here('ID', 'asset_pipeline');
//define_here('SOURCE_DIRECTORIES', ID . '_source_directories');

$self_manifest = MANIFEST . '/' . ID;
define_here('SOURCE_DIRECTORIES', $self_manifest . '/source-directories.php');
define_here('CACHE', $self_manifest . '/cache');
define_here('FILES_PRECOMPILED', $self_manifest .'/files-precompiled.php');

$settings = Symphony::Configuration()->get(ID);
define_here('INSTALLATION_COMPLETE', (bool)$settings);

if (INSTALLATION_COMPLETE) {
    $output_dir = trim($settings['output_directory'], '/');
    $use_docroot = ($settings['output_directory_base'] == 'docroot');
    define_here('OUTPUT_DIR', ($use_docroot ? DOCROOT : WORKSPACE) . '/' . $output_dir);
    define_here('OUTPUT_URL', ($use_docroot ? '/' : '/workspace/') . $output_dir);
}
