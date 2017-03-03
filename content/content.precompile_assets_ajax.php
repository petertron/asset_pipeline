<?php

//require_once EXTENSIONS . '/asset_pipeline/lib/functions.php';
require_once EXTENSIONS . '/asset_pipeline/lib/stream/stream-setup.php';

use AssetPipeline as AP;

class contentExtensionAsset_pipelinePrecompile_assets_ajax
{
    static $preprocessors = array();

    public function __construct()
    {
        Symphony::ExtensionManager()->notifyMembers(
            'RegisterPreprocessors',
            '/extension/asset_pipeline/',
            array('preprocessors' => &self::$preprocessors)
        );
        $this->_output = 'Files compiled:<br><br>';
        $this->error_occurred = false;
    }

    public function build(array $context = array())
    {
        $this->doc_root = getcwd();

        $files_compiled = array();

        // Compile non-code files.
        $directories = new RecursiveDirectoryIterator(AP\SOURCE_FILES, FilesystemIterator::SKIP_DOTS);
        foreach ($directories as $dir_path => $dir_info) {
            chdir($dir_path);
            $rdi = new RecursiveDirectoryIterator('./', FilesystemIterator::SKIP_DOTS);
            $rii = new RecursiveIteratorIterator($rdi);
            foreach ($rii as $file_path => $file_info) {
                $file = substr($file_path, 2);
                if (substr(mime_content_type($file), 0, 4) != 'text'
                    && !array_key_exists($file, $files_compiled)) {
                    $ext = General::getExtension($file_path);
                    $md5 = md5_file($file);
                    if ($ext) {
                        $output_file = substr($file, 0, strrpos($file, '.')) . '-' . $md5 . '.' . $ext;
                    } else {
                        $output_file = $file . '-' . $md5;
                    }
                    $output_file_abs = AP\OUTPUT_DIR . '/' . $output_file;
                    $output_dir_abs = dirname($output_file_abs);
                    if (!is_dir($output_dir_abs)) {
                        General::realiseDirectory($output_dir_abs);
                    }
                    copy($file_path, $output_file_abs);
                    $files_compiled[$file] = $output_file;
                    $this->_output .= "$output_file<br>";
                }
            }
        }
        chdir($this->doc_root);

        // Compile code files

        $settings = Symphony::Configuration()->get(AP\ID);
        $precompile = explode(',', $settings['precompile_files']);
        foreach ($precompile as $input_file) {
            $input_file = trim(trim($input_file), '/');
            $output_file = file_get_contents('ap.filename-for://' . $input_file);
            if (General::getExtension($input_file) == 'php') {
                $input_file = substr($input_file, 0, -4);
            }
            $files_compiled[$input_file] = $output_file;
            $this->_output .= "$output_file<br>";
        }

        #file_put_contents(AP\PRECOMPILED_FILES, json_encode($files_compiled));
        file_put_contents(AP\PRECOMPILED_FILES, "<?php\n\nreturn " . var_export($files_compiled, true) . ";");
    }

    public function generate($page = NULL)
    {
        header('Content-Type: text/html');
        echo $this->_output;
        exit();
    }
}
