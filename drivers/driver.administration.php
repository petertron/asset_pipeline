<?php

// Administration extension driver

use asset_pipeline\ap;

class extension_Asset_pipeline extends Extension
{
    static $instance;

    public  $settings;
    private $source_subdirs;
    public $output_url;
    private $source_files;

    static $default_settings = array(
        'source_directory' => 'assets-src',
        'output_directory_parent' => 'workspace',
        'output_directory' => 'assets',
        'precompile_stylesheets' => 'application',
        'precompile_scripts' => 'application',
        'filename_md5_hash' => 'yes'
    );

    public function __construct()
    {
        parent::__construct();
        $this->settings = Symphony::Configuration()->get(ap\ID);
        if (!$this->settings) return;


        $source_dir = ap\SOURCE_DIR;
        $this->source_subdirs = array();
        foreach (scandir($source_dir) as $file) {
            if ($file[0] == '.') continue;
            if (is_dir($source_dir . $file)) {
                $this->source_subdirs[] = $source_dir . $file;
            }
        }

        self::$instance = $this;
    }


    public function install()
    {
    }

    public function uninstall()
    {
        Symphony::Configuration()->remove(ap\ID);
        Symphony::Configuration()->remove(ap\COMPILATION_LIST);
        Symphony::Configuration()->write();
    }

    public function fetchNavigation()
    {
        if ($this->settings) {
            return array(
                array(
                    'location' => __('Blueprints'),
                    'name' => __('Asset Compilation List'),
                    'link' => '/blueprints/asset-compilation-list/',
                    'relative' => false
                )
            );
        }
    }

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/all/',
                'delegate' => 'ModifySymphonyLauncher',
                'callback' => 'modifyLauncher'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => 'appendPreferences'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => 'savePreferences'
            ),/*
            array(
                'page' => '/frontend/',
                'delegate' => 'FrontendParamsPostResolve',
                'callback' => 'paramsPostResolve'
            ),*/
            array(
                'page' => '/frontend/',
                'delegate' => 'FrontendOutputPreGenerate',
                'callback' => 'outputPreGenerate'
            )
        );
    }

    // Reroute URL for Compilation List page

    public function modifyLauncher()
    {
        $sym_page = getCurrentPage();
        if ($sym_page == '/blueprints/asset-compilation-list/') {
            if (isset($_POST['with-selected']) || isset($_POST['action'])) {
                $_GET['symphony-page'] = '/extension/asset_pipeline/compilation_list_actions/';
            } else {
                $_GET['symphony-page'] = '/extension/asset_pipeline/compilation_list/';
            }
        }
    }

    public function appendPreferences($context)
    {
        $settings = is_array($this->settings) ? $this->settings : self::$default_settings;

        $fieldset = new XMLElement(
            'fieldset',
            new XMLElement('legend', 'Asset Pipeline'),
            array('class' => 'settings')
        );

        if (!$this->settings) {
            $fieldset->appendChild(
                new XMLElement(
                    'div',
                    __('The settings for this extension have not been saved yet. When you save your preferences, '
                    . 'folders for your assets will be created if they do not exist already.'),
                    array('class' => 'columns notice')
                )
            );
        }
        $fieldset->appendChild(
            Widget::Label(
                __('Source Directory'),
                Widget::Input(
                    'settings[' . ap\ID . '][source_directory]', $settings['source_directory']
                ),
                null, null, array('class' => 'column')
            )
        );

        $two_columns = new XMLElement('div', null, array('class' => 'two columns'));
        $two_columns->appendChild(
            Widget::Label(
                __('Output Parent Directory'),
                Widget::Select(
                    'settings[' . ap\ID . '][output_parent_directory]',
                    array(
                        array('workspace', strtolower($settings['output_parent_directory']) != 'root', 'Workspace'),
                        array('root', strtolower($settings['output_parent_directory']) == 'root', 'Symphony Root')
                    )
                ),
                'column'
            )
        );
        $two_columns->appendChild(
            Widget::Label(
                __('Output Directory'),
                Widget::Input(
                    'settings[' . ap\ID . '][output_directory]', preg_replace('/\s+/', PHP_EOL, $settings['output_directory'])
                ),
                'column'
            )
        );
        $fieldset->appendChild($two_columns);

        $two_columns = new XMLElement('div', null, array('class' => 'two columns'));
        Widget::Checkbox(
            'settings[asset_pipeline][generate_md5]',
            'yes',
            'When precompiling files, include MD5 hash in filename',
            $two_columns
        );
        $fieldset->appendChild($two_columns);

        $context['wrapper']->appendChild($fieldset);
    }

    public function savePreferences($context)
    {
        /*
        General::realiseDirectory(WORKSPACE . '/assets-src/images');
        General::realiseDirectory(WORKSPACE . '/assets-src/stylesheets');
        General::realiseDirectory(WORKSPACE . '/assets-src/scripts');

        Symphony::Configuration()->set('precompile_scripts', 'application', ap\ID);
        Symphony::Configuration()->write();
*/
        if (!array_key_exists(ap\COMPILATION_LIST, $context['settings'])) {
            $context['settings'][ap\COMPILATION_LIST] = array('scripts/application.js', 'stylesheets/application.css');
        }
    }

}
