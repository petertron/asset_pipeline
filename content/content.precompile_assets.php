<?php

require_once TOOLKIT . '/class.administrationpage.php';

class contentExtensionAsset_pipelinePrecompile_assets extends AdministrationPage
{
    public function __construct()
    {
        parent::__construct();
        $_GET['symphony-page'] = 'system/precompile-assets/';
    }

    public function __viewIndex()
    {
        Symphony::ExtensionManager()->notifyMembers('ModifySymphonyLauncher', '/all/');
        $this->setPageType('table');
        $this->addScriptToHead(URL . '/extensions/asset_pipeline/assets/precompile-assets.js', 400, false);

        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Precompile Assets'), __('Symphony'))));
        $this->appendSubheading(
            __('Precompile Assets')
        );

        $fieldset = new XMLElement(
            'fieldset',
            null,
            array('id' => 'file-adder', 'style' => 'margin: 8px 18px 0 18px;')
        );

        $fieldset->appendChild(
            new XMLElement(
                'button',
                __('Compile'),
                array('id' => 'compile', 'class' => 'button', 'type' => 'button')
            )
        );
        $this->Form->appendChild($fieldset);
        $this->Form->appendChild(
            new XMLElement(
                'div',
                null,
                array('id' => 'compilation-log', 'style' => 'margin: 8px 18px 0 18px;')
            )
        );
    }
}

