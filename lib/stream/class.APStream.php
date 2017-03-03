<?php

namespace AssetPipeline;

use General;

class APStream extends Stream
{
    static function getPreprocessors($output_type = null)
    {
        if ($output_type) {
            $preprocessors = array();
            foreach (self::$preprocessors as $name => $prepro) {
                if ($prepro->getOutputType() == $output_type) {
                    $preprocessors[$name] = $prepro;
                }
            }
            return $preprocessors;
        } else {
            return self::$preprocessors;
        }
    }

    function setOutput()
    {
        $glob_exp = $this->glob_exp;
        $source_file_ext = General::getExtension($glob_exp);

        if (!$source_file_ext) {
            if ($this->calling_file) {
                // If filename has no extension then try to ascertain whether CSS or JS is to be output.
                $output_type = General::getExtension(substr($this->calling_file, 0, -4));
            }
            if (!isset($output_type) && isset($this->params['output_type'])) {
                $output_type = $this->params['output_type'];
            }
            if ($output_type != 'css' && $output_type != 'js') {
                return true;
            }

            $glob_exp .= '.{' . $output_type . ',' . $output_type . '.php';
            $prepros = self::getPreprocessors($output_type);
            if (!empty($prepros)) {
                foreach(array_keys($prepros) as $name) {
                    $glob_exp .= ",$name,$name.php";
                }
            }
            $glob_exp .= '}';
        }

        // Find file.
        $source_file_abs = $this->locateSourceFile($glob_exp);
        if (!$source_file_abs) {
            return;
        }

        $modified_file_path = $this->shortenedFilePath($source_file_abs, SOURCE_FILES . '/');

        // Get file type.
        $mime_type = mime_content_type($source_file_abs);
        $primary_content_type = explode('/', $mime_type)[0];
        $input_type = General::getExtension($modified_file_path);

        if ($primary_content_type == 'text') {
            //if (General::getExtension($source_file_abs) == 'php') {
            if ($input_type == 'php') {
                ob_start();
                include $source_file_abs;
                $content = ob_get_clean();
                $modified_file_path = substr($modified_file_path, 0, -4);
                $input_type = General::getExtension($modified_file_path);
            } else {
                $content = file_get_contents($source_file_abs);
            }

            // Apply code preprocessing if required.
            if (in_array($input_type, array_keys(self::getPreprocessors()))) {
                $prepro = self::$preprocessors[$input_type];
                $content = $prepro->convert($content, dirname($source_file_abs));
                if (isset($prepro->error)) {
                    self::renderErrorTemplate($source_file_abs, $prepro->error);
                }
                $modified_file_path = substr($modified_file_path, 0, 0 - strlen($input_type)) . $prepro->getOutputType();
                $this->output_type = $prepro->getOutputType();
            } else {
                $this->output_type = $input_type;
            }

            if (APP_MODE == 'administration') {
                // Minify content.
                if ($this->output_type == 'css') {
                    $content = self::minifyCSS($content);
                } elseif ($this->output_type == 'js') {
                    require_once EXTENSIONS . '/asset_pipeline/lib/JSMin.php';
                    $content = JSMin::minify($content);
                }
            }

            if ($this->action == 'url-for') {
                // Save file and output file URL.
                $output_file = filename_insert_md5($modified_file_path, md5($content));
                if (APP_MODE == 'administration') {
                    file_put_contents(OUTPUT_DIR . '/' . $output_file, $content);
                    $this->output = OUTPUT_URL . '/' . $output_file;
                } else {
                    file_put_contents(ASSET_CACHE . '/' . $output_file, $content);
                    $this->output = '/' . $output_file . '/?mode=pipeline';
                }
            } elseif ($this->action == 'filename-for') {
                $output_file = filename_insert_md5($modified_file_path, md5($content));
                file_put_contents(OUTPUT_DIR . '/' . $output_file, $content);
                $this->output = $output_file;
            } else {
                $this->output = $content;
            }
        } else {
            // Non-text file.
            if ($this->action == 'url-for') {
                $output_file = filename_insert_md5($modified_file_path, md5_file($source_file_abs));
                if (APP_MODE == 'administration') {
                    copy($source_file_abs, OUTPUT_DIR . '/' . $output_file);
//                     $this->output = AP\OUTPUT_URL . '/' . $output_file;
                    $this->output = OUTPUT_URL . '/' . $output_file;
                } else {
                    symlink($source_file_abs, ASSET_CACHE . '/' . $output_file);
                    $this->output = '/' . $output_file . '/?mode=pipeline';
                }
            } else {
                $this->output = "data:$mime_type;base64," . \base64_encode(\file_get_contents($source_file_abs));
            }
        }
    }

    function shortenedFilePath($source_file_abs, $base_path)
    {
        return substr($source_file_abs, strpos($source_file_abs, '/', strlen($base_path)) + 1);
    }

    static function minifyCSS($buffer)
    {
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        // Remove space after colons
        $buffer = str_replace(': ', ':', $buffer);
        // Remove line breaks & tabs
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t"), '', $buffer);
        // Collapse adjacent spaces into a single space
        $buffer = preg_replace('/\s{2,}/', ' ', $buffer);
        // Remove spaces that might still be left where we know they aren't needed
        $buffer = str_replace(array('} '), '}', $buffer);
        $buffer = str_replace(array('{ '), '{', $buffer);
        $buffer = str_replace(array('; '), ';', $buffer);
        $buffer = str_replace(array(', '), ',', $buffer);
        $buffer = str_replace(array(' }'), '}', $buffer);
        $buffer = str_replace(array(' {'), '{', $buffer);
        $buffer = str_replace(array(' ;'), ';', $buffer);
        $buffer = str_replace(array(' ,'), ',', $buffer);
        return $buffer;
    }

}
