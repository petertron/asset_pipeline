<?php

namespace AssetPipeline;

use Symphony;

class Stream
{
    protected static $preprocessors = array();
    protected $glob_exp;
    protected $params = array();
    protected $calling_file;
    private $position = 0;
    private $path = null;
    protected $output = null;

    static function registerPreprocessors()
    {
        Symphony::ExtensionManager()->notifyMembers(
            'RegisterPreprocessors',
            '/extension/asset_pipeline/',
            array('preprocessors' => &self::$preprocessors)
        );
    }

    static function getSourceFileRelativePath($path_abs)
    {
        $path_rel = substr($path_abs, strlen(SOURCE_FILES) + 1);
        //return substr($path_rel, strpos($path_rel, DIRECTORY_SEPARATOR) + 1);
        return substr($path_rel, strpos($path_rel, '/') + 1);
    }

    static function renderErrorTemplate($source_file_abs, $message)
    {
        $template = file_get_contents(
            EXTENSIONS . '/asset_pipeline/content/preprocessor_error.tpl'
        );
        echo sprintf($template, \ASSETS_URL, basename($source_file_abs), $source_file_abs, $message);
        exit;
    }

    function __construct()
    {
        $backtrace = debug_backtrace(
            defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? DEBUG_BACKTRACE_IGNORE_ARGS : false
        );
        $calling_file = $backtrace[1]['file'];
        if (substr($calling_file, 0, strlen(SOURCE_FILES)) == SOURCE_FILES) {
            $this->calling_file = $calling_file;
        }
    }

    final function stream_set_option($option, $arg1 = null, $arg2 = null)
    {
        #echo $option . ', ' . $arg1 . ', ' . $arg2; die;
        return false;
    }

    final function stream_open($path)//, $mode, $options, &$opened_path)
    {
        $path_parts = null;
        preg_match('/^ap(.[^:]+)?:\/\/(\/*)([^\?]*)\??(.*)$/', $path, $path_parts);
        $this->action = substr($path_parts[1], 1);
        $this->relative_path = ($path_parts[2] == '/') ? false : true;
        if ($this->relative_path && $this->calling_file) {
            $this->glob_exp = dirname($this->calling_file) . '/' . $path_parts[3];
        } else {
            $this->glob_exp = SOURCE_FILES . '/*/' . $path_parts[3];
        }
        if ($path_parts[4]) {
            parse_str($path_parts[4], $this->params);
        }
        //$this->path = $path_parts[2];
        if (method_exists($this, 'setOutput')) {
            $this->setOutput();
        }
        return true;
    }

    final function stream_read($count)
    {
       $output = substr($this->output, intval($this->position), $count);
       $this->position += $count;
       return $output;
    }

    final function stream_write($data)
    {
        return $data;
    }

    final function url_stat()
    {
        return array();
    }

    final function stream_stat()
    {
        return array();
    }

    final function stream_eof()
    {
        return true;
    }

    function locateSourceFile($glob_exp)
    {
        $glob = glob($glob_exp, GLOB_BRACE);
        if (empty($glob)) {
            echo "No file found: $glob_exp"; die;
        }
        return (!empty($glob)) ? $glob[0] : null;
    }
}

Stream::registerPreprocessors();
