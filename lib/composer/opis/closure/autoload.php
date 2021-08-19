<?php
/* ===========================================================================
 * Copyright (c) 2018-2021 Zindex Software
 *
 * Licensed under the MIT License
 * =========================================================================== */

require_once 'functions.php';

spl_autoload_register(function($class){
   
    $class = ltrim($class, '\\');
    $dir = __DIR__ . '/src';
    $namespace = 'Opis\Closure';
    
    if(strpos($class, $namespace) === 0)
    {
        $class = substr($class, strlen($namespace));
        $path = '';
        if(($pos = strripos($class, '\\')) !== FALSE)
        {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
        }
        $path .= str_replace('_', '/', $class) . '.php';
        $dir .= '/' . $path;
        
        if(file_exists($dir))
        {
            include $dir;
            return true;
        }
        
        return false;
    }
    
    return false;

});
