<?php

/**
 * SabreDAV's PHP autoloader
 *
 * If you love the autoloader, and don't care as much about performance, this
 * file register a new autoload function using spl_autoload_register.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * @param string $className
 * @return void
 */
function Sabre_autoload($className) {

    if(strpos($className,'Sabre_')===0) {

        include dirname(__FILE__) . '/' . str_replace('_','/',substr($className,6)) . '.php';

    }

}

spl_autoload_register('Sabre_autoload');

