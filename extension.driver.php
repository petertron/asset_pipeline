<?php

require EXTENSIONS . '/asset_pipeline/lib/defines.php';

// APP_MODE is a Symphony 3 constant.
define_safe('APP_MODE', isset($_GET['mode']) ? $_GET['mode'] : 'frontend');

require EXTENSIONS . '/asset_pipeline/drivers/driver.' . APP_MODE . '.php';
