<?php

namespace asset_pipeline;

trait DirectoryMethods
{
    public function fileExists($path)
    {
        return is_file($this->getPathAbs($path)) ? true : false;
    }

    public function loadFile($path)
    {
        $path_abs = $this->getPathAbs($path);
        if (is_file($path_abs)) {
            return file_get_contents($path_abs);
        } else {
            return false;
        }
    }

    public function saveFile($path, $contents)
    {
        $path_abs = $this->getPathAbs($path);
        return file_put_contents($path_abs, $contents);
    }

    function getPathAbs($path)
    {
        return $this->base_dir . '/' . trim($path, '/');
    }
}

class SourceDirectory
{
    use asset_pipeline\DirectoryMethods;

    private $base_dir;

    public function __construct()
    {
        $this->base_dir = WORKSPACE . '/' . trim(Symphony::Configuration()->get('source_directory', 'asset_pipeline'), '/');
    }

}

class OutputDirectory
{
    use asset_pipeline\DirectoryMethods;

    private $base_dir;

    public function __construct()
    {
        $this->base_dir = (Symphony::Configuration()->get('output_parent_directory', 'asset_pipeline') == 'docroot') ? DOCROOT : WORKSPACE . '/' .  trim(Symphony::Configuration()->get('output_directory', 'asset_pipeline'), '/');
    }

}