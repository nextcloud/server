<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Controller;

use OC\Setup;
use OCP\Util;
use Psr\Log\LoggerInterface;

class SetupController {
	private string $autoConfigFile;

	public function __construct(
		protected Setup $setupHelper,
		protected LoggerInterface $logger,
	) {
		$this->autoConfigFile = \OC::$configDir . 'autoconfig.php';
	}

	public function run(array $post): void {
		// Check for autosetup:
		$post = $this->loadAutoConfig($post);
		$opts = $this->setupHelper->getSystemInfo();

		// convert 'abcpassword' to 'abcpass'
		if (isset($post['adminpassword'])) {
			$post['adminpass'] = $post['adminpassword'];
		}
		if (isset($post['dbpassword'])) {
			$post['dbpass'] = $post['dbpassword'];
		}

		if (!$this->setupHelper->canInstallFileExists()) {
			$this->displaySetupForbidden();
			return;
		}

		if (isset($post['install']) and $post['install'] == 'true') {
			// We have to launch the installation process :
			$e = $this->setupHelper->install($post);
			$errors = ['errors' => $e];

			if (count($e) > 0) {
				$options = array_merge($opts, $post, $errors);
				$this->display($options);
			} else {
				$this->finishSetup();
			}
		} else {
			$options = array_merge($opts, $post);
			$this->display($options);
		}
	}

	private function displaySetupForbidden(): void {
		\OC_Template::printGuestPage('', 'installation_forbidden');
	}

	public function display($post): void {
		$defaults = [
			'adminlogin' => '',
			'adminpass' => '',
			'dbuser' => '',
			'dbpass' => '',
			'dbname' => '',
			'dbtablespace' => '',
			'dbhost' => 'localhost',
			'dbtype' => '',
		];
		$parameters = array_merge($defaults, $post);

		Util::addStyle('server', null);

		// include common nextcloud webpack bundle
		Util::addScript('core', 'common');
		Util::addScript('core', 'main');
		Util::addTranslations('core');

		\OC_Template::printGuestPage('', 'installation', $parameters);
	}

	private function finishSetup(): void {
		if (file_exists($this->autoConfigFile)) {
			unlink($this->autoConfigFile);
		}
		\OC::$server->getIntegrityCodeChecker()->runInstanceVerification();

		if ($this->setupHelper->shouldRemoveCanInstallFile()) {
			\OC_Template::printGuestPage('', 'installation_incomplete');
		}

		header('Location: ' . \OC::$server->getURLGenerator()->getAbsoluteURL('index.php/core/apps/recommended'));
		exit();
	}

	public function loadAutoConfig(array $post): array {
		if (file_exists($this->autoConfigFile)) {
			$this->logger->info('Autoconfig file found, setting up Nextcloud…');
			$AUTOCONFIG = [];
			include $this->autoConfigFile;
			$post = array_merge($post, $AUTOCONFIG);
		}

		$dbIsSet = isset($post['dbtype']);
		$directoryIsSet = isset($post['directory']);
		$adminAccountIsSet = isset($post['adminlogin']);

		if ($dbIsSet and $directoryIsSet and $adminAccountIsSet) {
			$post['install'] = 'true';
		}
		$post['dbIsSet'] = $dbIsSet;
		$post['directoryIsSet'] = $directoryIsSet;

		return $post;
	}
}
