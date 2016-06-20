<?php

require_once EXTENSIONS . '/asset_pipeline/lib/ap.php';

use asset_pipeline\AP;

class contentExtensionAsset_pipelinePrecompile_assets_ajax
{
    public function __construct()
    {
        $this->_output = array();
        $this->error_occurred = false;
    }

    public function build(array $context = array())
    {
        AP::initialise();
        AP::registerPlugins();

        if (is_array($_POST['items'])) {
            $items = $_POST['items'];

            $output_dir_abs = AP::getOutputDirectory();

            include MANIFEST . '/asset_pipeline/source-directories.php';
            $this->_output['html'] = "Files compiled:<br><br>";

            // Compile non-code files

            foreach ($items as $dir_path) {
                $directory = $source_directories[$dir_path];
                if (in_array($directory['type'], array('css', 'js'))) continue;
                $source_dir_abs = WORKSPACE . '/' . $dir_path;// . '/';
                $listing = AP::getRecursiveFileList($source_dir_abs);
                foreach ($listing as $file) {
                    $source_file_abs = $source_dir_abs . '/' . $file;
                    $md5 = md5_file($source_file_abs);
                    $output_file = AP::filenameInsertMD5($file, $md5);
                    $output_file_abs = $output_dir_abs . '/' . $output_file;
                    copy ($source_file_abs, $output_file_abs);
                    AP::registerCompiledFile($file, $output_file);
                    $this->_output['html'] .= "$output_file<br>";
                }
            }

            // Compile code files

            $processors = array('css' => 'processCSS', 'js' => 'processJS');
            foreach ($items as $dir_path) {
                $directory = $source_directories[$dir_path];
                $type = $directory['type'];
                if (!AP::isCodeType($type)) continue;
                //if (!array_key_exists($type, $processors)) continue;
                $source_dir_abs = WORKSPACE . '/' . $dir_path;
                $to_compile = $directory['precompile_files'];
                $processCode = $processors[$type];
                if (is_array($to_compile) && !empty($to_compile)) {
                    foreach ($to_compile as $file) {
                        $input_type = General::getExtension($file);
                        if (AP::getOutputType($input_type) != $type) continue;
                        $source_file_abs = $source_dir_abs . '/' . $file;
                        $output = AP::$processCode($source_file_abs);
                        AP::deleteCompiledFile($file); // Delete previous compilation, if any
                        $md5 = md5($content);
                        $output_file = AP::filenameInsertMD5($file, $md5);
                        $output_file_abs = $output_dir_abs . '/' . $output_file;
                        file_put_contents($output_file_abs, $output);
                        AP::registerCompiledFile($file, $output_file);
                        $this->_output['html'] .= "$output_file<br>";
                    }
                }
            }

            AP::saveCompilationInfo();
        }
    }

    public function generate($page = NULL)
    {
        header('Content-Type: text/javascript');
        echo json_encode($this->_output);
        exit();
    }

}
