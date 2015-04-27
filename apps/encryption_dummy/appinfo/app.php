<?php

$manager = \OC::$server->getEncryptionManager();
$module = new \OCA\Encryption_Dummy\DummyModule();
$manager->registerEncryptionModule('OC_DUMMY_MODULE', 'Dummy Encryption Module', function() use ($module) {
	return $module;
});

