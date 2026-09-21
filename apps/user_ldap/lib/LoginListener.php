<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP;

use OCA\User_LDAP\Db\GroupMembership;
use OCA\User_LDAP\Db\GroupMembershipMapper;
use OCP\Config\IUserConfig;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
use OCP\User\Events\UserLoggedInEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserLoggedInEvent>
 */
class LoginListener implements IEventListener {
	public function __construct(
		private IEventDispatcher $dispatcher,
		private Group_Proxy $groupBackend,
		private IGroupManager $groupManager,
		private User_Proxy $userBackend,
		private LoggerInterface $logger,
		private GroupMembershipMapper $groupMembershipMapper,
		private IUserConfig $userConfig,
		private INotificationManager $notificationManager,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof UserLoggedInEvent) {
			$this->onPostLogin($event->getUser());
		}
	}

	public function onPostLogin(IUser $user): void {
		$this->logger->info(
			self::class . ' - {user} postLogin',
			[
				'app' => 'user_ldap',
				'user' => $user->getUID(),
			]
		);
		$this->handlePasswordExpiry($user);
		$this->updateGroups($user);
	}

	private function updateGroups(IUser $userObject): void {
		$userId = $userObject->getUID();
		$groupMemberships = $this->groupMembershipMapper->findGroupMembershipsForUser($userId);
		$knownGroups = array_map(
			static fn (GroupMembership $groupMembership): string => $groupMembership->getGroupid(),
			$groupMemberships
		);
		$groupMemberships = array_combine($knownGroups, $groupMemberships);
		$actualGroups = $this->groupBackend->getUserGroups($userId);

		$newGroups = array_diff($actualGroups, $knownGroups);
		$oldGroups = array_diff($knownGroups, $actualGroups);
		foreach ($newGroups as $groupId) {
			$groupObject = $this->groupManager->get($groupId);
			if ($groupObject === null) {
				$this->logger->error(
					self::class . ' - group {group} could not be found (user {user})',
					[
						'app' => 'user_ldap',
						'user' => $userId,
						'group' => $groupId
					]
				);
				continue;
			}
			try {
				$this->groupMembershipMapper->insert(GroupMembership::fromParams(['groupid' => $groupId,'userid' => $userId]));
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					$this->logger->error(
						self::class . ' - group {group} membership failed to be added (user {user})',
						[
							'app' => 'user_ldap',
							'user' => $userId,
							'group' => $groupId,
							'exception' => $e,
						]
					);
				}
				/* We failed to insert the groupmembership so we do not want to advertise it */
				continue;
			}
			$this->groupBackend->addRelationshipToCaches($userId, null, $groupId);
			$this->dispatcher->dispatchTyped(new UserAddedEvent($groupObject, $userObject));
			$this->logger->info(
				self::class . ' - {user} added to {group}',
				[
					'app' => 'user_ldap',
					'user' => $userId,
					'group' => $groupId
				]
			);
		}
		foreach ($oldGroups as $groupId) {
			try {
				$this->groupMembershipMapper->delete($groupMemberships[$groupId]);
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_DATABASE_OBJECT_NOT_FOUND) {
					$this->logger->error(
						self::class . ' - group {group} membership failed to be removed (user {user})',
						[
							'app' => 'user_ldap',
							'user' => $userId,
							'group' => $groupId,
							'exception' => $e,
						]
					);
				}
				/* We failed to delete the groupmembership so we do not want to advertise it */
				continue;
			}
			$groupObject = $this->groupManager->get($groupId);
			if ($groupObject === null) {
				$this->logger->error(
					self::class . ' - group {group} could not be found (user {user})',
					[
						'app' => 'user_ldap',
						'user' => $userId,
						'group' => $groupId
					]
				);
				continue;
			}
			$this->dispatcher->dispatchTyped(new UserRemovedEvent($groupObject, $userObject));
			$this->logger->info(
				'service "updateGroups" - {user} removed from {group}',
				[
					'user' => $userId,
					'group' => $groupId
				]
			);
		}
	}

	private function handlePasswordExpiry(IUser $userObject): void {
		$uid = $userObject->getUID();
		$access = $this->userBackend->getLDAPAccess($uid);
		if ($access === false) {
			return;
		}
		$dn = $access->username2dn($uid);
		if ($dn === false) {
			return;
		}
		$connection = $access->getConnection();
		$ppolicyDN = $connection->ldapDefaultPPolicyDN;
		if (empty($ppolicyDN) || ((int)$connection->turnOnPasswordChange !== 1)) {
			// Password expiry handling disabled
			return;
		}
		// Retrieve relevant user attributes
		$result = $access->search('objectclass=*', $dn, ['pwdpolicysubentry', 'pwdgraceusetime', 'pwdreset', 'pwdchangedtime']);

		if (!empty($result)) {
			if (array_key_exists('pwdpolicysubentry', $result[0])) {
				$pwdPolicySubentry = $result[0]['pwdpolicysubentry'];
				if ($pwdPolicySubentry && (count($pwdPolicySubentry) > 0)) {
					// Custom ppolicy DN
					$ppolicyDN = $pwdPolicySubentry[0];
				}
			}

			$pwdGraceUseTime = array_key_exists('pwdgraceusetime', $result[0]) ? $result[0]['pwdgraceusetime'] : [];
			$pwdReset = array_key_exists('pwdreset', $result[0]) ? $result[0]['pwdreset'] : [];
			$pwdChangedTime = array_key_exists('pwdchangedtime', $result[0]) ? $result[0]['pwdchangedtime'] : [];
		}

		// Retrieve relevant password policy attributes
		$cacheKey = 'ppolicyAttributes' . $ppolicyDN;
		$result = $connection->getFromCache($cacheKey);
		if (is_null($result)) {
			$result = $access->search('objectclass=*', $ppolicyDN, ['pwdgraceauthnlimit', 'pwdmaxage', 'pwdexpirewarning']);
			$connection->writeToCache($cacheKey, $result);
		}

		$pwdGraceAuthNLimit = array_key_exists('pwdgraceauthnlimit', $result[0]) ? $result[0]['pwdgraceauthnlimit'] : [];
		$pwdMaxAge = array_key_exists('pwdmaxage', $result[0]) ? $result[0]['pwdmaxage'] : [];
		$pwdExpireWarning = array_key_exists('pwdexpirewarning', $result[0]) ? $result[0]['pwdexpirewarning'] : [];

		// Handle grace login
		if (!empty($pwdGraceUseTime)) {
			// Was this a grace login?
			if (!empty($pwdGraceAuthNLimit)
				&& count($pwdGraceUseTime) < (int)$pwdGraceAuthNLimit[0]) {
				// At least one more grace login available?
				$this->userConfig->setValueBool($uid, 'user_ldap', 'needsPasswordReset', true);
				header('Location: ' . Server::get(IURLGenerator::class)->linkToRouteAbsolute(
					'user_ldap.renewPassword.showRenewPasswordForm', ['user' => $uid]));
			} else {
				// No more grace login available
				header('Location: ' . Server::get(IURLGenerator::class)->linkToRouteAbsolute(
					'user_ldap.renewPassword.showLoginFormInvalidPassword', ['user' => $uid]));
			}
			exit();
		}
		// Handle pwdReset attribute
		if (!empty($pwdReset) && $pwdReset[0] === 'TRUE') {
			// User must change their password
			$this->userConfig->setValueBool($uid, 'user_ldap', 'needsPasswordReset', true);
			header('Location: ' . Server::get(IURLGenerator::class)->linkToRouteAbsolute(
				'user_ldap.renewPassword.showRenewPasswordForm', ['user' => $uid]));
			exit();
		}
		// Handle password expiry warning
		if (!empty($pwdChangedTime)) {
			if (!empty($pwdMaxAge)
				&& !empty($pwdExpireWarning)) {
				$pwdMaxAgeInt = (int)$pwdMaxAge[0];
				$pwdExpireWarningInt = (int)$pwdExpireWarning[0];
				if ($pwdMaxAgeInt > 0 && $pwdExpireWarningInt > 0) {
					$pwdChangedTimeDt = \DateTime::createFromFormat('YmdHisZ', $pwdChangedTime[0]);
					$pwdChangedTimeDt->add(new \DateInterval('PT' . $pwdMaxAgeInt . 'S'));
					$currentDateTime = new \DateTime();
					$secondsToExpiry = $pwdChangedTimeDt->getTimestamp() - $currentDateTime->getTimestamp();
					if ($secondsToExpiry <= $pwdExpireWarningInt) {
						// Remove last password expiry warning if any
						$notification = $this->notificationManager->createNotification();
						$notification->setApp('user_ldap')
							->setUser($uid)
							->setObject('pwd_exp_warn', $uid)
						;
						$this->notificationManager->markProcessed($notification);
						// Create new password expiry warning
						$notification = $this->notificationManager->createNotification();
						$notification->setApp('user_ldap')
							->setUser($uid)
							->setDateTime($currentDateTime)
							->setObject('pwd_exp_warn', $uid)
							->setSubject('pwd_exp_warn_days', [(int)ceil($secondsToExpiry / 60 / 60 / 24)])
						;
						$this->notificationManager->notify($notification);
					}
				}
			}
		}
	}
}
