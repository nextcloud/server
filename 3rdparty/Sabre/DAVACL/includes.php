<?php

/**
 * Sabre_DAVACL includes file
 *
 * Including this file will automatically include all files from the
 * Sabre_DAVACL package.
 *
 * This often allows faster loadtimes, as autoload-speed is often quite slow.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

// Begin includes
include __DIR__ . '/AbstractPrincipalCollection.php';
include __DIR__ . '/Exception/AceConflict.php';
include __DIR__ . '/Exception/NeedPrivileges.php';
include __DIR__ . '/Exception/NoAbstract.php';
include __DIR__ . '/Exception/NotRecognizedPrincipal.php';
include __DIR__ . '/Exception/NotSupportedPrivilege.php';
include __DIR__ . '/IACL.php';
include __DIR__ . '/IPrincipal.php';
include __DIR__ . '/IPrincipalBackend.php';
include __DIR__ . '/Plugin.php';
include __DIR__ . '/Principal.php';
include __DIR__ . '/PrincipalBackend/PDO.php';
include __DIR__ . '/PrincipalCollection.php';
include __DIR__ . '/Property/Acl.php';
include __DIR__ . '/Property/AclRestrictions.php';
include __DIR__ . '/Property/CurrentUserPrivilegeSet.php';
include __DIR__ . '/Property/Principal.php';
include __DIR__ . '/Property/SupportedPrivilegeSet.php';
include __DIR__ . '/Version.php';
// End includes
