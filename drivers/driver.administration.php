<?php

// Driver for administration

require EXTENSIONS . '/asset_pipeline/lib/pipeline.php';

use asset_pipeline\AP;
use asset_pipeline\Pipeline;

class extension_Asset_pipeline extends Extension
{
    public  $settings;

    static $default_settings = array(
        'output_parent_directory' => 'workspace',
        'output_directory' => 'assets',
        'filename_md5_hash' => 'yes'
    );

    public function install()
    {
    }

    public function uninstall()
    {
        Symphony::Configuration()->remove(AP\ID);
        Symphony::Configuration()->write();
        General::deleteDirectory(MANIFEST . '/' . AP\ID);
        //General::deleteDirectory(AP\OUTPUT_DIR);
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
                'page' => '/frontend/',
                'delegate' => 'FrontendOutputPreGenerate',
                'callback' => 'outputPreGenerate'
            )
        );
    }

    // Reroute URL for Compilation List page

    public function modifyLauncher()
    {
        if (!AP\INSTALLATION_COMPLETE) return;

        $sym_page = getCurrentPage();
        if ($sym_page == '/system/precompile-assets/') {
            if (isset($_POST['action']['submit'])) {
                $_GET['symphony-page'] = '/extension/asset_pipeline/precompile_assets_ajax/';
            } else {
                $_GET['symphony-page'] = '/extension/asset_pipeline/precompile_assets/';
            }
        }
    }

    public function appendPreferences($context)
    {
        Administration::instance()->Page->addScriptToHead(
            URL . '/extensions/asset_pipeline/assets/asset-pipeline.preferences.js', 3134
        );

        $group = new XMLElement(
            'fieldset',
            new XMLElement('legend', 'Asset Pipeline'),
            array('class' => 'settings')
        );

        if (!AP\INSTALLATION_COMPLETE) {
            $group->appendChild(
                new XMLElement(
                    'div',
                    __('No settings have been saved for this extension. Choose an output directory and at least one source directory. When you save your preferences, directories will be created if they do not exist.'),
                    array('class' => 'columns notice', 'style' => 'padding-left: 8px; padding-right: 8px; margin-bottom: 6px')
                )
            );
            $settings = self::$default_settings;
        } else {
            $settings = Symphony::Configuration()->get(AP\ID);
        }

        // Output directory.

        $two_columns = new XMLElement('div', null, array('class' => 'two columns condensed'));
        $two_columns->appendChild(
            Widget::Label(
                __('Output Parent Directory'),
                Widget::Select(
                    'settings[asset_pipeline][output_parent_directory]',
                    array(
                        array('workspace', strtolower($settings['output_parent_directory']) != 'docroot', 'Workspace'),
                        array('docroot', strtolower($settings['output_parent_directory']) == 'docroot', 'Symphony Root')
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
        $group->appendChild($two_columns);
        $group->appendChild(new XMLElement('p', __('The output directory is where your files are placed when you precompile them for production.'), array('class' => 'help')));
        /*$two_columns = new XMLElement('div', null, array('class' => 'two columns'));
        Widget::Checkbox(
            'settings[asset_pipeline][generate_md5]',
            'yes',
            'When precompiling files, include MD5 hash in filename',
            $two_columns
        );
        $group->appendChild($two_columns);
*/
        // Source directories.

        $group->appendChild(new XMLElement('p', __('Source Directories'), array('class' => 'label')));
        $div = new XMLElement('div', null, array('class' => 'frame asset-pipeline-duplicator'));
        $duplicator = new XMLElement('ol');
        $duplicator->setAttribute('data-add', __('Add directory'));
        $duplicator->setAttribute('data-remove', __('Remove directory'));

        // Create templates.
        $duplicator->appendChild(self::createSourceDirDuplicatorTemplate('css'));
        $duplicator->appendChild(self::createSourceDirDuplicatorTemplate('js'));
        $duplicator->appendChild(self::createSourceDirDuplicatorTemplate('*'));

        // If there are errors then use POST data.
        $source_directories = isset($_POST['asset_pipeline']['source_directories']) ?
            $_POST['asset_pipeline']['source_directories'] : array();

        if (!empty($source_directories) && !empty($this->source_directory_errors)) {
            foreach ($source_directories as $position => $directory) {
                $duplicator->appendChild(
                    self::createSourceDirDuplicatorTemplate($directory['type'], $position, $directory, $this->source_directory_errors[$position])
                );
            }
        } // otherwise use saved data
        else {
            $source_directories = file_exists(AP\SOURCE_DIRECTORIES) ?
                include AP\SOURCE_DIRECTORIES : array();
            if (is_array($source_directories) && !empty($source_directories)) {
                $position = 0;
                foreach ($source_directories as $path => $values) {
                    $values['path'] = $path;
                    if (is_array($values['precompile_files'])) {
                        $values['precompile_files'] = implode(PHP_EOL, $values['precompile_files']);
                    }

                    $duplicator->appendChild(
                        self::createSourceDirDuplicatorTemplate($values['type'], $position++, $values)
                    );
                }
            }
        }

        $div->appendChild($duplicator);
        $group->appendChild($div);

        $context['wrapper']->appendChild($group);
    }

    /*
     * Save preferences
     */
    public function savePreferences($context)
    {
        $self_settings = $context['settings'][AP\ID];

        // Create asset directories
        $output_dir = (($self_settings['output_parent_directory'] == 'docroot') ? DOCROOT : WORKSPACE)
            . '/' . trim($self_settings['output_directory'], '/');
        General::realiseDirectory($output_dir);
        General::realiseDirectory(AP\CACHE);

        // Save source_directories if any exist

        if (isset($_POST['asset_pipeline']['source_directories'])) {
            $source_directories_saved = $this->saveSourceDirectories($_POST[AP\ID]['source_directories']);
        } // Nothing posted, so clear source_directories
        else {
            $source_directories_saved = $this->saveSourceDirectories(array());
        }

        // There were errors saving the source_directories.
/*        if ($source_directories_saved === false) {
            $context['errors']['source_directories'] = __('An error occurred while saving the source directories. Make sure the source_directories file, %s, exists and is writable and the directory, %s, is also writable.',
                array(
                    '<code>/manifest/asset_pipeline/source-directories.php</code>',
                    '<code>/manifest/asset_pipeline</code>',
                )
            );
        }
*/
    }

    /*
     * Save  source directories configuration file
     */
    public function saveSourceDirectories($source_directories)
    {
        if (!is_array($source_directories) || empty($source_directories)) return;

        $this->source_directory_errors = array();

        $paths = array();

        foreach ($source_directories as $position => $directory) {
            $errors = array();
            if (empty($directory['name'])) {
                $errors['name'] = __('This is a required field.');
            }

            if (empty($directory['path'])) {
                $errors['path'] = __('This is a required field.');
            } else {
                $path = $directory['path'];
                if (in_array($path, $paths)) {
                    $errors['path'] = __('Path already used.');
                } else {
                    $paths[] = $path;
                }
            }

            if (!empty($errors)) {
                $this->source_directory_errors[$position] = $errors;
            }

            /*if (self::isCodeType($directory['type'])) {
                if (!empty($directory['precompile_files'])) {
                    $to_precompile = array();
                    foreach (explode(PHP_EOL, $directory['precompile_files']) as $file) {
                        $file = trim($file);
                        if ($file) {
                            $to_precompile[] = $file;
                        }
                    }
                    $directory['precompile_files'] = $to_precompile;
                }
            }*/
        }

        if (!empty($this->source_directory_errors)) {
            return false;
        }

        $spacer = "    ";
        $string = "<?php" . PHP_EOL . PHP_EOL . "return array(" . PHP_EOL;
        foreach ($source_directories as $position => $directory) {
            //$string .= PHP_EOL . $spacer . "########";
            $path = trim($directory['path'], '/');
            General::realiseDirectory(WORKSPACE . '/' . $path);
            $type = $directory['type'];
            $string .= PHP_EOL . $spacer . "'$path' => array(";
            $string .= PHP_EOL . $spacer . $spacer . "'name' => '" .
                addslashes($directory['name']) . "',";
            $string .= PHP_EOL . $spacer . $spacer . "'type' => '$type',";

            if (self::isCodeType($type)) {
                $string .= PHP_EOL . $spacer . $spacer . "'precompile_files' => array(";
                $precompile_files = trim($directory['precompile_files']);
                if ($precompile_files) {
                    $files = explode(PHP_EOL, $precompile_files);
                    foreach ($files as $file) {
                        if ($file) {
                            $string .=  "'" . addslashes(trim($file, "\r")) . "',";
                        }
                    }
                }
                $string = rtrim($string, ',') . "),";
            }
            $string .= PHP_EOL . $spacer . ")," . PHP_EOL;
            //$string .= PHP_EOL . $spacer . "########" . PHP_EOL;
        }
        $string .= PHP_EOL .");" . PHP_EOL;

        // Try to write source_directories file
        if (!General::writeFile(AP\SOURCE_DIRECTORIES, $string, Symphony::Configuration()->get('write_mode', 'file'))) {
            return false;
        }

        return true;
    }

    /*
     * Create source directory template
     */
    public static function createSourceDirDuplicatorTemplate(
        $type = '*',
        $position = '-1',
        $values = array(),
        $errors = array()
    )
    {
        $types = array(
            'css' => __('Stylesheets directory'),
            'js' => __('Scripts directory'),
            '*' => __('Non-code directory')
        );

        if (empty($values)) {
            $values = array(
                'position' => null,
                'type' => null,
                'name' => null,
                'path' => null,
                'precompile_files' => null
            );
        }

        //$errors = $this->source_directory_errors;
        // General template settings
        $li = new XMLElement('li');
        $li->setAttribute('class', $position >= 0 ? 'instance expanded' : 'template');
        $li->setAttribute('data-type', 'type-' . $type);
        $header = new XMLElement('header', null, array('data-name' => $types[$type]));
        $label = (!empty($values['name'])) ? $values['name'] : __('New Directory');
        $header->appendChild(new XMLElement('h4', '<strong>' . $label . '</strong> <span class="type">' . $types[$type] . '</span>'));
        $li->appendChild($header);
        $li->appendChild(Widget::Input("asset_pipeline[source_directories][$position][type]", $type, 'hidden'));

        $group = new XMLElement('div', null, array('class' => 'two columns'));

        // Name
        $label = Widget::Label(__('Name'), null, 'column');
        $label->appendChild(Widget::Input("asset_pipeline[source_directories][$position][name]", $values['name']));
        if (isset($errors['name'])) {
            $group->appendChild(Widget::Error($label, $errors['name']));
        } else {
            $group->appendChild($label);
        }

        // Path
        $label = Widget::Label(__('Path<i>Path relative to Workspace</i>'), null, 'column');
        $label->appendChild(Widget::Input("asset_pipeline[source_directories][{$position}][path]", $values['path']));
        if (isset($errors['path'])) {
            $group->appendChild(Widget::Error($label, $errors['path']));
        } else {
            $group->appendChild($label);
        }

        $li->appendChild($group);

        if (self::isCodeType($type)) {
            $label = Widget::Label(
                __('Precompile Files<i>Put each file on a separate line</i>'), null, 'column'
            );
            $label->appendChild(
                Widget::TextArea(
                    "asset_pipeline[source_directories][$position][precompile_files]",
                    4, 80, $values['precompile_files']
                )
            );
            $li->appendChild($label);
        }
        return $li;
    }

    static function isCodeType($file_ext)
    {
        return in_array($file_ext, array('css', 'js'));
    }
}
