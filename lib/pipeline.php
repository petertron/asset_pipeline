<?php

namespace asset_pipeline;

use asset_pipeline\AP;
use Symphony;
use General;

class Pipeline
{
    const ID = 'asset_pipeline';

    private static $initialised = false;

    private static $source_directories;
    private static $files_precompiled;
    private static $file_files_precompiled;
    private static $plugins = array();

    public static function initialise()
    {
        if (self::$initialised) return;

        self::$source_directories = is_file(AP\SOURCE_DIRECTORIES) ?
            include AP\SOURCE_DIRECTORIES : array();

        self::$files_precompiled = is_file(AP\FILES_PRECOMPILED) ?
            include AP\FILES_PRECOMPILED : array();

        self::registerPlugins();

        self::$initialised = true;
    }

    public static function registerPlugins()
    {
        Symphony::ExtensionManager()->notifyMembers(
            'RegisterPlugins', '/extension/asset_pipeline/', array('plugins' => &self::$plugins)
        );
    }

    public static function getFilesCompiled()
    {
        return self::$files_precompiled;
    }

    public static function getSetting($name)
    {
        return Symphony::Configuration()->get($name, self::ID);
    }

    public static function getOutputType($input_type)
    {
        if (array_key_exists($input_type, self::$plugins)) {
            return self::$plugins[$input_type]['output_type'];
        } else {
            return $input_type;
        }
    }

    public static function getDriver($input_type)
    {
        if (array_key_exists($input_type, self::$plugins)) {
            return self::$plugins[$input_type]['driver'];
        } else {
            return null;
        }
    }

    public static function isCodeType($file_ext)
    {
        return in_array($file_ext, array('css', 'js'));
    }

    public static function readFile($path_abs)
    {
        if (is_file($path_abs)) {
            return file_get_contents($path_abs);
        } else {
            return false;
        }
    }

    /*
     * Function called from XSLT.
     */
    public static function prepareAsset($file)
    {
        if (!AP\INSTALLATION_COMPLETE) return null;

        // If file exists in output directory, return URL for it.
        if (isset(self::$files_precompiled[$file])) {
            $file_out = self::$files_precompiled[$file];
        } else {
            $file_out = $file;
        }

        if (is_file(AP\OUTPUT_DIR . '/' . $file_out)) {
            return AP\OUTPUT_URL . '/' . $file_out;
        }

        // No file in output directory -- compile now.

        // Find file.
        $file_found = false;
        foreach (self::$source_directories as $dir_path => $values) {
            $source_file_abs = WORKSPACE . '/' . $dir_path . '/' . $file;
            if (is_file($source_file_abs)) {
                $file_found = true;
                break;
            }
        }
        if (!$file_found) return null;

        $input_type = General::getExtension($source_file_abs);
        $output_type = self::getOutputType($input_type);
        $output_file = ($output_type == $input_type) ?
            $file : self::replaceExtension($file, $output_type);
        $output_file_abs = AP\CACHE . '/' . $output_file;
        General::realiseDirectory(dirname($output_file_abs));
        if ($output_type == 'css') {
            $output = self::processCSS($source_file_abs);
            General::writeFile($output_file_abs, $output);
        } elseif ($output_type == 'js') {
            $output = self::processJS($source_file_abs);
            General::writeFile($output_file_abs, $output);
        } else {
            symlink($source_file_abs, $output_file_abs);
        }

        return "/$output_file/?mode=pipeline";
    }

    // Process CSS

    public static function processCSS($source_path_abs)
    {
        $output = '';
        $dir_path = dirname($source_path_abs);
        $content = file_get_contents($source_path_abs);
        $input_type = General::getExtension($source_path_abs);
        if ($input_type != 'css') {
            if (self::getOutputType($input_type) == 'css') {
                $driver = self::getDriver($input_type);
                $result = $driver->compile($content, $dir_path);
                if (APP_MODE == 'frontend' && isset($result['error'])) {
                    echo self::renderErrorTemplate(
                        basename($source_path_abs),
                        $source_path_abs,
                        $result['error']
                    );
                    exit;
                }

                $content = $result['output'];
            } else {
                return false; // Invalid input type
            }
        }
        if (substr($content, 0, 2) == '/*') {
            $header_end = strpos($content, '*/');
            if ($header_end !== -1) {
                $header = substr($content, 0, $header_end + 2);
                $content = trim(trim(substr($content, $header_end + 2)), "\n");

                $requires = array();
                $line = strtok($header, "\r\n");
                while ($line) {
                    $line = trim(ltrim(trim($line), '*'));
                    if (substr($line, 0, 1) == '=') {
                        $line = preg_split('/\s+/', ltrim(substr($line, 1)));
                        if ($line[0] == 'require') {
                            $requires[] = $line[1];
                        }
                    }
                    $line = strtok("\r\n");
                }
                if(!empty($requires)) {
                    foreach ($requires as $file) {
                        $output .= self::processCSS($dir_path . '/' . $file);
                    }
                }
            }
        }
        $output .= $content;
        return $output;
    }

