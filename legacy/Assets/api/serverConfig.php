<?php

function isModRewriteEnabled()
{
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        return in_array('mod_rewrite', $modules) || in_array('rewrite_module', $modules);
    }
    return function_exists('apache_getenv') || isset($_SERVER['REDIRECT_STATUS']);
}
