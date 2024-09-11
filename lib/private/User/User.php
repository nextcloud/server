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
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\BeforeUserRemovedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IImage;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\Notification\IManager as INotificationManager;
use OCP\User\Backend\IGetHomeBackend;
use OCP\User\Backend\IProvideAvatarBackend;
use OCP\User\Backend\IProvideEnabledStateBackend;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;
use OCP\User\Events\BeforePasswordUpdatedEvent;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\GetQuotaEvent;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

use function json_decode;
use function json_encode;

class User implements IUser {
	private const CONFIG_KEY_MANAGERS = 'manager';

	/** @var IAccountManager */
	protected $accountManager;

	/** @var string */
	private $uid;

	/** @var string|null */
	private $displayName;

	/** @var UserInterface|null */
	private $backend;

	/** @var IEventDispatcher */
	private $dispatcher;

	/** @var bool|null */
	private $enabled;

	/** @var Emitter|Manager|null */
	private $emitter;

	/** @var string */
	private $home;

	/** @var int|null */
	private $lastLogin;

	/** @var IAvatarManager */
	private $avatarManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IConfig */
	private $config;

	public function __construct(
		string $uid,
		?UserInterface $backend,
		IEventDispatcher $dispatcher,
		$emitter = null,
		?IConfig $config = null,
		$urlGenerator = null,
	) {
		$this->uid = $uid;
		$this->backend = $backend;
		$this->dispatcher = $dispatcher;
		$this->emitter = $emitter;
		$this->config = $config ?? \OCP\Server::get(IConfig::class);
		$this->urlGenerator = $urlGenerator ?? \OCP\Server::get(IURLGenerator::class);
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
			$this->lastLogin = (int)$this->config->getUserValue($this->uid, 'login', 'lastLogin', 0);
		}
		return (int)$this->lastLogin;
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
		if ($this->backend === null) {
			\OCP\Server::get(LoggerInterface::class)->error('Cannot delete user: No backend set');
			return false;
		}

		if ($this->emitter) {
			/** @deprecated 21.0.0 use BeforeUserDeletedEvent event with the IEventDispatcher instead */
			$this->emitter->emit('\OC\User', 'preDelete', [$this]);
		}
		$this->dispatcher->dispatchTyped(new BeforeUserDeletedEvent($this));

		// Set delete flag on the user - this is needed to ensure that the user data is removed if there happen any exception in the backend
		// because we can not restore the user meaning we could not rollback to any stable state otherwise.
		$this->config->setUserValue($this->uid, 'core', 'deleted', 'true');
		// We also need to backup the home path as this can not be reconstructed later if the original backend uses custom home paths
		$this->config->setUserValue($this->uid, 'core', 'deleted.home-path', $this->getHome());

		// Try to delete the user on the backend
		$result = $this->backend->deleteUser($this->uid);
		if ($result === false) {
			// The deletion was aborted or something else happened, we are in a defined state, so remove the delete flag
			$this->config->deleteUserValue($this->uid, 'core', 'deleted');
			return false;
		}

		// We have to delete the user from all groups
		$groupManager = \OCP\Server::get(IGroupManager::class);
		foreach ($groupManager->getUserGroupIds($this) as $groupId) {
			$group = $groupManager->get($groupId);
			if ($group) {
				$this->dispatcher->dispatchTyped(new BeforeUserRemovedEvent($group, $this));
				$group->removeUser($this);
				$this->dispatcher->dispatchTyped(new UserRemovedEvent($group, $this));
			}
		}

		$commentsManager = \OCP\Server::get(ICommentsManager::class);
		$commentsManager->deleteReferencesOfActor('users', $this->uid);
		$commentsManager->deleteReadMarksFromUser($this);

		$avatarManager = \OCP\Server::get(AvatarManager::class);
		$avatarManager->deleteUserAvatar($this->uid);

		$notificationManager = \OCP\Server::get(INotificationManager::class);
		$notification = $notificationManager->createNotification();
		$notification->setUser($this->uid);
		$notificationManager->markProcessed($notification);

		$accountManager = \OCP\Server::get(AccountManager::class);
		$accountManager->deleteUser($this);

		$database = \OCP\Server::get(IDBConnection::class);
		try {
			// We need to create a transaction to make sure we are in a defined state
			// because if all user values are removed also the flag is gone, but if an exception happens (e.g. database lost connection on the set operation)
			// exactly here we are in an undefined state as the data is still present but the user does not exist on the system anymore.
			$database->beginTransaction();
			// Remove all user settings
			$this->config->deleteAllUserValues($this->uid);
			// But again set flag that this user is about to be deleted
			$this->config->setUserValue($this->uid, 'core', 'deleted', 'true');
			$this->config->setUserValue($this->uid, 'core', 'deleted.home-path', $this->getHome());
			// Commit the transaction so we are in a defined state: either the preferences are removed or an exception occurred but the delete flag is still present
			$database->commit();
		} catch (\Throwable $e) {
			$database->rollback();
			throw $e;
		}