    // Process JavaScript

    public static function processJS($source_path_abs)
    {
        $output = '';
        $dir_path = dirname($source_path_abs);
        $body = file_get_contents($source_path_abs);
        $input_type = General::getExtension($source_path_abs);
        if ($input_type != 'js') {
            if (self::getOutputType($input_type) == 'js') {
                $driver = self::getDriver($input_type);
                $result = $driver->compile($body);
                if (APP_MODE == 'frontend' && isset($result['error'])) {
                    echo self::renderErrorTemplate(
                        basename($source_path_abs),
                        $source_path_abs,
                        $result['error']
                    );
                    exit;
                }

                $body = $result['output'];
            } else {
                return false; // Invalid input type
            }
        }
        $requires = array();

        $line = strtok($body, "\r\n");
        while ($line) {
            $line = trim($line);
            if (substr($line, 0, 2) != '//') break;
            $line = trim(substr($line, 2));
            if (substr($line, 0, 1) == '=') {
                $line = preg_split('/\s+/', ltrim(substr($line, 1)));
                if ($line[0] == 'require') {
                    $requires[] = $line[1];
                }
            }
            $line = strtok("\r\n");
        }

        if(!empty($requires)) {
            $body = '';
            foreach ($requires as $file) {
                $output .= self::processJS($dir_path . '/' . $file);
            }
        }

        $output .= $body;
        return $output;
    }


    public static function filenameInsertMD5($file, $md5)
    {
        $last_dot = strrpos($file, '.');
        return $last_dot ?
            substr($file, 0, $last_dot) . "-$md5" . substr($file, $last_dot) : false;
    }

    public static function getRecursiveFileList($dir)
    {
        $files = array();
        $base_path_length = strlen($dir) + 1;
        return self::scanDirRecursive($dir, $base_path_length);
    }

    private static function scanDirRecursive($dir, $base_path_length)
    {
        $files = array();
        foreach (scandir($dir) as $file) {
            if ($file[0] == '.') continue;
            if (is_dir("$dir/$file")) {
                $files = array_merge($files, self::scanDirRecursive("$dir/$file", $base_path_length));
            } else {
                $files[] = substr("$dir/$file", $base_path_length);
            }
        }
        return $files;
    }

    public static function saveCompilationInfo()
    {
        $string = "<?php\n\nreturn array(\n";
        foreach (self::$files_precompiled as $key => $value) {
            $string .= "    '$key' => '$value',\n";
        }
        $string .= ");";
        file_put_contents(AP\FILES_PRECOMPILED, $string);
    }

    public static function registerCompiledFile($file, $output_file)
    {
        self::$files_precompiled[$file] = $output_file;
    }

    public static function deleteCompiledFile($file)
    {
        if (!isset(self::$files_precompiled[$file])) return;

        $to_delete = AP\OUTPUT_DIR . '/' . self::$files_precompiled[$file];
        if (is_file($to_delete)) {
            unlink($to_delete);
        }
    }

    public static function replaceExtension($file, $new_ext)
    {
        return substr($file, 0, strrpos($file, '.')) . '.' . $new_ext;
    }

    public static function clearDirectory($dir, $silent = true)
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

                if (!self::clearDirectory($dir . '/' . $item, $silent)) {
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

    static function renderErrorTemplate($filename, $source_path_abs, $message)
    {
        $template = file_get_contents(
            EXTENSIONS . '/asset_pipeline/content/preprocessor_error.tpl'
        );
        return sprintf($template, ASSETS_URL, $filename, $source_path_abs, $message);
    }
}

/*
        while (substr($body, 0, 2) == '//') {
            $line_end = strpos($body, "\n");
            if (is_numeric($line_end)) {
                $line = substr($body, 2, $line_end);
                $body = substr($body, $line_end + 1);
            } else {
                $line = $body;
                $body = '';
            }

            if (substr($line, 0, 1) == '=') {
            echo $line; die;
                $line = preg_split('/\s+/', ltrim(substr($line, 2)));
                if ($line[0] == 'require') {
                    $requires[] = $line[1];
                    echo $line[1];die;
                }
            }
        }
*/
