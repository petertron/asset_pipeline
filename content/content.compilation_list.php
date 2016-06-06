<?php

require_once TOOLKIT . '/class.administrationpage.php';

use asset_pipeline\ap;

class contentExtensionAsset_pipelineCompilation_list extends AdministrationPage
{
    public function __construct()
    {
        parent::__construct();
        $_GET['symphony-page'] = '/blueprints/asset-compilation-list/';
    }

    public function __viewIndex()
    {
        $this->setPageType('table');
        $this->addScriptToHead(URL . '/extensions/asset_pipeline/assets/asset-compilation-list.js', 400, false);

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Asset Compilation List'), __('Symphony'))));

        $view = new XMLElement(
            'div',
            Widget::Label(__('View'), null, 'apply-label-left'),
            array('class' => 'apply actions')
        );

        $this->appendSubheading(
            __('Asset Compilation List')
        );

        $this->insertAction(
            new XMLElement(
                'button',
                __('Show File Adder'),
                array(
                    'id' => 'show-file-adder',
                    'class' => 'button',
                )
            )
        );
        $this->insertAction(
            new XMLElement(
                'button',
                __('Hide File Adder'),
                array(
                    'id' => 'hide-file-adder',
                    'class' => 'button',
                    'style' => 'display: none'
                )
            )
        );
        $this->insertAction(
            new XMLElement(
                'button',
                __("Toggle 'With' Mode"),
                array(
                    'id' => 'toggle-with-mode',
                    'class' => 'button',
                    'title' => __("Toggle 'With Selected...'/'With All...'")
                )
            )
        );

        $source_files = ap\getSourceFiles();

        $compilation_list = array();
        foreach (ap\getCompilationList() as $subpath => $base) {
            $compilation_list[] = $base . '/' . $subpath;
        }
        sort($compilation_list);

        // Generate file-adder section

        $files = $compilation_list ? array_diff($source_files, $compilation_list) : $source_files;
        $options = array();
        foreach ($files as $file) {
            $options[] = array($file, false, $file);
        }

        $fieldset = new XMLElement(
            'fieldset',
            null,
            array('id' => 'file-adder', 'style' => 'margin: 8px 18px 0 18px; display: none;')
        );
        $fieldset->appendChild(
            Widget::Label(
                __('Files Available'),
                Widget::Select('items[]', $options, array('id' => 'files-available', 'multiple' => 'multiple')),
                null
            )
        );
        $fieldset->appendChild(
            new XMLElement(
                'button',
                __('Add Selected Files'),
                array('id' => 'add-files', 'class' => 'button', 'type' => 'button')
            )
        );
        $this->Form->appendChild($fieldset);

        // Files table.

        $files_precompiled = ap\getFilesPrecompiled();
        $table_rows = array();

        include EXTENSIONS . '/asset_pipeline/content/comp_list_table_rows.php';

/*        if ($compilation_list) {
            foreach ($compilation_list as $file) {
                $mtime = ap\getFileMTime(ap\SOURCE_DIR . '/' . $file);
                $column1 = Widget::TableData($file);
                $column1->appendChild(
                    Widget::Input("items[$file]", 'on', 'checkbox')
                );
                $table_row = Widget::TableRow(
                    array(
                        //'<input name="items[0]" type="checkbox" value="on"/>'),
                        $column1,
                        Widget::TableData($mtime ? $mtime : __('<span class="inactive">Not found</span>')),
                        Widget::TableData('<span class="inactive">-</span>')
                    )
                );
                if (!$mtime) {
                    $table_row->setAttribute('class', 'status-error');
                }
                $table_rows[] = $table_row;
            }
        } else {
            $table_rows[] = Widget::TableRow(
                array(Widget::TableData(__('None found.'), 'inactive', null, 4))
            );
        }
*/
        $this->Form->appendChild(
            Widget::Table(
                Widget::TableHead(
                    array(
                        array(__('File'), 'col'),
                        //array(__('Subdirectory'),
                        array(__('Last Modified'), 'col'),
                        array(__('Precompiled'), 'col'),
                    )
                ),
                null,
                Widget::TableBody($table_rows, null, 'files-added'),
                'selectable',
                null,
                array('role' => 'directory', 'data-interactive' => 'data-interactive')
            )
        );

        $version = new XMLElement('p', 'Symphony ' . Symphony::Configuration()->get('version', 'symphony'), array(
            'id' => 'version'
        ));
        $this->Form->appendChild($version);

        $app = Widget::Apply(array(
            array('', true, __('With All...')),
            array('compile', false, __('Compile')),
            array('delete-compiled', false, __('Delete Compiled File')),
            array('remove', false, __('Remove From List'))
        ));
        $actions = new XMLElement('div', null, array('class' => 'actions'));

        $actions->appendChild(
            Widget::Apply(array(
                array('', true, __('With Selected...')),
                array('compile', false, __('Compile')),
                array('delete-compiled', false, __('Delete Compiled File')),
                array('remove', false, __('Remove From List'))
            ))
        );
        $this->Form->appendChild($actions);
    }

}

/*$actions->appendChild(
    new XMLElement(
        'span', '<input name="setting[with-all]" value="yes" type="checkbox" style="position: relative; vertical-align: middle;"/>Apply to all entries', array('style' => 'display: inline-block; margin: 3px 24px 0 0;')
    )
);*/
