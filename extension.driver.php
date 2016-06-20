<?php

// APP_MODE is a Symphony 3 constant.
define_safe('APP_MODE', isset($_GET['mode']) ? $_GET['mode'] : 'frontend');

// Load driver.
require EXTENSIONS . '/asset_pipeline/drivers/driver.' . APP_MODE . '.php';
