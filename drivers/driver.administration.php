<?php

// Administration extension driver

require EXTENSIONS . '/asset_pipeline/lib/directory.php';

use asset_pipeline\AP;

class extension_Asset_pipeline extends Extension
{
    public  $settings;

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

        $this->settings = Symphony::Configuration()->get(AP::ID);
        $this->installation_complete = (bool)$this->settings;
        if ($this->installation_complete) {
            AP::initialise();
        }
    }

/*
    public function install()
    {
    }
*/
    public function uninstall()
    {
        Symphony::Database()->query("DROP TABLE " . AP::TBL_FILES_PRECOMPILED);
        Symphony::Configuration()->remove(AP::ID);
        Symphony::Configuration()->remove(AP::COMPILATION_LIST);
        Symphony::Configuration()->write();
    }

    public function fetchNavigation()
    {
        if ($this->installation_complete) {
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
        if (!$this->installation_complete) return;

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
        $fieldset = new XMLElement(
            'fieldset',
            new XMLElement('legend', 'Asset Pipeline'),
            array('class' => 'settings')
        );

        if (!$this->installation_complete) {
            $fieldset->appendChild(
                new XMLElement(
                    'div',
                    __('The settings for this extension have not been saved yet. When you save your preferences, '
                    . 'folders for your assets will be created if they do not exist already.'),
                    array('class' => 'columns notice')
                )
            );
            $settings = self::$default_settings;
        } else {
            $settings = Symphony::Configuration()->get(AP::ID);
        }

        $fieldset->appendChild(
            Widget::Label(
                __('Source Directory'),
                Widget::Input(
                    'settings[' . ap::ID . '][source_directory]', $settings['source_directory']
                ),
                null, null, array('class' => 'column')
            )
        );

        $two_columns = new XMLElement('div', null, array('class' => 'two columns'));
        $two_columns->appendChild(
            Widget::Label(
                __('Output Parent Directory'),
                Widget::Select(
                    'settings[' . ap::ID . '][output_parent_directory]',
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
                    'settings[' . ap::ID . '][output_directory]', preg_replace('/\s+/', PHP_EOL, $settings['output_directory'])
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
        if (!$this->installation_complete) {
            $self_settings = $context['settings'][ap::ID];

            // Create asset directories

            $source_dir = WORKSPACE . '/' . trim($self_settings['source_directory'], '/');
            if (!is_dir($source_dir)) {
                General::realiseDirectory($source_dir . '/images');
                General::realiseDirectory($source_dir . '/scripts');
                General::realiseDirectory($source_dir . '/stylesheets');
            }

            $output_dir = ($self_settings['output_parent_directory'] ? WORKSPACE : DOCROOT)
                . '/' . trim($self_settings['output_directory'], '/');
            General::realiseDirectory($output_dir);

            // Create default compilation list

            $context['settings'][AP::COMPILATION_LIST] = array(
                'application.css' => 'stylesheets',
                'application.js' => 'scripts'
            );

            // Create DB table for precompiled files

            Symphony::Database()->query(
                "CREATE TABLE IF NOT EXISTS `" . AP::TBL_FILES_PRECOMPILED . "` (
                    `file` varchar(255) NOT NULL,
                    `compiled_file` varchar(255),
                    PRIMARY KEY (`file`)
                )"
            );
        }
    }

}
