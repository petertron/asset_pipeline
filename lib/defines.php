<?php

namespace AssetPipeline;

use Symphony;

function define_here($name, $value)
{
    define(__NAMESPACE__ . '\\' . $name, $value);
}

define_here('ID', 'asset_pipeline');
define_here('EXTENSION', \EXTENSIONS . '/' . ID);
define_here('URL', \URL . '/extensions/' . ID);
define_here('ASSETS_URL', URL . '/assets');
//define_here('SELF_WORKSPACE', \WORKSPACE . '/asset-pipeline');
//define_here('CONFIG', SELF_WORKSPACE . '/config');

$self_manifest = \MANIFEST . '/asset-pipeline';
define_here('ASSET_CACHE', $self_manifest . '/cache');
define_here('PRECOMPILED_FILES', $self_manifest .'/precompiled-files.php');
define_here('SYMLINKS', $self_manifest . '/symlinks');

$settings = Symphony::Configuration()->get(ID);

define_here('INSTALLATION_COMPLETE', (bool)$settings);
if (INSTALLATION_COMPLETE) {
    define_here('SOURCE_FILES', \WORKSPACE . '/' . trim($settings['source_directory'], '/'));
    //echo SOURCE_FILES; die;
    $output_dir = trim($settings['output_directory'], '/');
    $use_docroot = ($settings['output_parent_directory'] == 'docroot');
    define_here('OUTPUT_DIR', ($use_docroot ? \DOCROOT : \WORKSPACE) . '/' . $output_dir);
    //define_here('OUTPUT_URL', \URL . ($use_docroot ? '/' : '/workspace/') . $output_dir);
    define_here('OUTPUT_URL', ($use_docroot ? '/' : '/workspace/') . $output_dir);
}
