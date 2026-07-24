<?php

declare(strict_types=1);
/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\ISubAdmin;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

/** @template-implements IEventListener<LoadAdditionalEntriesEvent> */
class LoadAdditionalEntriesListener implements IEventListener {

	public function __construct(
		private readonly IConfig $config,
		private readonly IL10N $l10n,
		private readonly INavigationManager $navigationManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly ISubAdmin $subAdmin,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalEntriesEvent)) {
			return;
		}

		$this->registerNavigationEntries();
	}

	/**
	 * Registers the navigation entries for the user settings.
	 * Needed as some entries are dynamic and thus we cannot use the appinfo/info.xml
	 *
	 * Registers the following entries:
	 * - Appearance and accessibility
	 * - Personal settings (named "Settings" for non-admins)
	 * - Accounts (only for subadmins)
	 * - Help & privacy (conditionally enabled based on config)
	 */
	private function registerNavigationEntries(): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}

		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		$isSubAdmin = $this->subAdmin->isSubAdmin($user);

		// Accessibility settings - the URL is dynamic (route parameters) which is currently not supported by appinfo.xml
		$this->navigationManager->add([
			'type' => 'settings',
			'id' => 'accessibility_settings',
			'order' => 2,
			'href' => $this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'theming']),
			'name' => $this->l10n->t('Appearance and accessibility'),
			'icon' => $this->urlGenerator->imagePath('theming', 'accessibility-dark.svg'),
		]);

		// Personal settings - this entry is dynamic so we cannot use appinfo
		$this->navigationManager->add([
			'type' => 'settings',
			'id' => 'settings_personal',
			'order' => 3,
			'href' => $this->urlGenerator->linkToRoute('settings.PersonalSettings.index'),
			'name' => $isAdmin
				? $this->l10n->t('Personal settings')
				: $this->l10n->t('Settings'),
			'icon' => $isAdmin
				? $this->urlGenerator->imagePath('settings', 'personal.svg')
				: $this->urlGenerator->imagePath('settings', 'admin.svg'),
		]);

		if ($isAdmin) {
			$this->navigationManager->add([
				'type' => 'settings',
				'id' => 'settings_administration',
				'order' => 4,
				'href' => $this->urlGenerator->linkToRoute('settings.adminSettings.index'),
				'name' => $this->l10n->t('Administration settings'),
				'icon' => $this->urlGenerator->imagePath('settings', 'admin.svg'),
			]);
		}

		// User management is conditionally enabled for subadmins, but appinfo currently only supports full admins
		if ($isSubAdmin) {
			$this->navigationManager->add([
				'type' => 'settings',
				'id' => 'core_users',
				'order' => 6,
				'href' => $this->urlGenerator->linkToRoute('settings.Users.usersList'),
				'name' => $this->l10n->t('Accounts'),
				'icon' => $this->urlGenerator->imagePath('settings', 'users.svg'),
			]);
		}

		// conditionally enabled navigation entry
		if ($this->config->getSystemValueBool('knowledgebaseenabled', true)) {
			$this->navigationManager->add([
				'type' => 'settings',
				'id' => 'help',
				'order' => 99998,
				'href' => $this->urlGenerator->linkToRoute('settings.Help.help'),
				'name' => $this->l10n->t('Help & privacy'),
				'icon' => $this->urlGenerator->imagePath('settings', 'help.svg'),
			]);
		}
	}

}
