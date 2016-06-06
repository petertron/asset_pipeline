<?php

namespace asset_pipeline\ap;

use Symphony;

function ns_define($name, $value)
{
    define(__NAMESPACE__.'\\'.$name, $value);
}

ns_define('ID', 'asset_pipeline');

//ns_define('EXTENSION', EXTENSIONS . '/' . ID);

ns_define('SOURCE_DIR', WORKSPACE . '/' . trim(getSetting('source_directory'), '/'));

$output_dir = trim(getSetting('output_directory'), '/');
if (trim(getSetting('output_parent_directory'), '/') == '') {
    ns_define('OUTPUT_DIR', DOCROOT . '/' . $output_dir);
    ns_define('OUTPUT_URL', '/' . $output_dir);
} else {
    ns_define('OUTPUT_DIR', WORKSPACE . '/' . $output_dir);
    ns_define('OUTPUT_URL', '/workspace/' . $output_dir);
}

//ns_define('SELF_MANIFEST', MANIFEST . '/' . ID);
ns_define('CACHE', MANIFEST . '/' . ID . '/cache');
ns_define('FILES_PRECOMPILED', MANIFEST . '/' . ID . '/files-precompiled');

ns_define('COMPILATION_LIST', ID . '_compilation_list');

//$region = Symphony::Configuration()->get('region');
//ns_define('DATE_FORMAT', "{$region['date_format']} {$region['time_format']}");
ns_define(
    'DATE_FORMAT',
    getSetting('date_format', 'region') . ' ' . getSetting('time_format', 'region')
);

// Functions

function getSetting($name, $group = null)
{
    $group = $group ? $group : ID;
    return Symphony::Configuration()->get($name, $group);
}

function getSourceFiles()
{
    return getRecursiveDirListing(SOURCE_DIR);
}

function getCompilationList()
{
    $comp_list = Symphony::Configuration()->get(COMPILATION_LIST);
    if (is_array($comp_list) && !empty($comp_list)) {
        return $comp_list;
    } else {
        return null;
    }
}

function saveCompilationList($compilation_list)
{
    Symphony::Configuration()->set(COMPILATION_LIST, $compilation_list);
    Symphony::Configuration()->write();
}

function getFilesPrecompiled()
{
    $files_compiled = null;
    if (is_file(FILES_PRECOMPILED)) {
        try {
            $files_compiled = unserialize(file_get_contents(FILES_PRECOMPILED));
        } catch (Exception $e) {}
    }
    return $files_compiled;
}

function getFileMTime($path)
{
    return is_file($path) ? date(DATE_FORMAT, filemtime($path)) : false;
}

function source_file_exists($subpath)
{
    return is_file(file_path_join(SOURCE_DIR, $subpath));
}

function getSourceFileDate($file)
{
    $path_abs = SOURCE_DIR . '/' . $file;
    return is_file($path_abs) ? date(DATE_FORMAT, filemtime($path_abs)) : false;
}

function getRecursiveDirListing($dir)
{
    $files = array();
    $base_path_length = strlen($dir) + 1;
    return scanDirRecursive($dir, $base_path_length);
}

function scanDirRecursive($dir, $base_path_length)
{
    $files = array();
    foreach (scandir($dir) as $file) {
        if ($file[0] == '.') continue;
        if (is_dir("$dir/$file")) {
            $files = array_merge($files, scanDirRecursive("$dir/$file", $base_path_length));
        } else {
            $files[] = substr("$dir/$file", $base_path_length);
        }
    }
    return $files;
}

function file_path_join($base_dir, $subdir)
{
    return $base_dir . '/' . trim($subdir, '/');
}

function dissembleSourcePath($path)
{
    $slash_pos = strpos($path, '/');
    if ($slash_pos == -1) return $path;
    return array(substr($path, 0, $slash_pos), substr($path, $slash_pos + 1));
}

function shortenFilePath($source_path)
{
    $split = explode('/', $source_path);
    if (count($split) > 1) {
        array_shift($split); // discard first subdir
    }
   return implode('/', $split);
}

function filenameInsertMD5($file, $md5)
{
    $last_dot = strrpos($file, '.');
    return $last_dot ? substr($file, 0, $last_dot) . "-$md5" . substr($file, $last_dot) : false;
}

function xmlArrayToHtml($items)
{
    $html = '';
    foreach ($items as $item) {
        $html .= $item->generate();
    }
    return $html;
}