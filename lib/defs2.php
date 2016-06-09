<?php

namespace asset_pipeline\ap;

use Symphony;

//define_here('EXTENSION', EXTENSIONS . '/' . ID);

define_here('SOURCE_DIR', WORKSPACE . '/' . trim(getSetting('source_directory'), '/'));

$output_dir = trim(getSetting('output_directory'), '/');
if (trim_sl(getSetting('output_parent_directory')) == '') {
    define_here('OUTPUT_DIR', DOCROOT . '/' . $output_dir);
    define_here('OUTPUT_URL', '/' . $output_dir);
} else {
    define_here('OUTPUT_DIR', WORKSPACE . '/' . $output_dir);
    define_here('OUTPUT_URL', '/workspace/' . $output_dir);
}

//$region = Symphony::Configuration()->get('region');
//define_here('DATE_FORMAT', "{$region['date_format']} {$region['time_format']}");
define_here(
    'DATE_FORMAT',
    getSetting('date_format', 'region') . ' ' . getSetting('time_format', 'region')
);

$source_subdirs = array();
foreach (scandir(SOURCE_DIR) as $file) {
    if ($file[0] == '.') continue;
    if (is_dir(SOURCE_DIR . '/' . $file)) {
        $source_subdirs[] = trim_sl($file);
    }
}
define_here('SOURCE_SUBDIRS', implode('/', $source_subdirs));

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

function getSourceSubdirs()
{
    return explode('/', SOURCE_SUBDIRS);
}

/*function getCompilationList()
{
    $comp_list = Symphony::Configuration()->get(COMPILATION_LIST);
    if (is_array($comp_list) && !empty($comp_list)) {
        return $comp_list;
    } else {
        return null;
    }
}*/

function getCompilationList()
{
    $compilation_list = Symphony::Configuration()->get(COMPILATION_LIST);
    if (is_array($compilation_list) && !empty($compilation_list)) {
        $compilation_list_a = array();
        foreach ($compilation_list as $file => $subdir) {
            $file = trim_sl($file);
            if (!$file) continue;
            $subdir = trim_sl($subdir);
            if (!$subdir) continue;
            $compilation_list_a[$file] = $subdir;
        }
        ksort($compilation_list_a);
        return $compilation_list_a;
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

function trim_sl($value)
{
    return trim($value, '/');
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
