<?php

/**
 * SabreDAV's autoloader
 *
 * This file is kept for backwards compatibility purposes.
 * SabreDAV now uses the composer autoloader.
 *
 * You should stop including this file, and include 'vendor/autoload.php'
 * instead.
 *
 * @package Sabre
 * @subpackage DAV
 * @deprecated Will be removed in a future version!
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * We are assuming that the composer autoloader is just 2 directories up.
 *
 * This is not the case when sabredav is installed as a dependency. But, in
 * those cases it's not expected that people will look for this file anyway.
 */

require __DIR__ . '/../../vendor/autoload.php';
