<?php
/*
namespace asset_pipeline\ap;

use Symphony;

function ns_define($name, $value)
{
    define(__NAMESPACE__.'\\'.$name, $value);
}

function getSetting($name, $group = null)
{
    $group = $group ? $group : ID;
    return Symphony::Configuration()->get($name, $group);
}

ns_define('ID', 'asset_pipeline');

ns_define('EXTENSION', EXTENSIONS . '/' . ID);

ns_define('INSTALLATION_COMPLETE', (bool)Symphony::Configuration()->get(ID));
*/

// APP_MODE is a Symphony 3 constant.
define_safe('APP_MODE', isset($_GET['mode']) ? $_GET['mode'] : 'frontend');

// Load extension driver for frontend or administration.
require EXTENSIONS . '/asset_pipeline/drivers/driver.' . APP_MODE . '.php';
