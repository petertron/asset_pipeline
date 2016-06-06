<?php

require EXTENSIONS . '/asset_pipeline/lib/ap.php';
require EXTENSIONS . '/asset_pipeline/lib/prepro.php';

use asset_pipeline\ap;
use asset_pipeline\prepro;

class contentExtensionAsset_pipelineCompilation_list_actions
{
    public function __construct()
    {
        $this->_output = array();
        $this->error_occurred = false;
    }

    public function build(array $context = array())
    {
        Symphony::ExtensionManager()->notifyMembers('RegisterPlugins', '/extension/asset_pipeline/');

        $action = $_POST['action'];
        $source_files = ap\getSourceFiles();
        $compilation_list = ap\getCompilationList();
        if (!$compilation_list) {
            $compilation_list = array();
        }
        //$this->compilation_list = $compilation_list;

        if (isset($_POST['with-selected'])) {
            $items = array_keys($_POST['items']);
            switch ($_POST['with-selected']) {
                case 'remove':
                    if ($compilation_list) {
                        //$compilation_list = array_values(array_diff($compilation_list, $items));
                        ap\saveCompilationList($compilation_list);
                    }
                    break;
                case 'remove-compiled-files':
                    break;
                case 'compile':
                    $css_files = array();
                    $js_files = array();
                    $results = array();

                    foreach ($items as $file) {
                        $ext = General::getExtension($file);
                        $prepro = prepro\getPreproIfExists($ext);
                        $output_type = $prepro ? $prepro::getOutputType() : $ext;
                        if ($output_type == 'css') {
                            $css_files[$file] = $prepro;
                            continue;
                        }
                        if ($output_type == 'js') {
                            $js_files[$file] = $prepro;
                            continue;
                        }
                        // Compile non-code file
                        $source_file_abs = ap\file_path_join(ap\SOURCE_DIR, $file);
                        $md5 = md5_file($source_file_abs);
                        $output_file = ap\filenameInsertMD5(ap\shortenFilePath($file), $md5);
                        $output_file_abs = ap\file_path_join(ap\OUTPUT_DIR, $output_file);
                        copy ($source_file_abs, $output_file_abs);
                    }

                    if (!empty($css_files)) {
                        ob_start();
                        foreach ($css_files as $source_path => $prepro) {
                            ap\AP::processCSS($source_path, $prepro);
                            $content = ob_get_contents();
                            ob_clean();
                            $md5 = md5($content);
                            $output_file = ap\filenameInsertMD5(ap\shortenFilePath($source_path), $md5);
                            $output_file_abs = ap\file_path_join(ap\OUTPUT_DIR, $output_file);
                            file_put_contents($output_file_abs, $content);
                            //$results[$file];
                        }
                        ob_end_clean();
                    }
                    break;
            }
        } elseif (isset($_POST['action'])) {
            $items = $_POST['items'];
            foreach ($items as $path_in) {
                list($base, $file) = ap\dissembleSourcePath($path_in);
                if (!array_key_exists($file, $compilation_list)) {
                    $compilation_list[$file] = $base;
                }
            }
            ksort($compilation_list);
            ap\saveCompilationList($compilation_list);
        }

        $files_available = $compilation_list ?
            array_diff($source_files, $compilation_list) : $source_files;
        $html = '';
        if ($files_available) {
            foreach ($files_available as $file) {
                $html .= '<option value="' . $file . '">' . $file . '</option>';
            }
        }
        $this->_output['files_available'] = $html;

        include EXTENSIONS . '/asset_pipeline/content/comp_list_table_rows.php';
        $this->_output['files_added'] = ap\xmlArrayToHtml($table_rows);
    }

    public function generate($page = NULL)
    {
        header('Content-Type: text/javascript');
        echo json_encode($this->_output);
        exit();
    }

    function precompileCodeFile($file)
    {
    }
}
