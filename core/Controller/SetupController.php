<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Controller;

use OC\IntegrityCheck\Checker;
use OC\Setup;
use OCP\IInitialStateService;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Template\ITemplateManager;
use OCP\Util;
use Psr\Log\LoggerInterface;

class SetupController {
	private string $autoConfigFile;

	public function __construct(
		protected Setup $setupHelper,
		protected LoggerInterface $logger,
		protected ITemplateManager $templateManager,
		protected IInitialStateService $initialStateService,
		protected IURLGenerator $urlGenerator,
		protected ServerVersion $serverVersion,
	) {
		$this->autoConfigFile = \OC::$configDir . 'autoconfig.php';
	}

	public function run(
		#[\SensitiveParameter]
		array $post,
	): void {
		if (!$this->setupHelper->canInstallFileExists()) {
			$this->displaySetupForbidden();
			return;
		}

		// Check for autosetup
		$post = $this->loadAutoConfig($post);

		// Normalize incoming password field names
		if (isset($post['adminpassword'])) {
			$post['adminpass'] = $post['adminpassword'];
		}

		if (isset($post['dbpassword'])) {
			$post['dbpass'] = $post['dbpassword'];
		}

		if (($post['install'] ?? null) === 'true') {
			$errors = $this->setupHelper->install($post);

			if ($errors === []) {
				$this->finishSetup();
				return;
			}

			$post['errors'] = $errors;
		}

		$this->display(array_merge($this->setupHelper->getSystemInfo(), $post));
	}

	private function displaySetupForbidden(): void {
		$this->templateManager->printGuestPage('', 'installation_forbidden');
	}

	public function display(
		#[\SensitiveParameter]
		array $post,
	): void {
		$defaults = [
			'adminlogin' => '',
			'adminpass' => '',
			'dbuser' => '',
			'dbpass' => '',
			'dbname' => '',
			'dbtablespace' => '',
			'dbhost' => 'localhost',
			'dbtype' => '',
			'dbsslmode' => '',
			'dbsslca' => '',
			'dbsslcert' => '',
			'dbsslkey' => '',
			'dbsslcrl' => '',
			'dbsslnoverify' => false,
			'hasAutoconfig' => false,
			'serverRoot' => \OC::$SERVERROOT,
			'version' => implode('.', $this->serverVersion->getVersion()),
			'versionstring' => $this->serverVersion->getVersionString(),
		];

		$parameters = array_merge($defaults, $post);

		Util::addStyle('server', null);

		Util::addScript('core', 'main');
		Util::addStyle('core', 'main');
		Util::addScript('core', 'install');
		Util::addStyle('core', 'install');
		Util::addTranslations('core');

		$this->initialStateService->provideInitialState('core', 'config', $parameters);
		$this->initialStateService->provideInitialState('core', 'data', false);
		$this->initialStateService->provideInitialState('core', 'links', [
			'adminInstall' => $this->urlGenerator->linkToDocs('admin-install'),
			'adminSourceInstall' => $this->urlGenerator->linkToDocs('admin-source_install'),
			'adminDBConfiguration' => $this->urlGenerator->linkToDocs('admin-db-configuration'),
		]);

		$this->templateManager->printGuestPage('', 'installation');
	}

	private function finishSetup(): void {
		if (file_exists($this->autoConfigFile)) {
			unlink($this->autoConfigFile);
		}

		Server::get(Checker::class)->runInstanceVerification();

		if ($this->setupHelper->shouldRemoveCanInstallFile()) {
			$this->templateManager->printGuestPage('', 'installation_incomplete');
			return;
		}

		header('Location: ' . $this->urlGenerator->getAbsoluteURL('index.php/core/apps/recommended'));
		exit;
	}

	/**
	 * @psalm-taint-escape file we trust file path given in POST for setup
	 */
	public function loadAutoConfig(
		#[\SensitiveParameter]
		array $post,
	): array {
		if (file_exists($this->autoConfigFile)) {
			$this->logger->info('Autoconfig file found, setting up Nextcloud…');

			$AUTOCONFIG = [];
			include $this->autoConfigFile;

			$post['hasAutoconfig'] = $AUTOCONFIG !== [];
			$post = array_merge($post, $AUTOCONFIG);
		}

		if (isset($post['dbtype'], $post['directory'], $post['adminlogin'])) {
			$post['install'] = 'true';
		}

		return $post;
	}
}
