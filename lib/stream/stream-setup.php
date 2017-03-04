<?php

require 'class.Stream.php';
require 'class.APStream.php';

stream_wrapper_register('ap', 'AssetPipeline\APStream');
stream_wrapper_register('ap.url', 'AssetPipeline\APStream');
stream_wrapper_register('ap.filename', 'AssetPipeline\APStream');
