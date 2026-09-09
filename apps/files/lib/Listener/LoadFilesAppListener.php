<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Listener;

use OC\Files\FilenameValidator;
use OCA\Files\ConfigLexicon;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files\Event\LoadFilesApp;
use OCA\Files\Event\LoadSearchPlugins;
use OCA\Files\Event\LoadSidebar;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Collaboration\Resources\LoadAdditionalScriptsEvent as ResourcesLoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Template\ITemplateManager;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Util;

/**
 * Loads the Files app's frontend scripts and generic initial state so it can
 * be rendered on any page, be it the Files app's own `files.view.index`
 * route or a foreign page calling `OCP.Files.renderFilesApp()`.
 *
 * @template-implements IEventListener<LoadFilesApp>
 */
class LoadFilesAppListener implements IEventListener {
	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IInitialState $initialState,
		private IConfig $config,
		private IUserSession $userSession,
		private UserConfig $userConfig,
		private ViewConfig $viewConfig,
		private FilenameValidator $filenameValidator,
		private IRegistry $twoFactorRegistry,
		private IAppConfig $appConfig,
		private ITemplateManager $templateManager,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof LoadFilesApp)) {
			return;
		}

		Util::addInitScript('files', 'init');
		Util::addScript('files', 'main');

		$user = $this->userSession->getUser();
		$userId = $user->getUID();

		$this->initialState->provideInitialState('config', $this->userConfig->getConfigs());
		$this->initialState->provideInitialState('viewConfigs', $this->viewConfig->getConfigs());
		$this->initialState->provideInitialState('recent_limit', $this->appConfig->getAppValueInt(ConfigLexicon::RECENT_LIMIT, 100));
		// Not yet consumed by the frontend, provided for future implementation
		$this->initialState->provideInitialState('group_recent_files', $this->appConfig->getAppValueBool(ConfigLexicon::GROUP_RECENT_FILES, false));
		$this->initialState->provideInitialState('recent_files_group_mime_types', $this->appConfig->getAppValueArray(ConfigLexicon::RECENT_FILES_GROUP_MIME_TYPES, []));
		$this->initialState->provideInitialState('recent_files_group_timespan_minutes', $this->appConfig->getAppValueInt(ConfigLexicon::RECENT_FILES_GROUP_TIMESPAN_MINUTES, 2));

		// File sorting user config
		$filesSortingConfig = json_decode($this->config->getUserValue($userId, 'files', 'files_sorting_configs', '{}'), true);
		$this->initialState->provideInitialState('filesSortingConfig', $filesSortingConfig);

		// Forbidden file characters (deprecated use capabilities)
		// TODO: Remove with next release of `@nextcloud/files`
		$forbiddenCharacters = $this->filenameValidator->getForbiddenCharacters();
		$this->initialState->provideInitialState('forbiddenCharacters', $forbiddenCharacters);

		$this->eventDispatcher->dispatchTyped(new LoadAdditionalScriptsEvent());
		$this->eventDispatcher->dispatchTyped(new ResourcesLoadAdditionalScriptsEvent());
		$this->eventDispatcher->dispatchTyped(new LoadSidebar());
		$this->eventDispatcher->dispatchTyped(new LoadSearchPlugins());
		// Load Viewer scripts
		if (class_exists(LoadViewer::class)) {
			$this->eventDispatcher->dispatchTyped(new LoadViewer());
		}

		$this->initialState->provideInitialState('templates_enabled', true);
		$this->initialState->provideInitialState('templates_path', $this->templateManager->hasTemplateDirectory() ? $this->templateManager->getTemplatePath() : false);
		$this->initialState->provideInitialState('templates', $this->templateManager->listCreators());
		$this->initialState->provideInitialState('localClientEnabled', $this->appConfig->getAppValueBool(ConfigLexicon::LOCAL_CLIENT_INTEGRATION));

		$isTwoFactorEnabled = false;
		foreach ($this->twoFactorRegistry->getProviderStates($user) as $providerId => $providerState) {
			if ($providerId !== 'backup_codes' && $providerState === true) {
				$isTwoFactorEnabled = true;
			}
		}

		$this->initialState->provideInitialState('isTwoFactorEnabled', $isTwoFactorEnabled);
	}
}
