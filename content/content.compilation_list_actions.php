<?php

//require_once EXTENSIONS . '/asset_pipeline/lib/defs1.php';
require EXTENSIONS . '/asset_pipeline/lib/ap.php';
require EXTENSIONS . '/asset_pipeline/lib/prepro.php';

use asset_pipeline\AP as AP;
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
        AP::initialise();
        //AP::registerPlugins();

        $action = $_POST['action'];
        $source_files = AP::SourceDirectory()->getRecursiveFileList();
        $compilation_list = AP::getCompilationList();
        if (!$compilation_list) {
            $compilation_list = array();
        }
        //$this->compilation_list = $compilation_list;

        if (isset($_POST['with-selected'])) {
            $items = array_keys($_POST['items']);
            switch ($_POST['with-selected']) {
                case 'remove':
                    if ($compilation_list) {
                        foreach ($items as $item) {
                            unset($compilation_list[$item]);
                        }
                        AP::saveCompilationList($compilation_list);
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
                        $prepro = AP::getPreproIfExists($ext);
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
                        $source_file_abs = AP::SourceDirectory()->getPathAbs(
                            $compilation_list[$file] .'/' . $file
                        );
                        $md5 = md5_file($source_path_abs);
                        $output_file = AP::filenameInsertMD5($file, $md5);
                        $output_file_abs = AP::OutputDirectory()->getPathAbs($output_file);
                        copy ($source_file_abs, $output_file_abs);
                    }

                    if (!empty($css_files)) {
                        ob_start();
                        foreach ($css_files as $file => $prepro) {
                            $source_file = $compilation_list[$file] . '/' . $file;
                            AP::processCSS($source_file, $prepro);
                            $content = ob_get_contents();
                            ob_clean();
                            $md5 = md5($content);
                            $output_file = AP::filenameInsertMD5($file, $md5);
                            AP::OutputDirectory()->writeFile($output_file, $content);
                            Symphony::Database()->insert(
                                array('file' => $file, 'compiled_file' => $output_file),
                                AP::TBL_FILES_PRECOMPILED,
                                true
                            );
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
            AP::saveCompilationList($compilation_list);
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
}
