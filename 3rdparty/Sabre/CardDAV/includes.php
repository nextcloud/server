<?php

/**
 * Sabre_CardDAV includes file
 *
 * Including this file will automatically include all files from the
 * Sabre_CardDAV package.
 *
 * This often allows faster loadtimes, as autoload-speed is often quite slow.
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

// Begin includes
include __DIR__ . '/AddressBookQueryParser.php';
include __DIR__ . '/AddressBookRoot.php';
include __DIR__ . '/Backend/Abstract.php';
include __DIR__ . '/Backend/PDO.php';
include __DIR__ . '/IAddressBook.php';
include __DIR__ . '/ICard.php';
include __DIR__ . '/IDirectory.php';
include __DIR__ . '/Plugin.php';
include __DIR__ . '/Property/SupportedAddressData.php';
include __DIR__ . '/UserAddressBooks.php';
include __DIR__ . '/VCFExportPlugin.php';
include __DIR__ . '/Version.php';
include __DIR__ . '/AddressBook.php';
include __DIR__ . '/Card.php';
// End includes
