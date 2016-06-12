<?php

namespace asset_pipeline;

use Symphony;

class CompilationList
{
    private $compilation_list;

    public function __construct()
    {
        $compilation_list = Symphony::Configuration()->get(self::COMPILATION_LIST);
        if (is_array($compilation_list) && !empty($compilation_list)) {
            foreach ($compilation_list as $file => $subdir) {
                $file = trim($file, '/');
                if (!$file) continue;
                $subdir = trim($subdir, '/');
                //if (!$subdir) continue;
                $this->compilation_list[$file] = $subdir;
            }
            ksort($this->compilation_list);
        }
    }

    public function reset()
    {
        reset($compilation_list);
    }

    public function getCurrent()
    {
    }
}