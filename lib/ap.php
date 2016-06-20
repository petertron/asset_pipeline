<?php

namespace asset_pipeline;

use Symphony;
use General;

class AP
{
    const ID = 'asset_pipeline';

    private static $initialised = false;

    private static $source_directories;
    private static $files_compiled;
    private static $file_files_precompiled;
    private static $output_dir;
    private static $output_url;
    private static $plugins = array();

    public static function initialise()
    {
        if (self::$initialised) return;

        // Source directories

        include MANIFEST . '/asset_pipeline/source-directories.php';
        self::$source_directories = $source_directories;

        // Define output directory

        $output_dir = trim(self::getSetting('output_directory'), '/');
        $use_docroot = (self::getSetting('output_parent_directory') == 'docroot');
        self::$file_files_precompiled = MANIFEST . '/asset_pipeline/files-compiled.php';
        self::$output_dir = ($use_docroot ? DOCROOT : WORKSPACE) . '/' . $output_dir;
        self::$output_url = ($use_docroot ? '/' : '/workspace/') . $output_dir;

        self::$files_compiled = is_file(self::$file_files_precompiled) ?
            include self::$file_files_precompiled : array();

        self::$initialised = true;
    }

    public static function getFilesCompiled()
    {
        return self::$files_compiled;
    }

    public static function registerPlugins()
    {
        Symphony::ExtensionManager()->notifyMembers(
            'RegisterPlugins', '/extension/asset_pipeline/', array('plugins' => &self::$plugins)
        );
    }

    public static function getOutputType($input_type)
    {
        if (self::$plugins[$input_type]) {
            return self::$plugins[$input_type];
        } else {
            return $input_type;
        }
    }

    public static function getSetting($name)
    {
        return Symphony::Configuration()->get($name, self::ID);
    }

    public static function getOutputDirectory()
    {
        return self::$output_dir;
    }

    public static function getOutputUrl()
    {
        return self::$output_url;
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
        //if (!INSTALLATION_COMPLETE) return null;

        // If file exists in output directory, return URL for it.
        if (isset(self::$files_compiled[$file])) {
            $file_out = self::$files_compiled[$file];
        } else {
            $file_out = $file;
        }

        if (is_file(self::$output_dir . '/' . $file_out)) {
            return self::$output_url . '/' . $file_out;
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
        $output_type = AP::getOutputType($input_type);

        $output_file = ($output_type == $input_type) ?
            $file : self::replaceExtension($file, $output_type);
        if ($output_type == 'css') {
            $output = self::processCSS($source_file_abs);
            file_put_contents(MANIFEST . '/asset_pipeline/cache/' . $output_file, $output);
        } elseif ($output_type == 'js') {
            $output = self::processJS($source_file_abs);
            file_put_contents(MANIFEST . '/asset_pipeline/cache/' . $output_file, $output);
        }

        return "/$output_file/?mode=pipeline";
    }

    // Process CSS

    public static function processCSS($source_path_abs)
    {
        $this_func = __METHOD__;

        $output = '';
        $dir_path = dirname($source_path_abs);
        $content = file_get_contents($source_path_abs);
        $input_type = General::getExtension($source_path_abs);
        if ($input_type != 'css') {
            if (self::getOutputType($input_type) == 'css') {
                $result = call_user_func("asset_pipeline\\$input_type\\compile", $content, $dir_path);
                $content = $result['content'];
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
                        $line = preg_split('/\s+/', ltrim(substr($line, 2)));
                        if ($line[0] == 'require') {
                            $requires[] = $line[1];
                        }
                    }
                    $line = strtok("\r\n");
                }
                if(!empty($requires)) {
                    foreach ($requires as $file) {
                        $output .= $this_func($dir_path . '/' . $file);
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
        $this_func = __METHOD__;

        $output = '';
        $dir_path = dirname($source_path_abs);
        $body = file_get_contents($source_path_abs);
        $input_type = General::getExtension($source_path_abs);
        if ($input_type != 'js') {
            if (self::getOutputType($input_type) == 'js') {
                $result = call_user_func("asset_pipeline\\$input_type\\compile", $body, $dir_path);
                $body = $result['content'];
            }
        } else {
            return false; // Invalid input type
        }

        $requires = array();

        while (substr($body, 0, 2) == '//') {
            $line_end = strpos($body, PHP_EOL, 2);
            if (is_numeric($line_end)) {
                $line = substr($body, 2, $line_end);
                $body = substr($body, $line_end + 1);
            } else {
                $line = $body;
                $body = '';
            }

            if (substr($line, 0, 1) == '=') {
                $line = preg_split('/\s+/', ltrim(substr($line, 2)));
                if ($line[0] == 'require') {
                    $requires[] = $line[1];
                }
            }
        }

        if(!empty($requires)) {
            foreach ($requires as $file) {
                $output .= $this_func($dir_path . '/' . $file);
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

    public static function isCodeType($file_ext)
    {
        return in_array($file_ext, array('css', 'js'));
    }

    public static function saveCompilationInfo()
    {
        $string = "<?php\n\nreturn array(\n";
        foreach (self::$files_compiled as $key => $value) {
            $string .= "    '$key' => '$value',\n";
        }
        $string .= ");";
        file_put_contents(self::$file_files_precompiled, $string);
    }

    public static function registerCompiledFile($file, $output_file)
    {
        self::$files_compiled[$file] = $output_file;
    }

    public static function deleteCompiledFile($file)
    {
        if (!isset(self::$files_compiled[$file])) return;

        $to_delete = self::$output_dir . '/' . self::$files_compiled[$file];
        if (is_file($to_delete)) {
            unlink($to_delete);
        }
    }

    public static function replaceExtension($file, $new_ext)
    {
        return substr($file, 0, strrpos($file, '.')) . '.' . $new_ext;
    }
}

/*
    public function deleteFile($path)
    {
        $path_abs = $this->getPathAbs($path);
        if (file_exists($path_abs)) {
            unlink ($path_abs);
        }
    }

    public function getPathAbs($path)
    {
        return $this->base_dir . '/' . trim($path, '/');
    }

*/