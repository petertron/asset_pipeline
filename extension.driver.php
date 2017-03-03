<?php

require 'lib/defines.php';
require 'lib/functions.php';

define_safe('APP_MODE', isset($_GET['mode']) ? $_GET['mode'] : 'frontend');

require 'drivers/driver.' . APP_MODE . '.php';
