<?php

namespace asset_pipeline;

use Symphony;

function define_here($name, $value)
{
    define(__NAMESPACE__.'\\'.$name, $value);
}

define_here('ID', 'asset_pipeline');

define_here('EXTENSION', EXTENSIONS . '/' . ID);

define_here('INSTALLATION_COMPLETE', (bool)Symphony::Configuration()->get(ID));

define_here('COMPILATION_LIST', ID . '_compilation_list');

//define_here('FILES_PRECOMPILED', MANIFEST . '/' . ID . '/files-precompiled');
define_here('TBL_FILES_PRECOMPILED', 'tbl_' . ID . '_files_precompiled');

define_here('CACHE', MANIFEST . '/' . ID . '/cache');

trait GlobalDefs
{
    public function getID()
    {
        return 'asset_pipeline';
    }
}

trait DriverDefs
{
    function getInstallationComplete()
    {
        return (bool)Symphony::Configuration()->get(ID);
    }


}