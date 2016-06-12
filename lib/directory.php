<?php

namespace asset_pipeline;

use Symphony;

class BaseDirectory
{
    private $base_dir;

    public function __construct($base_dir)
    {
        $this->base_dir = $base_dir;
    }

    public function fileExists($path)
    {
        return is_file($this->getPathAbs($path)) ? true : false;
    }

    public function readFile($path)
    {
        $path_abs = $this->getPathAbs($path);
        if (is_file($path_abs)) {
            return file_get_contents($path_abs);
        } else {
            return false;
        }
    }

    public function writeFile($path, $contents)
    {
        $path_abs = $this->getPathAbs($path);
        return file_put_contents($path_abs, $contents);
    }

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

    public function getRecursiveFileList()
    {
        $files = array();
        $dir = $this->base_dir;
        $base_path_length = strlen($dir) + 1;
        return $this->scanDirRecursive($dir, $base_path_length);
    }

    private function scanDirRecursive($dir, $base_path_length)
    {
        $files = array();
        foreach (scandir($dir) as $file) {
            if ($file[0] == '.') continue;
            if (is_dir("$dir/$file")) {
                $files = array_merge($files, $this->scanDirRecursive("$dir/$file", $base_path_length));
            } else {
                $files[] = substr("$dir/$file", $base_path_length);
            }
        }
        return $files;
    }
}


class AP
{
    const ID = 'asset_pipeline';
    const COMPILATION_LIST = 'asset_pipeline_compilation_list';
    const TBL_FILES_PRECOMPILED = 'tbl_asset_pipeline_files_compiled';

    private static $initialised = false;

    private static $SourceDirectory;
    private static $OutputDirectory;

    public static function initialise()
    {
        if (self::$initialised) return;

        self::$SourceDirectory = new BaseDirectory(
            WORKSPACE . '/'
                . trim(Symphony::Configuration()->get('source_directory', self::ID), '/')
        );

        self::$OutputDirectory = new BaseDirectory(
            (Symphony::Configuration()->get('output_parent_directory', self::ID) == 'docroot') ? DOCROOT : WORKSPACE . '/' .  trim(Symphony::Configuration()->get('output_directory', self::ID), '/')
        );
    }

    public static function SourceDirectory()
    {
        return self::$SourceDirectory;
    }

    public static function OutputDirectory()
    {
        return self::$OutputDirectory;
    }

    public static function getCompilationList()
    {
        $compilation_list = Symphony::Configuration()->get(self::COMPILATION_LIST);
        if (is_array($compilation_list) && !empty($compilation_list)) {
            $compilation_list_a = array();
            foreach ($compilation_list as $file => $subdir) {
                $file = trim($file, '/');
                if (!$file) continue;
                $subdir = trim($subdir, '/');
                //if (!$subdir) continue;
                $compilation_list_a[$file] = $subdir;
            }
            ksort($compilation_list_a);
            return $compilation_list_a;
        } else {
            return null;
        }
    }

    public static function saveCompilationList($compilation_list)
    {
        Symphony::Configuration()->set(self::COMPILATION_LIST, $compilation_list);
        Symphony::Configuration()->write();
    }

    public static function processCSS($source_path, $prepro = null)
    {
        $content = self::SourceDirectory()->readFile($source_path);
        if ($prepro) {
            $result = $prepro::compile($content, dirname($source_path_abs));
            $content = $result['content'];
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
                        $ext = General::getExtension($file);
                        if ($ext == 'css') {
                            self::processCSS($file);
                        } else {
                            $prepro = getPreproIfExists($ext);
                            if ($prepro && $prepro::getOutputType() == 'css') {
                                self::processCSS($file, $prepro);
                            }
                        }
                    }
                }
            }
        }
        echo $content;
    }

    static function processJS($source_path, $prepro = null)
    {
        return "// breep";
    }

    public static function filenameInsertMD5($file, $md5)
    {
        $last_dot = strrpos($file, '.');
        return $last_dot ?
            substr($file, 0, $last_dot) . "-$md5" . substr($file, $last_dot) : false;
    }

}
