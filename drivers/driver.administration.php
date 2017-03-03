<?php

// Driver for administration

use AssetPipeline as AP;

class Extension_Asset_pipeline extends Extension
{
    public $settings;

    static $default_settings = array(
        'source_directory' => 'assets-src',
        'output_parent_directory' => 'workspace',
        'output_directory' => 'assets'
    );

    public function uninstall()
    {
        Symphony::Configuration()->remove(AP\ID);
        Symphony::Configuration()->write();
        General::deleteDirectory(MANIFEST . '/' . AP\ID);
    }

    public function fetchNavigation()
    {
        if (AP\INSTALLATION_COMPLETE) {
            return array(
                array(
                    'location' => __('System'),
                    'name' => __('Precompile Assets'),
                    'link' => '/system/precompile-assets/',
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
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'AdminPagePostCallback',
                'callback' => 'postCallback'
            ),
            array(
                'page' => '/frontend/',
                'delegate' => 'FrontendOutputPreGenerate',
                'callback' => 'outputPreGenerate'
            )
        );
    }

    // Reroute URL for Compilation List page

    public function appendPreferences($context)
    {
        Administration::instance()->Page->addScriptToHead(
            AP\ASSETS_URL . '/asset-pipeline.preferences.js', 3134
        );

        $fieldset = new XMLElement(
            'fieldset',
            new XMLElement('legend', 'Asset Pipeline'),
            array('class' => 'settings')
        );

        if (!AP\INSTALLATION_COMPLETE) {
            $fieldset->appendChild(
                new XMLElement(
                    'div',
                    __('No settings have been saved for this extension.'),
                    ['class' => 'columns notice', 'style' => 'padding-left: 8px; padding-right: 8px; margin-bottom: 6px']
                )
            );
            $settings = self::$default_settings;
        } else {
            $settings = Symphony::Configuration()->get(AP\ID);
        }

        // Source directory

        $column = new XMLElement('div', null, array('class' => 'column'));
        $column->appendChild(
            Widget::Label(
                __('Assets Source Directory'),
                Widget::Input(
                    'settings[asset_pipeline][source_directory]',
                    trim(trim(trim($settings['source_directory']), '/'))
                ),
                'column'
            )
        );
        $fieldset->appendChild($column);

        // Output directory.

        $two_columns = new XMLElement('div', null, array('class' => 'two columns'));
        $two_columns->appendChild(
            Widget::Label(
                __('Output Parent Directory'),
                Widget::Select(
                    'settings[asset_pipeline][output_parent_directory]',
                    array(
                        array('workspace', strtolower($settings['output_parent_directory']) != 'docroot', __('Workspace')),
                        array('docroot', strtolower($settings['output_parent_directory']) == 'docroot', __('Document Root'))
                    )
                ),
                'column'
            )
        );
        $two_columns->appendChild(
            Widget::Label(
                __('Output Directory'),
                Widget::Input(
                    'settings[asset_pipeline][output_directory]',
                    trim(trim(trim($settings['output_directory']), '/'))
                ),
                'column'
            )
        );
        $fieldset->appendChild($two_columns);

        $fieldset->appendChild(
            Widget::Label(
                __('Precompile Files<i>Comma separated list</i>'),
                Widget::Input(
                    'settings[asset_pipeline][precompile_files]',
                    trim(trim(trim($settings['precompile_files']), '/'))
                ),
                'column'
            )
        );
        $context['wrapper']->appendChild($fieldset);
    }

    /*
     * Preferences pre-save
     */
    public function savePreferences($context)
    {
        $current_values = Symphony::Configuration()->get(AP\ID);
        $new_values = $context['settings'][AP\ID];
        $errors = $context['errors'];

        $source_dir = trim(trim($new_values['source_directory'], '/'));
        if (!$source_dir) {
            $errors[AP\ID]['source_directory'] = __('This is a required field');
        } else {
            $new_values['source_directory'] = $source_dir;
            $source_dir_abs = WORKSPACE . '/' . $source_dir;
            // Create new source directory if necessary.
            $source_dir_current = isset($current_values['source_directory']) ?
                $current_values['source_directory'] : null;
            $source_dir_current_abs = isset($source_dir_current) ?
                WORKSPACE . '/' . $source_dir_current : null;
            if ($source_dir_current_abs) {
                if ($source_dir != $source_dir_current && !is_dir($source_dir_abs)) {
                    if (!is_dir(dirname($source_dir_abs))) {
                        General::realiseDirectory(dirname($source_dir_abs));
                    }
                    rename($source_dir_current_abs, $source_dir_abs);
                }
            } else {
                // Create new directory.
                General::realiseDirectory($source_dir_abs);
                General::realiseDirectory($source_dir_abs . '/images');
                General::realiseDirectory($source_dir_abs . '/scripts');
                General::realiseDirectory($source_dir_abs . '/stylesheets');
            }
        }
        // Create asset directories
        $output_dir = (($self_settings['output_parent_directory'] == 'docroot') ? DOCROOT : WORKSPACE)
            . '/' . trim($self_settings['output_directory'], '/');
        General::realiseDirectory($output_dir);
        General::realiseDirectory(AP\ASSET_CACHE);


    }

    public function postCallback($context)
    {
        //echo "<pre>";print_r($context);echo "</pre>"; die;
        $callback = $context['callback'];
        //echo "<pre>";print_r($callback);echo "</pre>"; die;

        if ($callback['driver'] == 'systemprecompile-assets') {
            $callback['driver_location'] = EXTENSIONS . '/asset_pipeline/content/content.systemprecompile-assets.php';
            $callback['classname'] = 'contentExtensionAsset_pipelinePrecompile_assets';
        }

        $context['callback'] = $callback;
    }

}
