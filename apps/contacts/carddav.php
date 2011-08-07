<?php

// Do not load FS ...
$RUNTIME_NOSETUPFS = true;

require_once('../../lib/base.php');

// Backends
$authBackend = new OC_Connector_Sabre_Auth();
$principalBackend = new OC_Connector_Sabre_Principal();
$carddavBackend   = new OC_Connector_Sabre_CardDAV();

// Root nodes
$nodes = array(
    new Sabre_DAVACL_PrincipalCollection($principalBackend),
    new Sabre_CardDAV_AddressBookRoot($principalBackend, $carddavBackend),
);

// Fire up server
$server = new Sabre_DAV_Server($nodes);
$server->setBaseUri($WEBROOT.'/apps/contacts/carddav.php');
// Add plugins
$server->addPlugin(new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud'));
$server->addPlugin(new Sabre_CardDAV_Plugin());
$server->addPlugin(new Sabre_DAVACL_Plugin());

// And off we go!
$server->exec();
