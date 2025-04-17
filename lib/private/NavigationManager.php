<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use InvalidArgumentException;
use OC\App\AppManager;
use OC\Group\Manager;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
use Psr\Log\LoggerInterface;

/**
 * Manages the ownCloud navigation
 */

class NavigationManager implements INavigationManager {
	protected $entries = [];
	protected $closureEntries = [];
	protected $activeEntry;
	protected $unreadCounters = [];

	/** @var bool */
	protected $init = false;
	/** @var IAppManager|AppManager */
	protected $appManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IFactory */
	private $l10nFac;
	/** @var IUserSession */
	private $userSession;
	/** @var Manager */
	private $groupManager;
	/** @var IConfig */
	private $config;
	/** User defined app order (cached for the `add` function) */
	private array $customAppOrder;
	private LoggerInterface $logger;

	public function __construct(
		IAppManager $appManager,
		IURLGenerator $urlGenerator,
		IFactory $l10nFac,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IConfig $config,
		LoggerInterface $logger,
		protected IEventDispatcher $eventDispatcher,
	) {
		$this->appManager = $appManager;
		$this->urlGenerator = $urlGenerator;
		$this->l10nFac = $l10nFac;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function add($entry) {
		if ($entry instanceof \Closure) {
			$this->closureEntries[] = $entry;
			return;
		}
		$this->init();

		$id = $entry['id'];

		$entry['active'] = false;
		$entry['unread'] = $this->unreadCounters[$id] ?? 0;
		if (!isset($entry['icon'])) {
			$entry['icon'] = '';
		}
		if (!isset($entry['classes'])) {
			$entry['classes'] = '';
		}
		if (!isset($entry['type'])) {
			$entry['type'] = 'link';
		}

		if ($entry['type'] === 'link') {
			// app might not be set when using closures, in this case try to fallback to ID
			if (!isset($entry['app']) && $this->appManager->isEnabledForUser($id)) {
				$entry['app'] = $id;
			}

			// Set order from user defined app order
			$entry['order'] = (int)($this->customAppOrder[$id]['order'] ?? $entry['order'] ?? 100);
		}

		$this->entries[$id] = $entry;

		// Needs to be done after adding the new entry to account for the default entries containing this new entry.
		$this->updateDefaultEntries();
	}

	private function updateDefaultEntries() {
		$defaultEntryId = $this->getDefaultEntryIdForUser($this->userSession->getUser(), false);
		foreach ($this->entries as $id => $entry) {
			if ($entry['type'] === 'link') {
				$this->entries[$id]['default'] = $id === $defaultEntryId;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getAll(string $type = 'link'): array {
		$this->init();
		foreach ($this->closureEntries as $c) {
			$this->add($c());
		}
		$this->closureEntries = [];

		$result = $this->entries;
		if ($type !== 'all') {
			$result = array_filter($this->entries, function ($entry) use ($type) {
				return $entry['type'] === $type;
			});
		}

		return $this->proceedNavigation($result, $type);
	}

	/**
	 * Sort navigation entries default app is always sorted first, then by order, name and set active flag
	 *
	 * @param array $list
	 * @return array
	 */
	private function proceedNavigation(array $list, string $type): array {
		uasort($list, function ($a, $b) {
			if (($a['default'] ?? false) xor ($b['default'] ?? false)) {
				// Always sort the default app first
				return ($a['default'] ?? false) ? -1 : 1;
			} elseif (isset($a['order']) && isset($b['order'])) {
				// Sort by order
				return ($a['order'] < $b['order']) ? -1 : 1;
			} elseif (isset($a['order']) || isset($b['order'])) {
				// Sort the one that has an order property first
				return isset($a['order']) ? -1 : 1;
			} else {
				// Sort by name otherwise
				return ($a['name'] < $b['name']) ? -1 : 1;
			}
		});

		if ($type === 'all' || $type === 'link') {
			// There might be the case that no default app was set, in this case the first app is the default app.
			// Otherwise the default app is already the ordered first, so setting the default prop will make no difference.
			foreach ($list as $index => &$navEntry) {
				if ($navEntry['type'] === 'link') {
					$navEntry['default'] = true;
					break;
				}
			}
			unset($navEntry);
		}

		$activeEntry = $this->getActiveEntry();
		if ($activeEntry !== null) {
			foreach ($list as $index => &$navEntry) {
				if ($navEntry['id'] == $activeEntry) {
					$navEntry['active'] = true;
				} else {
					$navEntry['active'] = false;
				}
			}
			unset($navEntry);
		}

		return $list;
	}


	/**
	 * removes all the entries
	 */
	public function clear($loadDefaultLinks = true) {
		$this->entries = [];
		$this->closureEntries = [];
		$this->init = !$loadDefaultLinks;
	}

	/**
	 * @inheritDoc
	 */
	public function setActiveEntry($appId) {
		$this->activeEntry = $appId;
	}

	/**
	 * @inheritDoc
	 */
	public function getActiveEntry() {
		return $this->activeEntry;
	}

	private function init() {
		if ($this->init) {
			return;
		}
		$this->init = true;

		$l = $this->l10nFac->get('lib');
		if ($this->config->getSystemValueBool('knowledgebaseenabled', true)) {
			$this->add([
				'type' => 'settings',
				'id' => 'help',
				'order' => 99998,
				'href' => $this->urlGenerator->linkToRoute('settings.Help.help'),
				'name' => $l->t('Help & privacy'),
				'icon' => $this->urlGenerator->imagePath('settings', 'help.svg'),
			]);
		}

		if ($this->userSession->isLoggedIn()) {
			// Profile
			$this->add([
				'type' => 'settings',
				'id' => 'profile',
				'order' => 1,
				'href' => $this->urlGenerator->linkToRoute(
					'profile.ProfilePage.index',
					['targetUserId' => $this->userSession->getUser()->getUID()],
				),
				'name' => $l->t('View profile'),
			]);

			// Accessibility settings
			if ($this->appManager->isEnabledForUser('theming', $this->userSession->getUser())) {
				$this->add([
					'type' => 'settings',
					'id' => 'accessibility_settings',
					'order' => 2,
					'href' => $this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'theming']),
					'name' => $l->t('Appearance and accessibility'),
					'icon' => $this->urlGenerator->imagePath('theming', 'accessibility-dark.svg'),
				]);
			}

			if ($this->isAdmin()) {
				// App management
				$this->add([
					'type' => 'settings',
					'id' => 'core_apps',
					'order' => 5,
					'href' => $this->urlGenerator->linkToRoute('settings.AppSettings.viewApps'),
					'icon' => $this->urlGenerator->imagePath('settings', 'apps.svg'),
					'name' => $l->t('Apps'),
				]);

				// Personal settings
				$this->add([
					'type' => 'settings',
					'id' => 'settings',
					'order' => 3,
					'href' => $this->urlGenerator->linkToRoute('settings.PersonalSettings.index'),
					'name' => $l->t('Personal settings'),
					'icon' => $this->urlGenerator->imagePath('settings', 'personal.svg'),
				]);

				// Admin settings
				$this->add([
					'type' => 'settings',
					'id' => 'admin_settings',
					'order' => 4,
					'href' => $this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'overview']),
					'name' => $l->t('Administration settings'),
					'icon' => $this->urlGenerator->imagePath('settings', 'admin.svg'),
				]);
			} else {
				// Personal settings
				$this->add([
					'type' => 'settings',
					'id' => 'settings',
					'order' => 3,
					'href' => $this->urlGenerator->linkToRoute('settings.PersonalSettings.index'),
					'name' => $l->t('Settings'),
					'icon' => $this->urlGenerator->imagePath('settings', 'admin.svg'),
				]);
			}

			$logoutUrl = \OC_User::getLogoutUrl($this->urlGenerator);
			if ($logoutUrl !== '') {
				// Logout
				$this->add([
					'type' => 'settings',
					'id' => 'logout',
					'order' => 99999,
					'href' => $logoutUrl,
					'name' => $l->t('Log out'),
					'icon' => $this->urlGenerator->imagePath('core', 'actions/logout.svg'),
				]);
			}

			if ($this->isSubadmin()) {
				// User management
				$this->add([
					'type' => 'settings',
					'id' => 'core_users',
					'order' => 6,
					'href' => $this->urlGenerator->linkToRoute('settings.Users.usersList'),
					'name' => $l->t('Accounts'),
					'icon' => $this->urlGenerator->imagePath('settings', 'users.svg'),
				]);
			}
		}
		$this->eventDispatcher->dispatchTyped(new LoadAdditionalEntriesEvent());

		if ($this->userSession->isLoggedIn()) {
			$user = $this->userSession->getUser();
			$apps = $this->appManager->getEnabledAppsForUser($user);
			$this->customAppOrder = json_decode($this->config->getUserValue($user->getUID(), 'core', 'apporder', '[]'), true, flags:JSON_THROW_ON_ERROR);
		} else {
			$apps = $this->appManager->getEnabledApps();
			$this->customAppOrder = [];
		}

		foreach ($apps as $app) {
			if (!$this->userSession->isLoggedIn() && !$this->appManager->isEnabledForUser($app, $this->userSession->getUser())) {
				continue;
			}

			// load plugins and collections from info.xml
			$info = $this->appManager->getAppInfo($app);
			if (!isset($info['navigations']['navigation'])) {
				continue;
			}
			foreach ($info['navigations']['navigation'] as $key => $nav) {
				$nav['type'] = $nav['type'] ?? 'link';
				if (!isset($nav['name'])) {
					continue;
				}
				// Allow settings navigation items with no route entry, all other types require one
				if (!isset($nav['route']) && $nav['type'] !== 'settings') {
					continue;
				}
				$role = $nav['@attributes']['role'] ?? 'all';
				if ($role === 'admin' && !$this->isAdmin()) {
					continue;
				}
				$l = $this->l10nFac->get($app);
				$id = $nav['id'] ?? $app . ($key === 0 ? '' : $key);
				$order = $nav['order'] ?? 100;
				$type = $nav['type'];
				$route = !empty($nav['route']) ? $this->urlGenerator->linkToRoute($nav['route']) : '';
				$icon = $nav['icon'] ?? null;
				if ($icon !== null) {
					try {
						$icon = $this->urlGenerator->imagePath($app, $icon);
					} catch (\RuntimeException $ex) {
						// ignore
					}
				}
				if ($icon === null) {
					$icon = $this->appManager->getAppIcon($app);
				}
				if ($icon === null) {
					$icon = $this->urlGenerator->imagePath('core', 'default-app-icon');
				}

				$this->add(array_merge([
					// Navigation id
					'id' => $id,
					// Order where this entry should be shown
					'order' => $order,
					// Target of the navigation entry
					'href' => $route,
					// The icon used for the naviation entry
					'icon' => $icon,
					// Type of the navigation entry ('link' vs 'settings')
					'type' => $type,
					// Localized name of the navigation entry
					'name' => $l->t($nav['name']),
				], $type === 'link' ? [
					// App that registered this navigation entry (not necessarly the same as the id)
					'app' => $app,
				] : []
				));
			}
		}
	}

