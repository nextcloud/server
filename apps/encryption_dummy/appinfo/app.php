<?php

$manager = \OC::$server->getEncryptionManager();
$module = new \OCA\Encryption_Dummy\DummyModule();
$manager->registerEncryptionModule($module);

