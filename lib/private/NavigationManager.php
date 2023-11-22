<?php
/**
 * @copyright Copyright (c) 2016, ownCloud GmbH
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use OC\App\AppManager;
use OC\Group\Manager;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;

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
	/** The default app for the current user (cached for the `add` function) */
	private ?string $defaultApp;
	/** User defined app order (cached for the `add` function) */
	private array $customAppOrder;

	public function __construct(IAppManager $appManager,
						 IURLGenerator $urlGenerator,
						 IFactory $l10nFac,
						 IUserSession $userSession,
						 IGroupManager $groupManager,
						 IConfig $config) {
		$this->appManager = $appManager;
		$this->urlGenerator = $urlGenerator;
		$this->l10nFac = $l10nFac;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->config = $config;

		$this->defaultApp = null;
	}

	/**
	 * @inheritDoc
	 */
	public function add($entry) {
		if ($entry instanceof \Closure) {
			$this->closureEntries[] = $entry;
			return;
		}

		$entry['active'] = false;
		if (!isset($entry['icon'])) {
			$entry['icon'] = '';
		}
		if (!isset($entry['classes'])) {
			$entry['classes'] = '';
		}
		if (!isset($entry['type'])) {
			$entry['type'] = 'link';
		}

		$id = $entry['id'];
		$entry['unread'] = $this->unreadCounters[$id] ?? 0;
		if ($entry['type'] === 'link') {
			// This is the default app that will always be shown first
			$entry['default'] = ($entry['app'] ?? false) === $this->defaultApp;
			// Set order from user defined app order
			$entry['order'] = $this->customAppOrder[$id]['order'] ?? $entry['order'] ?? 100;
		}

		$this->entries[$id] = $entry;
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

		$activeApp = $this->getActiveEntry();
		if ($activeApp !== null) {
			foreach ($list as $index => &$navEntry) {
				if ($navEntry['id'] == $activeApp) {
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
				'name' => $l->t('Help'),
				'icon' => $this->urlGenerator->imagePath('settings', 'help.svg'),
			]);
		}

		if ($this->appManager === 'null') {
			return;
		}

		$this->defaultApp = $this->appManager->getDefaultAppForUser($this->userSession->getUser(), false);

		if ($this->userSession->isLoggedIn()) {
			// Profile
			$this->add([
				'type' => 'settings',
				'id' => 'profile',
				'order' => 1,
				'href' => $this->urlGenerator->linkToRoute(
					'core.ProfilePage.index',
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
					'name' => $l->t('Users'),
					'icon' => $this->urlGenerator->imagePath('settings', 'users.svg'),
				]);
			}
		}

		if ($this->userSession->isLoggedIn()) {
			$user = $this->userSession->getUser();
			$apps = $this->appManager->getEnabledAppsForUser($user);
			$this->customAppOrder = json_decode($this->config->getUserValue($user->getUID(), 'core', 'apporder', '[]'), true, flags:JSON_THROW_ON_ERROR);
		} else {
			$apps = $this->appManager->getInstalledApps();
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
				$icon = $nav['icon'] ?? 'app.svg';
				foreach ([$icon, "$app.svg"] as $i) {
					try {
						$icon = $this->urlGenerator->imagePath($app, $i);
						break;
					} catch (\RuntimeException $ex) {
						// no icon? - ignore it then
					}
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
}
