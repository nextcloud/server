<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Settings;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Service\BackendService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Encryption\IManager;
use OCP\IURLGenerator;
use OCP\Util;

trait CommonSettingsTrait {
	private BackendService $backendService;
	private IManager $encryptionManager;
	private IInitialState $initialState;
	private IURLGenerator $urlGenerator;
	private GlobalAuth $globalAuth;

	private int $visibility;
	private ?string $userId = null;
	/** @var Backend[]|null */
	private ?array $backends = null;

	/**
	 * Set the initial state for the user / admin settings
	 */
	protected function setInitialState() {
		$allowUserMounting = $this->backendService->isUserMountingAllowed();
		$isAdmin = $this->visibility === BackendService::VISIBILITY_ADMIN;
		$canCreateMounts = $isAdmin || $allowUserMounting;

		$this->initialState->provideInitialState('settings', [
			/** Link to external files documentation */
			'docUrl' => $this->urlGenerator->linkToDocs('admin-external-storage'),
			/** List of backend dependency or missing module issues to be shown on the fronend */
			'dependencyIssues' => $canCreateMounts ? $this->dependencyMessage() : null,
			/** Is this the admin settings or just user settings */
			'isAdmin' => $isAdmin,
			'hasEncryption' => $this->encryptionManager->isEnabled(),
		]);

		$this->initialState->provideInitialState(
			'global-credentials',
			array_merge(
				/** User ID of the credentials - empty string for global admin defined */
				['uid' => $this->userId ?? '' ],
				/** username and password configured */
				$this->globalAuth->getAuth($this->userId ?? ''),
			),
		);

		$this->initialState->provideInitialState(
			'allowedBackends',
			array_map(fn (Backend $backend) => $backend->getIdentifier(), $this->getAvailableBackends()),
		);
		$this->initialState->provideInitialState(
			'backends',
			array_values($this->backendService->getAvailableBackends()),
		);
		$this->initialState->provideInitialState(
			'authMechanisms',
			array_values($this->backendService->getAuthMechanisms()),
		);
	}

	/**
	 * Load the frontend script including the custom backend dependencies
	 */
	protected function loadScriptsAndStyles() {
		Util::addStyle('files_external', 'init_settings');
		Util::addInitScript('files_external', 'init_settings');

		Util::addScript('files_external', 'settings');
		Util::addStyle('files_external', 'settings');

		// load custom JS
		foreach ($this->backendService->getAvailableBackends() as $backend) {
			foreach ($backend->getCustomJs() as $script) {
				Util::addStyle('files_external', $script);
				Util::addScript('files_external', $script);
			}
		}

		foreach ($this->backendService->getAuthMechanisms() as $authMechanism) {
			foreach ($authMechanism->getCustomJs() as $script) {
				Util::addStyle('files_external', $script);
				Util::addScript('files_external', $script);
			}
		}
	}

	/**
	 * Get backend dependency error messages
	 * @return array{messages: string[], modules: array<string,string[]>}
	 */
	private function dependencyMessage(): array {
		$messages = [];
		$dependencyGroups = [];

		// Try all backends and check their dependencies
		foreach ($this->getAvailableBackends() as $backend) {
			foreach ($backend->checkDependencies() as $dependency) {
				$dependencyMessage = $dependency->getMessage();
				if ($dependencyMessage !== null) {
					// There is a custom message so we use that
					$messages[] = $dependencyMessage;
				} else {
					// No custom message so just add the dependency and add the backend to the list of dependants
					$dependencyGroups[$dependency->getDependency()][] = $backend;
				}
			}
		}

		$backendDisplayName = fn (Backend $backend) => $backend->getText();

		// Create a mapping [ 'dependency' => ['backendName1', ... ]]
		$missingModules = array_map(fn (array $dependants) => array_map($backendDisplayName, $dependants), $dependencyGroups);
		return [
			'messages' => $messages,
			'modules' => $missingModules,
		];
	}

	private function getAvailableBackends(): array {
		if ($this->backends === null) {
			$backends = $this->backendService->getAvailableBackends();
			if ($this->visibility === BackendService::VISIBILITY_PERSONAL) {
				$backends = array_filter($backends, $this->backendService->isAllowedUserBackend(...));
			}
			$this->backends = array_values($backends);
		}
		return $this->backends;
	}
}
