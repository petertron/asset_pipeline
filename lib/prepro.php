<?php

namespace asset_pipeline\prepro;

function getPreproIfExists($name)
{
    $prepro = __NAMESPACE__.'\\'.$name;
    return class_exists($prepro) ? $prepro : null;
}
