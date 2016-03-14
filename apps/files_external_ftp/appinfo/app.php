<?php

\OC::$server->getEventDispatcher()->addListener(
	'OCA\\Files_External::loadAdditionalBackends', function($event) {
		$l10n = \OC::$server->getL10N('files_external_ftp');
		$backendService = \OC::$server->getStoragesBackendService();
		$extContainer = \OC_Mount_Config::$app->getContainer();

		$backendService->registerBackend(new \OCA\Files_External_FTP\Backend(
			$l10n,
			$extContainer->query('OCA\Files_External\Lib\Auth\Password\Password')
		));
	}
);
