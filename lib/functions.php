<?php

namespace AssetPipeline;

use \extension_Asset_Pipeline as Driver;

/*
function find_precompiled_file($file)
{
    $info_file = PRECOMPILED_FILES . '/'. $file . '.txt';
    if (is_file($info_file)) {
        $info = unserialize(file_get_contents($info_file));
        $target_file = $info['target_file'];
        if (is_file(OUTPUT_DIR . '/' . $target_file)) {
                return $target_file;
        }
    }
    return false;
}
*/
function find_precompiled_file($file)
{

}

function filename_insert_md5($filename, $md5)
{
	$last_dot = strrpos($filename, '.');
	return $last_dot ?
		substr($filename, 0, $last_dot) . '-' . $md5 . substr($filename, $last_dot) : false;
}

function create_production_code_file($filename, $content)
{
    #$is_code_file = ($content === null);
    $output_file_abs = ASSET_CACHE . '/' . $output_file;
    if ($code_file) {
        $md5 = md5($content);
    } else {
        $md5 = md5_file($source_file_abs);
    }
    $output_file = filename_insert_md5($filename, $md5);
    General::realiseDirectory(dirname($output_file_abs));
    if ($code_file) {
        General::writeFile($output_file_abs, $output);
    } else {
        symlink($source_file_abs, $output_file_abs);
    }
}

function get_config_object()
{
    if (!is_file(CONFIG . '/config.xml')) {
        return false;
    }

    try {
        $object = simplexml_load_file(CONFIG . '/config.xml');
        //echo var_dump($object); die;
    } catch (Error $e) {
        return false;
    } catch (Exception $e) {
        return false;
    }
    return $object;
}

function exec_command($args)
{
    if (in_array('proc_open', explode(',', ini_get('disable_functions')))) {
        return false;
    }

    $num_args = count($args);
    if ($num_args < 2) {
        return false;
    }

    $line = trim($args[0]);
    for ($i = 1; $i < $num_args; $i++) {
        $line .= ' ' . escapeshellarg($args[$i]);
    }
    $pipes = null;
    $process = proc_open(
        $line,
        array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        ),
        $pipes, __DIR__
    );
    if ($error = stream_get_contents($pipes[2])) {
        $output = array('error' => $error);
    } else {
        $output = array('content' => stream_get_contents($pipes[1]));
    }

    foreach($pipes as $pipe) {
        fclose($pipe);
    }
    proc_close($process);

    return $output;
}

function url($file)
{
    $file_out = Driver::findPrecompiledFile($file);
    if (is_file(OUTPUT_DIR . '/' . $file_out)) {
        return OUTPUT_URL . '/' . $file_out;
    } else {
        return file_get_contents('ap.url-for://' . $file);
    }
}


function css_url($file)
{
    //$file_out = find_precompiled_file($file . '.css');
    $file_out = Driver::findPrecompiledFile($file . '.css');
    if (is_file(OUTPUT_DIR . '/' . $file_out)) {
        return OUTPUT_URL . '/' . $file_out;
    } else {
        #return file_get_contents('am.data://css-url/' . $file);
        return file_get_contents('ap.url-for://' . $file . '?output_type=css');
    }
}

function js_url($file)
{
    //$file_out = find_precompiled_file($file . '.js');
    $file_out = Driver::findPrecompiledFile($file . '.js');
    if (is_file(OUTPUT_DIR . '/' . $file_out)) {
        return OUTPUT_URL . '/' . $file_out;
    } else {
        return file_get_contents('ap.url-for://' . $file . '?output_type=js');
    }
}

function base64_data($file)
{
    return file_get_contents('ap://' . $file);
}

function prepare_asset($file)
{
    $file_out = find_precompiled_file($file);
    if (is_file(OUTPUT_DIR . '/' . $file_out)) {
        return OUTPUT_URL . '/' . $file_out;
    }

    $path_info = pathinfo($file);
    switch ($path_info['extension']) {
        case 'css':
            $stream_path = 'ap://css-url/' . $path_info['filename'];
            break;
        case 'js':
            $stream_path = 'ap://js-url/' . $path_info['filename'];
            break;
        default:
            $stream_path = 'ap://url' . $file;
            break;
    }
    return file_get_contents($stream_path);
}

function clear_directory($dir, $silent = true)
{
    try {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!clear_directory($dir . '/' . $item, $silent)) {
                return false;
            }
        }

        return true;
    } catch (Exception $ex) {
        if ($silent === false) {
            throw new Exception(__('Unable to remove - %s', array($dir)));
        }

        return false;
    }
}