		if ($this->emitter !== null) {
			/** @deprecated 21.0.0 use UserDeletedEvent event with the IEventDispatcher instead */
			$this->emitter->emit('\OC\User', 'postDelete', [$this]);
		}
		$this->dispatcher->dispatchTyped(new UserDeletedEvent($this));

		// Finally we can unset the delete flag and all other states
		$this->config->deleteAllUserValues($this->uid);

		return true;
	}

	/**
	 * Set the password of the user
	 *
	 * @param string $password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 * @return bool
	 */
	public function setPassword($password, $recoveryPassword = null) {
		$this->dispatcher->dispatchTyped(new BeforePasswordUpdatedEvent($this, $password, $recoveryPassword));
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'preSetPassword', [$this, $password, $recoveryPassword]);
		}
		if ($this->backend->implementsActions(Backend::SET_PASSWORD)) {
			/** @var ISetPasswordBackend $backend */
			$backend = $this->backend;
			$result = $backend->setPassword($this->uid, $password);

			if ($result !== false) {
				$this->dispatcher->dispatchTyped(new PasswordUpdatedEvent($this, $password, $recoveryPassword));
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
			} else {
				$this->home = $this->config->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data') . '/' . $this->uid;
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
		if (!$this->config->getSystemValueBool('allow_user_to_change_display_name', true)) {
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
		$queryDatabaseValue = function (): bool {
			if ($this->enabled === null) {
				$enabled = $this->config->getUserValue($this->uid, 'core', 'enabled', 'true');
				$this->enabled = $enabled === 'true';
			}
			return $this->enabled;
		};
		if ($this->backend instanceof IProvideEnabledStateBackend) {
			return $this->backend->isUserEnabled($this->uid, $queryDatabaseValue);
		} else {
			return $queryDatabaseValue();
		}
	}

	/**
	 * set the enabled status for the user
	 *
	 * @return void
	 */
	public function setEnabled(bool $enabled = true) {
		$oldStatus = $this->isEnabled();
		$setDatabaseValue = function (bool $enabled): void {
			$this->config->setUserValue($this->uid, 'core', 'enabled', $enabled ? 'true' : 'false');
			$this->enabled = $enabled;
		};
		if ($this->backend instanceof IProvideEnabledStateBackend) {
			$queryDatabaseValue = function (): bool {
				if ($this->enabled === null) {
					$enabled = $this->config->getUserValue($this->uid, 'core', 'enabled', 'true');
					$this->enabled = $enabled === 'true';
				}
				return $this->enabled;
			};
			$enabled = $this->backend->setUserEnabled($this->uid, $enabled, $queryDatabaseValue, $setDatabaseValue);
			if ($oldStatus !== $enabled) {
				$this->triggerChange('enabled', $enabled, $oldStatus);
			}
		} elseif ($oldStatus !== $enabled) {
			$setDatabaseValue($enabled);
			$this->triggerChange('enabled', $enabled, $oldStatus);
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
				throw new InvalidArgumentException('Failed to set quota to invalid value ' . $quota);
			}
			$quota = OC_Helper::humanFileSize($bytesQuota);
		}
		if ($quota !== $oldQuota) {
			$this->config->setUserValue($this->uid, 'files', 'quota', $quota);
			$this->triggerChange('quota', $quota, $oldQuota);
		}
		\OC_Helper::clearStorageInfo('/' . $this->uid . '/files');
	}

	public function getManagerUids(): array {
		$encodedUids = $this->config->getUserValue(
			$this->uid,
			'settings',
			self::CONFIG_KEY_MANAGERS,
			'[]'
		);
		return json_decode($encodedUids, false, 512, JSON_THROW_ON_ERROR);
	}

	public function setManagerUids(array $uids): void {
		$oldUids = $this->getManagerUids();
		$this->config->setUserValue(
			$this->uid,
			'settings',
			self::CONFIG_KEY_MANAGERS,
			json_encode($uids, JSON_THROW_ON_ERROR)
		);
		$this->triggerChange('managers', $uids, $oldUids);
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
			$this->avatarManager = \OC::$server->get(IAvatarManager::class);
		}

		$avatar = $this->avatarManager->getAvatar($this->uid);
		$image = $avatar->get($size);
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
		if (str_ends_with($server, '/index.php')) {
			$server = substr($server, 0, -10);
		}
		$server = $this->removeProtocolFromUrl($server);
		return $uid . '@' . $server;
	}

	private function removeProtocolFromUrl(string $url): string {
		if (str_starts_with($url, 'https://')) {
			return substr($url, strlen('https://'));
		}

		return $url;
	}

	public function triggerChange($feature, $value = null, $oldValue = null) {
		$this->dispatcher->dispatchTyped(new UserChangedEvent($this, $feature, $value, $oldValue));
		if ($this->emitter) {
			$this->emitter->emit('\OC\User', 'changeUser', [$this, $feature, $value, $oldValue]);
		}
	}
}
