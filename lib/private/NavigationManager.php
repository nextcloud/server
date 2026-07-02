<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC;

use InvalidArgumentException;
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
use Override;
use Psr\Log\LoggerInterface;

/**
 * Manages the Nextcloud navigation
 * @psalm-import-type NavigationEntry from INavigationManager
 * @psalm-import-type NavigationEntryOutput from INavigationManager
 */
class NavigationManager implements INavigationManager {
	/** @var array<string, NavigationEntryOutput> */
	protected array $entries = [];
	/** @var list<callable(): NavigationEntry> */
	protected array $closureEntries = [];
	protected ?string $activeEntry = null;
	protected array $unreadCounters = [];
	protected bool $init = false;
	/** User defined app order (cached for the `add` function) */
	private ?array $customAppOrder = null;
	/** List of loaded app info */
	private array $loadedAppInfo = [];
	private bool $additionalEntriesLoaded = false;

	public function __construct(
		protected IAppManager $appManager,
		private IURLGenerator $urlGenerator,
		private IFactory $l10nFac,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private IConfig $config,
		private LoggerInterface $logger,
		protected IEventDispatcher $eventDispatcher,
	) {
	}

	#[Override]
	public function add(array|callable $entry): void {
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

	private function updateDefaultEntries(): void {
		$defaultEntryId = $this->getDefaultEntryIdForUser($this->userSession->getUser(), false);
		foreach ($this->entries as $id => $entry) {
			if ($entry['type'] === 'link') {
				$this->entries[$id]['default'] = $id === $defaultEntryId;
			}
		}
	}

	#[Override]
	public function getAll(string $type = 'link'): array {
		$this->resolveAppNavigationEntries();

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
	 * @param array<string, NavigationEntryOutput> $list
	 * @return array<string, NavigationEntryOutput>
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
			// Otherwise, the default app is already the ordered first, so setting the default prop will make no difference.
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
	public function clear(bool $resetInit = true): void {
		$this->entries = [];
		$this->closureEntries = [];

		if ($resetInit) {
			$this->loadedAppInfo = [];
			$this->additionalEntriesLoaded = false;
			$this->init = false;
		}
	}

	#[Override]
	public function setActiveEntry(string $appId): void {
		$this->activeEntry = $appId;
	}

	#[Override]
	public function getActiveEntry(): ?string {
		return $this->activeEntry;
	}

	/**
	 * Initialize the internal state.
	 * This loads the default app mapping and user mapping for app ordering.
	 */
	private function init(): void {
		if ($this->init) {
			return;
		}
		$this->init = true;

		if ($this->customAppOrder === null) {
			if ($this->userSession->isLoggedIn()) {
				$user = $this->userSession->getUser();
				$this->customAppOrder = json_decode($this->config->getUserValue($user->getUID(), 'core', 'apporder', '[]'), true, flags:JSON_THROW_ON_ERROR);
			} else {
				$this->customAppOrder = [];
			}
		}
	}

	/**
	 * Resolve the app navigation entries from closures and info.xml files.
	 */
	private function resolveAppNavigationEntries(): void {
		$this->resolveAppInfoEntries();

		// we do not really know the current bootstrapping state
		// but we know that the files app is always enabled and loaded when "filesystem" is loaded thus the server is ready or close-to-ready.
		if ($this->appManager->isAppLoaded('files')) {
			// Resolve app navigation closures
			while ($c = array_pop($this->closureEntries)) {
				$this->add($c());
			}

			// Resolve dynamically added navigation entries via event listeners
			if (!$this->additionalEntriesLoaded) {
				$this->additionalEntriesLoaded = true;
				$this->eventDispatcher->dispatchTyped(new LoadAdditionalEntriesEvent());
			}
		}
	}

	/**
	 * Resolve classic info.xml based navigation entires
	 */
	private function resolveAppInfoEntries(): void {
		if ($this->userSession->isLoggedIn()) {
			$user = $this->userSession->getUser();
			$apps = $this->appManager->getEnabledAppsForUser($user);
		} else {
			$apps = $this->appManager->getEnabledApps();
		}

		$appsToLoad = array_diff($apps, $this->loadedAppInfo);
		$appsToLoad = array_filter($appsToLoad, $this->appManager->isAppLoaded(...));
		if ($appsToLoad === []) {
			return;
		}

		foreach ($appsToLoad as $app) {
			// load plugins and collections from info.xml
			$info = $this->appManager->getAppInfo($app);
			if (!isset($info['navigations']['navigation'])) {
				// this app does not have any navigation entries, skip it
				$this->loadedAppInfo[] = $app;
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
				$id = $nav['id'] ?? $app . ($key === 0 ? '' : $key);
				$order = $nav['order'] ?? 100;
				$type = $nav['type'] ?? 'link';
				$route = $nav['route'] ?? '';
				if ($route !== '') {
					$route = $this->urlGenerator->linkToRoute($route);
				}
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
					$icon = $this->urlGenerator->imagePath('core', 'places/default-app-icon.svg');
				}
				if ($type === 'link' && $route === '') {
					// This means either the route is invalid in the info.xml or the app was not year loaded by the router
					$this->logger->debug('Missing or invalid navigation route for app ' . $app, ['entry' => $nav]);
					continue;
				}

				$l = $this->l10nFac->get($app);
				$this->loadedAppInfo[] = $app;
				$this->add(array_merge([
					// Navigation id
					'id' => $id,
					// Order where this entry should be shown
					'order' => $order,
					// Target of the navigation entry
					'href' => $route,
					// The icon used for the navigation entry
					'icon' => $icon,
					// Type of the navigation entry ('link' vs 'settings')
					'type' => $type,
					// Localized name of the navigation entry
					'name' => $l->t($nav['name']),
				], $type === 'link' ? [
					// App that registered this navigation entry (not necessarily the same as the id)
					'app' => $app,
				] : []
				));
			}
		}
	}

	private function isAdmin(): bool {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			return $this->groupManager->isAdmin($user->getUID());
		}
		return false;
	}

	#[Override]
	public function setUnreadCounter(string $id, int $unreadCounter): void {
		$this->unreadCounters[$id] = $unreadCounter;
	}

	#[Override]
	public function get(string $id): ?array {
		$this->resolveAppNavigationEntries();
		return $this->entries[$id];
	}

	#[Override]
	public function getDefaultEntryIdForUser(?IUser $user = null, bool $withFallbacks = true): string {
		$this->resolveAppNavigationEntries();
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

	#[Override]
	public function getDefaultEntryIds(bool $withFallbacks = true): array {
		$this->resolveAppNavigationEntries();
		$storedIds = explode(',', $this->config->getSystemValueString('defaultapp', $withFallbacks ? 'dashboard,files' : ''));
		$ids = [];
		$entryIds = array_keys($this->entries);
		foreach ($storedIds as $id) {
			if (in_array($id, $entryIds, true)) {
				$ids[] = $id;
			}
		}
		return array_filter($ids);
	}

	#[Override]
	public function setDefaultEntryIds(array $ids): void {
		$this->resolveAppNavigationEntries();
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
