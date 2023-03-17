<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Leon Klingele <leon@struktur.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\User;

use InvalidArgumentException;
use OC\Accounts\AccountManager;
use OC\Avatar\AvatarManager;
use OC\Hooks\Emitter;
use OC_Helper;
use OCP\Accounts\IAccountManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\BeforeUserRemovedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IImage;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\GetQuotaEvent;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;
use OCP\User\Backend\IProvideAvatarBackend;
use OCP\User\Backend\IGetHomeBackend;
use OCP\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class User implements IUser {
	/** @var IAccountManager */
	protected $accountManager;
	/** @var string */
	private $uid;

	/** @var string|null */
	private $displayName;

	/** @var UserInterface|null */
	private $backend;
	/** @var EventDispatcherInterface */
	private $legacyDispatcher;

	/** @var IEventDispatcher */
	private $dispatcher;

	/** @var bool|null */
	private $enabled;

	/** @var Emitter|Manager */
	private $emitter;

	/** @var string */
	private $home;

	/** @var int|null */
	private $lastLogin;

	/** @var \OCP\IConfig */
	private $config;

	/** @var IAvatarManager */
	private $avatarManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(string $uid, ?UserInterface $backend, EventDispatcherInterface $dispatcher, $emitter = null, IConfig $config = null, $urlGenerator = null) {
		$this->uid = $uid;
		$this->backend = $backend;
		$this->legacyDispatcher = $dispatcher;
		$this->emitter = $emitter;
		if (is_null($config)) {
			$config = \OC::$server->getConfig();
		}
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		if (is_null($this->urlGenerator)) {
			$this->urlGenerator = \OC::$server->getURLGenerator();
		}
		// TODO: inject
		$this->dispatcher = \OC::$server->query(IEventDispatcher::class);
	}

	/**
	 * get the user id
	 *
	 * @return string
	 */
	public function getUID() {
		return $this->uid;
	}

	/**
	 * get the display name for the user, if no specific display name is set it will fallback to the user id
	 *
	 * @return string
	 */
	public function getDisplayName() {
		if ($this->displayName === null) {
			$displayName = '';
			if ($this->backend && $this->backend->implementsActions(Backend::GET_DISPLAYNAME)) {
				// get display name and strip whitespace from the beginning and end of it
				$backendDisplayName = $this->backend->getDisplayName($this->uid);
				if (is_string($backendDisplayName)) {
					$displayName = trim($backendDisplayName);
				}
			}

			if (!empty($displayName)) {
				$this->displayName = $displayName;
			} else {
				$this->displayName = $this->uid;
			}
		}
		return $this->displayName;
	}

	/**
	 * set the displayname for the user
	 *
	 * @param string $displayName
	 * @return bool
	 *
	 * @since 25.0.0 Throw InvalidArgumentException
	 * @throws \InvalidArgumentException
	 */
	public function setDisplayName($displayName) {
		$displayName = trim($displayName);
		$oldDisplayName = $this->getDisplayName();
		if ($this->backend->implementsActions(Backend::SET_DISPLAYNAME) && !empty($displayName) && $displayName !== $oldDisplayName) {
			/** @var ISetDisplayNameBackend $backend */
			$backend = $this->backend;
			$result = $backend->setDisplayName($this->uid, $displayName);
			if ($result) {
				$this->displayName = $displayName;
				$this->triggerChange('displayName', $displayName, $oldDisplayName);
			}
			return $result !== false;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function setEMailAddress($mailAddress) {
		$this->setSystemEMailAddress($mailAddress);
	}

	/**
	 * @inheritDoc
	 */
	public function setSystemEMailAddress(string $mailAddress): void {
		$oldMailAddress = $this->getSystemEMailAddress();

		if ($mailAddress === '') {
			$this->config->deleteUserValue($this->uid, 'settings', 'email');
		} else {
			$this->config->setUserValue($this->uid, 'settings', 'email', $mailAddress);
		}

		$primaryAddress = $this->getPrimaryEMailAddress();
		if ($primaryAddress === $mailAddress) {
			// on match no dedicated primary settings is necessary
			$this->setPrimaryEMailAddress('');
		}

		if ($oldMailAddress !== strtolower($mailAddress)) {
			$this->triggerChange('eMailAddress', $mailAddress, $oldMailAddress);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setPrimaryEMailAddress(string $mailAddress): void {
		if ($mailAddress === '') {
			$this->config->deleteUserValue($this->uid, 'settings', 'primary_email');
			return;
		}

		$this->ensureAccountManager();
		$account = $this->accountManager->getAccount($this);
		$property = $account->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)
			->getPropertyByValue($mailAddress);

		if ($property === null || $property->getLocallyVerified() !== IAccountManager::VERIFIED) {
			throw new InvalidArgumentException('Only verified emails can be set as primary');
		}
		$this->config->setUserValue($this->uid, 'settings', 'primary_email', $mailAddress);
	}

	private function ensureAccountManager() {
		if (!$this->accountManager instanceof IAccountManager) {
			$this->accountManager = \OC::$server->get(IAccountManager::class);
		}
	}

	/**
	 * returns the timestamp of the user's last login or 0 if the user did never
	 * login
	 *
	 * @return int
	 */
	public function getLastLogin() {
		if ($this->lastLogin === null) {
			$this->lastLogin = (int) $this->config->getUserValue($this->uid, 'login', 'lastLogin', 0);
		}
		return (int) $this->lastLogin;
	}

	/**
	 * updates the timestamp of the most recent login of this user
	 */
	public function updateLastLoginTimestamp() {
		$previousLogin = $this->getLastLogin();
		$now = time();
		$firstTimeLogin = $previousLogin === 0;

		if ($now - $previousLogin > 60) {
			$this->lastLogin = time();
			$this->config->setUserValue(
				$this->uid, 'login', 'lastLogin', (string)$this->lastLogin);
		}

		return $firstTimeLogin;
	}

	/**
	 * Delete the user
	 *
	 * @return bool
	 */
	public function delete() {
		/** @deprecated 21.0.0 use BeforeUserDeletedEvent event with the IEventDispatcher instead */
		$this->legacyDispatcher->dispatch(IUser::class . '::preDelete', new GenericEvent($this));
		if ($this->emitter) {
			/** @deprecated 21.0.0 use BeforeUserDeletedEvent event with the IEventDispatcher instead */
			$this->emitter->emit('\OC\User', 'preDelete', [$this]);
		}
		$this->dispatcher->dispatchTyped(new BeforeUserDeletedEvent($this));
		$result = $this->backend->deleteUser($this->uid);
		if ($result) {
			// FIXME: Feels like an hack - suggestions?

			$groupManager = \OC::$server->getGroupManager();
			// We have to delete the user from all groups
			foreach ($groupManager->getUserGroupIds($this) as $groupId) {
				$group = $groupManager->get($groupId);
				if ($group) {
					$this->dispatcher->dispatchTyped(new BeforeUserRemovedEvent($group, $this));
					$group->removeUser($this);
					$this->dispatcher->dispatchTyped(new UserRemovedEvent($group, $this));
				}
			}
			// Delete the user's keys in preferences
			\OC::$server->getConfig()->deleteAllUserValues($this->uid);

			\OC::$server->getCommentsManager()->deleteReferencesOfActor('users', $this->uid);
			\OC::$server->getCommentsManager()->deleteReadMarksFromUser($this);

			/** @var AvatarManager $avatarManager */
			$avatarManager = \OC::$server->query(AvatarManager::class);
			$avatarManager->deleteUserAvatar($this->uid);

			$notification = \OC::$server->getNotificationManager()->createNotification();
			$notification->setUser($this->uid);
			\OC::$server->getNotificationManager()->markProcessed($notification);

			/** @var AccountManager $accountManager */
			$accountManager = \OC::$server->query(AccountManager::class);
			$accountManager->deleteUser($this);

			/** @deprecated 21.0.0 use UserDeletedEvent event with the IEventDispatcher instead */
			$this->legacyDispatcher->dispatch(IUser::class . '::postDelete', new GenericEvent($this));
			if ($this->emitter) {
				/** @deprecated 21.0.0 use UserDeletedEvent event with the IEventDispatcher instead */
				$this->emitter->emit('\OC\User', 'postDelete', [$this]);
			}
			$this->dispatcher->dispatchTyped(new UserDeletedEvent($this));
		}
		return !($result === false);
	}

	/**
	 * Set the password of the user
	 *
	 * @param string $password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 * @return bool
	 */
	public function setPassword($password, $recoveryPassword = null) {
		$this->legacyDispatcher->dispatch(IUser::class . '::preSetPassword', new GenericEvent($this, [
			'password' => $password,
			'recoveryPassword' => $recoveryPassword,
		]));
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'preSetPassword', [$this, $password, $recoveryPassword]);
		}
		if ($this->backend->implementsActions(Backend::SET_PASSWORD)) {
			/** @var ISetPasswordBackend $backend */
			$backend = $this->backend;
			$result = $backend->setPassword($this->uid, $password);

			if ($result !== false) {
				$this->legacyDispatcher->dispatch(IUser::class . '::postSetPassword', new GenericEvent($this, [
					'password' => $password,
					'recoveryPassword' => $recoveryPassword,
				]));
				if ($this->emitter) {
					$this->emitter->emit('\OC\User', 'postSetPassword', [$this, $password, $recoveryPassword]);
				}
			}

			return !($result === false);
		} else {
			return false;
		}
	}

	/**
	 * get the users home folder to mount
	 *
	 * @return string
	 */
	public function getHome() {
		if (!$this->home) {
			/** @psalm-suppress UndefinedInterfaceMethod Once we get rid of the legacy implementsActions, psalm won't complain anymore */
			if (($this->backend instanceof IGetHomeBackend || $this->backend->implementsActions(Backend::GET_HOME)) && $home = $this->backend->getHome($this->uid)) {
				$this->home = $home;
			} elseif ($this->config) {
				$this->home = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/' . $this->uid;
			} else {
				$this->home = \OC::$SERVERROOT . '/data/' . $this->uid;
			}
		}
		return $this->home;
	}

	/**
	 * Get the name of the backend class the user is connected with
	 *
	 * @return string
	 */
	public function getBackendClassName() {
		if ($this->backend instanceof IUserBackend) {
			return $this->backend->getBackendName();
		}
		return get_class($this->backend);
	}

	public function getBackend(): ?UserInterface {
		return $this->backend;
	}

	/**
	 * Check if the backend allows the user to change his avatar on Personal page
	 *
	 * @return bool
	 */
	public function canChangeAvatar() {
		if ($this->backend instanceof IProvideAvatarBackend || $this->backend->implementsActions(Backend::PROVIDE_AVATAR)) {
			/** @var IProvideAvatarBackend $backend */
			$backend = $this->backend;
			return $backend->canChangeAvatar($this->uid);
		}
		return true;
	}

	/**
	 * check if the backend supports changing passwords
	 *
	 * @return bool
	 */
	public function canChangePassword() {
		return $this->backend->implementsActions(Backend::SET_PASSWORD);
	}

	/**
	 * check if the backend supports changing display names
	 *
	 * @return bool
	 */
	public function canChangeDisplayName() {
		if ($this->config->getSystemValue('allow_user_to_change_display_name') === false) {
			return false;
		}
		return $this->backend->implementsActions(Backend::SET_DISPLAYNAME);
	}

	/**
	 * check if the user is enabled
	 *
	 * @return bool
	 */
	public function isEnabled() {
		if ($this->enabled === null) {
			$enabled = $this->config->getUserValue($this->uid, 'core', 'enabled', 'true');
			$this->enabled = $enabled === 'true';
		}
		return (bool) $this->enabled;
	}

	/**
	 * set the enabled status for the user
	 *
	 * @param bool $enabled
	 */
	public function setEnabled(bool $enabled = true) {
		$oldStatus = $this->isEnabled();
		$this->enabled = $enabled;
		if ($oldStatus !== $this->enabled) {
			// TODO: First change the value, then trigger the event as done for all other properties.
			$this->triggerChange('enabled', $enabled, $oldStatus);
			$this->config->setUserValue($this->uid, 'core', 'enabled', $enabled ? 'true' : 'false');
		}
	}

	/**
	 * get the users email address
	 *
	 * @return string|null
	 * @since 9.0.0
	 */
	public function getEMailAddress() {
		return $this->getPrimaryEMailAddress() ?? $this->getSystemEMailAddress();
	}

	/**
	 * @inheritDoc
	 */
	public function getSystemEMailAddress(): ?string {
		return $this->config->getUserValue($this->uid, 'settings', 'email', null);
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryEMailAddress(): ?string {
		return $this->config->getUserValue($this->uid, 'settings', 'primary_email', null);
	}

	/**
	 * get the users' quota
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getQuota() {
		// allow apps to modify the user quota by hooking into the event
		$event = new GetQuotaEvent($this);
		$this->dispatcher->dispatchTyped($event);
		$overwriteQuota = $event->getQuota();
		if ($overwriteQuota) {
			$quota = $overwriteQuota;
		} else {
			$quota = $this->config->getUserValue($this->uid, 'files', 'quota', 'default');
		}
		if ($quota === 'default') {
			$quota = $this->config->getAppValue('files', 'default_quota', 'none');

			// if unlimited quota is not allowed => avoid getting 'unlimited' as default_quota fallback value
			// use the first preset instead
			$allowUnlimitedQuota = $this->config->getAppValue('files', 'allow_unlimited_quota', '1') === '1';
			if (!$allowUnlimitedQuota) {
				$presets = $this->config->getAppValue('files', 'quota_preset', '1 GB, 5 GB, 10 GB');
				$presets = array_filter(array_map('trim', explode(',', $presets)));
				$quotaPreset = array_values(array_diff($presets, ['default', 'none']));
				if (count($quotaPreset) > 0) {
					$quota = $this->config->getAppValue('files', 'default_quota', $quotaPreset[0]);
				}
			}
		}
		return $quota;
	}

	/**
	 * set the users' quota
	 *
	 * @param string $quota
	 * @return void
	 * @throws InvalidArgumentException
	 * @since 9.0.0
	 */
	public function setQuota($quota) {
		$oldQuota = $this->config->getUserValue($this->uid, 'files', 'quota', '');
		if ($quota !== 'none' and $quota !== 'default') {
			$bytesQuota = OC_Helper::computerFileSize($quota);
			if ($bytesQuota === false) {
				throw new InvalidArgumentException('Failed to set quota to invalid value '.$quota);
			}
			$quota = OC_Helper::humanFileSize($bytesQuota);
		}
		if ($quota !== $oldQuota) {
			$this->config->setUserValue($this->uid, 'files', 'quota', $quota);
			$this->triggerChange('quota', $quota, $oldQuota);
		}
		\OC_Helper::clearStorageInfo('/' . $this->uid . '/files');
	}

	/**
	 * get the avatar image if it exists
	 *
	 * @param int $size
	 * @return IImage|null
	 * @since 9.0.0
	 */
	public function getAvatarImage($size) {
		// delay the initialization
		if (is_null($this->avatarManager)) {
			$this->avatarManager = \OC::$server->getAvatarManager();
		}

		$avatar = $this->avatarManager->getAvatar($this->uid);
		$image = $avatar->get(-1);
		if ($image) {
			return $image;
		}

		return null;
	}

	/**
	 * get the federation cloud id
	 *
	 * @return string
	 * @since 9.0.0
	 */
	public function getCloudId() {
		$uid = $this->getUID();
		$server = rtrim($this->urlGenerator->getAbsoluteURL('/'), '/');
		if (substr($server, -10) === '/index.php') {
			$server = substr($server, 0, -10);
		}
		$server = $this->removeProtocolFromUrl($server);
		return $uid . '@' . $server;
	}

	private function removeProtocolFromUrl(string $url): string {
		if (strpos($url, 'https://') === 0) {
			return substr($url, strlen('https://'));
		}

		return $url;
	}

	public function triggerChange($feature, $value = null, $oldValue = null) {
		$this->legacyDispatcher->dispatch(IUser::class . '::changeUser', new GenericEvent($this, [
			'feature' => $feature,
			'value' => $value,
			'oldValue' => $oldValue,
		]));
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'changeUser', [$this, $feature, $value, $oldValue]);
		}
	}
}