	private function isAdmin() {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			return $this->groupManager->isAdmin($user->getUID());
		}
		return false;
	}

	private function isSubadmin() {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			return $this->groupManager->getSubAdmin()->isSubAdmin($user);
		}
		return false;
	}

	public function setUnreadCounter(string $id, int $unreadCounter): void {
		$this->unreadCounters[$id] = $unreadCounter;
	}

	public function get(string $id): ?array {
		$this->init();
		foreach ($this->closureEntries as $c) {
			$this->add($c());
		}
		$this->closureEntries = [];

		return $this->entries[$id];
	}

	public function getDefaultEntryIdForUser(?IUser $user = null, bool $withFallbacks = true): string {
		$this->init();
		// Disable fallbacks here, as we need to override them with the user defaults if none are configured.
		$defaultEntryIds = $this->getDefaultEntryIds(false);

		$user ??= $this->userSession->getUser();

		if ($user !== null) {
			$userDefaultEntryIds = explode(',', $this->config->getUserValue($user->getUID(), 'core', 'defaultapp'));
			$defaultEntryIds = array_filter(array_merge($userDefaultEntryIds, $defaultEntryIds));
			if (empty($defaultEntryIds) && $withFallbacks) {
				/* Fallback on user defined apporder */
				$customOrders = json_decode($this->config->getUserValue($user->getUID(), 'core', 'apporder', '[]'), true, flags: JSON_THROW_ON_ERROR);
				if (!empty($customOrders)) {
					// filter only entries with app key (when added using closures or NavigationManager::add the app is not guaranteed to be set)
					$customOrders = array_filter($customOrders, static fn ($entry) => isset($entry['app']));
					// sort apps by order
					usort($customOrders, static fn ($a, $b) => $a['order'] - $b['order']);
					// set default apps to sorted apps
					$defaultEntryIds = array_map(static fn ($entry) => $entry['app'], $customOrders);
				}
			}
		}

		if (empty($defaultEntryIds) && $withFallbacks) {
			$defaultEntryIds = ['dashboard','files'];
		}

		$entryIds = array_keys($this->entries);

		// Find the first app that is enabled for the current user
		foreach ($defaultEntryIds as $defaultEntryId) {
			if (in_array($defaultEntryId, $entryIds, true)) {
				return $defaultEntryId;
			}
		}

		// Set fallback to always-enabled files app
		return $withFallbacks ? 'files' : '';
	}

	public function getDefaultEntryIds(bool $withFallbacks = true): array {
		$this->init();
		$storedIds = explode(',', $this->config->getSystemValueString('defaultapp', $withFallbacks ? 'dashboard,files' : ''));
		$ids = [];
		$entryIds = array_keys($this->entries);
		foreach ($storedIds as $id) {
			if (in_array($id, $entryIds, true)) {
				$ids[] = $id;
				break;
			}
		}
		return array_filter($ids);
	}

	public function setDefaultEntryIds(array $ids): void {
		$this->init();
		$entryIds = array_keys($this->entries);

		foreach ($ids as $id) {
			if (!in_array($id, $entryIds, true)) {
				$this->logger->debug('Cannot set unavailable entry as default entry', ['missing_entry' => $id]);
				throw new InvalidArgumentException('Entry not available');
			}
		}

		$this->config->setSystemValue('defaultapp', join(',', $ids));
	}
}
