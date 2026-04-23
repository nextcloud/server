<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Group;

use OC\Hooks\PublicEmitter;
use OC\Settings\AuthorizedGroupMapper;
use OC\SubAdmin;
use OCA\Settings\Settings\Admin\Users;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\ICreateNamedGroupBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Events\BeforeGroupCreatedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\SubGroupAddedEvent;
use OCP\Group\Events\SubGroupRemovedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Group\Exception\CycleDetectedException;
use OCP\Group\Exception\NestedGroupsNotSupportedException;
use OCP\GroupInterface;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Security\Ip\IRemoteAddress;
use OCP\Server;
use Psr\Log\LoggerInterface;
use function is_string;

/**
 * Class Manager
 *
 * Hooks available in scope \OC\Group:
 * - preAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postAddUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - postRemoveUser(\OC\Group\Group $group, \OC\User\User $user)
 * - preDelete(\OC\Group\Group $group)
 * - postDelete(\OC\Group\Group $group)
 * - preCreate(string $groupId)
 * - postCreate(\OC\Group\Group $group)
 *
 * @package OC\Group
 */
class Manager extends PublicEmitter implements IGroupManager {
	/** @var GroupInterface[] */
	private array $backends = [];
	/** @var array<string, IGroup> */
	private array $cachedGroups = [];
	/** @var array<string, list<string>> */
	private array $cachedUserGroups = [];
	/** @var array<string, list<string>> cached transitive ancestor gids per gid (self included) */
	private array $cachedAncestors = [];
	/** @var array<string, list<string>> cached transitive descendant gids per gid (self included) */
	private array $cachedDescendants = [];
	private ?SubAdmin $subAdmin = null;
	private DisplayNameCache $displayNameCache;
	private const MAX_GROUP_LENGTH = 255;

	/**
	 * Beyond this threshold, {@see addSubGroup()} and {@see removeSubGroup()}
	 * skip synthesizing per-user UserAdded/UserRemoved events and log a
	 * prominent warning instead. The aim is to bound worst-case request
	 * duration on huge nested hierarchies at the cost of leaving dependent
	 * state (notably server-side encryption key distribution) out of sync
	 * for those users. Admins who hit this cap must run a manual re-key pass.
	 */
	private const MAX_SYNTHESIZED_USER_EVENTS = 500;

	public function __construct(
		private \OC\User\Manager $userManager,
		private IEventDispatcher $dispatcher,
		private LoggerInterface $logger,
		ICacheFactory $cacheFactory,
		private IRemoteAddress $remoteAddress,
	) {
		$this->displayNameCache = new DisplayNameCache($cacheFactory, $this);

		$this->listen('\OC\Group', 'preDelete', function (IGroup $group): void {
			unset($this->cachedGroups[$group->getGID()]);
			$this->cachedUserGroups = [];
			$this->cachedAncestors = [];
			$this->cachedDescendants = [];
		});
		$this->listen('\OC\Group', 'preAddUser', function (IGroup $group): void {
			$this->cachedUserGroups = [];
		});
		$this->listen('\OC\Group', 'preRemoveUser', function (IGroup $group): void {
			$this->cachedUserGroups = [];
		});
	}

	/**
	 * Checks whether a given backend is used
	 *
	 * @param string $backendClass Full classname including complete namespace
	 * @return bool
	 */
	public function isBackendUsed($backendClass) {
		$backendClass = strtolower(ltrim($backendClass, '\\'));

		foreach ($this->backends as $backend) {
			if (strtolower(get_class($backend)) === $backendClass) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param GroupInterface $backend
	 */
	public function addBackend($backend) {
		$this->backends[] = $backend;
		$this->clearCaches();
	}

	public function clearBackends() {
		$this->backends = [];
		$this->clearCaches();
	}

	/**
	 * Get the active backends
	 *
	 * @return GroupInterface[]
	 */
	public function getBackends() {
		return $this->backends;
	}


	protected function clearCaches() {
		$this->cachedGroups = [];
		$this->cachedUserGroups = [];
		$this->cachedAncestors = [];
		$this->cachedDescendants = [];
	}

	/**
	 * @param string $gid
	 * @return IGroup|null
	 */
	public function get($gid) {
		if (isset($this->cachedGroups[$gid])) {
			return $this->cachedGroups[$gid];
		}
		return $this->getGroupObject($gid);
	}

	/**
	 * @param string $gid
	 * @param string $displayName
	 * @return IGroup|null
	 */
	protected function getGroupObject($gid, $displayName = null) {
		$backends = [];
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(Backend::GROUP_DETAILS)) {
				$groupData = $backend->getGroupDetails($gid);
				if (is_array($groupData) && !empty($groupData)) {
					// take the display name from the last backend that has a non-null one
					if (is_null($displayName) && isset($groupData['displayName'])) {
						$displayName = $groupData['displayName'];
					}
					$backends[] = $backend;
				}
			} elseif ($backend->groupExists($gid)) {
				$backends[] = $backend;
			}
		}
		if (count($backends) === 0) {
			return null;
		}
		/** @var GroupInterface[] $backends */
		$this->cachedGroups[$gid] = new Group($gid, $backends, $this->dispatcher, $this->userManager, $this, $displayName);
		return $this->cachedGroups[$gid];
	}

	/**
	 * @brief Batch method to create group objects
	 *
	 * @param list<string> $gids List of groupIds for which we want to create a IGroup object
	 * @param array<string, string> $displayNames Array containing already know display name for a groupId
	 * @return array<string, IGroup>
	 */
	protected function getGroupsObjects(array $gids, array $displayNames = []): array {
		$backends = [];
		$groups = [];
		foreach ($gids as $gid) {
			$backends[$gid] = [];
			if (!isset($displayNames[$gid])) {
				$displayNames[$gid] = null;
			}
		}
		foreach ($this->backends as $backend) {
			if ($backend instanceof IGroupDetailsBackend || $backend->implementsActions(GroupInterface::GROUP_DETAILS)) {
				/** @var GroupInterface&IGroupDetailsBackend $backend */
				if ($backend instanceof IBatchMethodsBackend) {
					$groupDatas = $backend->getGroupsDetails($gids);
				} else {
					$groupDatas = [];
					foreach ($gids as $gid) {
						$groupDatas[$gid] = $backend->getGroupDetails($gid);
					}
				}
				foreach ($groupDatas as $gid => $groupData) {
					if (!empty($groupData)) {
						// take the display name from the last backend that has a non-null one
						if (isset($groupData['displayName'])) {
							$displayNames[$gid] = $groupData['displayName'];
						}
						$backends[$gid][] = $backend;
					}
				}
			} else {
				if ($backend instanceof IBatchMethodsBackend) {
					$existingGroups = $backend->groupsExists($gids);
				} else {
					$existingGroups = array_filter($gids, fn (string $gid): bool => $backend->groupExists($gid));
				}
				foreach ($existingGroups as $group) {
					$backends[$group][] = $backend;
				}
			}
		}
		foreach ($gids as $gid) {
			if (count($backends[$gid]) === 0) {
				continue;
			}
			$this->cachedGroups[$gid] = new Group($gid, $backends[$gid], $this->dispatcher, $this->userManager, $this, $displayNames[$gid]);
			$groups[$gid] = $this->cachedGroups[$gid];
		}
		return $groups;
	}

	/**
	 * @param string $gid
	 * @return bool
	 */
	public function groupExists($gid) {
		return $this->get($gid) instanceof IGroup;
	}

	/**
	 * @param string $gid
	 * @return IGroup|null
	 */
	public function createGroup($gid) {
		if ($gid === '' || $gid === null) {
			return null;
		} elseif ($group = $this->get($gid)) {
			return $group;
		} elseif (mb_strlen($gid) > self::MAX_GROUP_LENGTH) {
			throw new \Exception('Group name is limited to ' . self::MAX_GROUP_LENGTH . ' characters');
		} else {
			$this->dispatcher->dispatchTyped(new BeforeGroupCreatedEvent($gid));
			$this->emit('\OC\Group', 'preCreate', [$gid]);
			foreach ($this->backends as $backend) {
				if ($backend->implementsActions(Backend::CREATE_GROUP)) {
					if ($backend instanceof ICreateNamedGroupBackend) {
						$groupName = $gid;
						if (($gid = $backend->createGroup($groupName)) !== null) {
							$group = $this->getGroupObject($gid);
							$this->dispatcher->dispatchTyped(new GroupCreatedEvent($group));
							$this->emit('\OC\Group', 'postCreate', [$group]);
							return $group;
						}
					} elseif ($backend->createGroup($gid)) {
						$group = $this->getGroupObject($gid);
						$this->dispatcher->dispatchTyped(new GroupCreatedEvent($group));
						$this->emit('\OC\Group', 'postCreate', [$group]);
						return $group;
					}
				}
			}
			return null;
		}
	}

	public function search(string $search, ?int $limit = null, ?int $offset = 0) {
		$groups = [];
		foreach ($this->backends as $backend) {
			$groupIds = $backend->getGroups($search, $limit ?? -1, $offset ?? 0);
			$newGroups = $this->getGroupsObjects($groupIds);
			foreach ($newGroups as $groupId => $group) {
				$groups[$groupId] = $group;
			}
			if (!is_null($limit) && $limit <= 0) {
				return array_values($groups);
			}
		}
		return array_values($groups);
	}

	/**
	 * @param IUser|null $user
	 * @return array<string, IGroup>
	 */
	public function getUserGroups(?IUser $user = null): array {
		if (!$user instanceof IUser) {
			return [];
		}
		return $this->getUserIdGroups($user->getUID());
	}

	/**
	 * @param string $uid the user id
	 * @return array<string, IGroup>
	 */
	public function getUserIdGroups(string $uid): array {
		$groups = [];

		foreach ($this->getUserIdGroupIds($uid) as $groupId) {
			$aGroup = $this->get($groupId);
			if ($aGroup instanceof IGroup) {
				$groups[$groupId] = $aGroup;
			} else {
				$this->logger->debug('User "' . $uid . '" belongs to deleted group: "' . $groupId . '"', ['app' => 'core']);
			}
		}

		return $groups;
	}

	/**
	 * Checks if a userId is in the admin group
	 *
	 * @param string $userId
	 * @return bool if admin
	 */
	public function isAdmin($userId) {
		if (!$this->remoteAddress->allowsAdminActions()) {
			return false;
		}

		foreach ($this->backends as $backend) {
			if (is_string($userId) && $backend->implementsActions(Backend::IS_ADMIN) && $backend->isAdmin($userId)) {
				return true;
			}
		}
		return $this->isInGroup($userId, 'admin');
	}

	public function isDelegatedAdmin(string $userId): bool {
		if (!$this->remoteAddress->allowsAdminActions()) {
			return false;
		}

		// Check if the user as admin delegation for users listing
		$authorizedGroupMapper = Server::get(AuthorizedGroupMapper::class);
		$user = $this->userManager->get($userId);
		$authorizedClasses = $authorizedGroupMapper->findAllClassesForUser($user);
		return in_array(Users::class, $authorizedClasses, true);
	}

	/**
	 * Checks if a userId is in a group
	 *
	 * @param string $userId
	 * @param string $group
	 * @return bool if in group
	 */
	public function isInGroup($userId, $group) {
		return in_array($group, $this->getUserIdGroupIds($userId));
	}

	public function getUserGroupIds(IUser $user): array {
		return $this->getUserIdGroupIds($user->getUID());
	}

	/**
	 * Return the *effective* group ids a user belongs to, including ancestors
	 * reached via nested-group edges.
	 *
	 * Unlike {@see getUserGroupIds()}, this walks the group_group table and
	 * includes every group reachable from the user's direct memberships.
	 * Use this for share expansion, permission checks, and anywhere "the user
	 * is effectively a member of G" is the intended semantic.
	 *
	 * @return list<string>
	 */
	public function getUserEffectiveGroupIds(IUser $user): array {
		$direct = $this->getUserIdGroupIds($user->getUID());
		return $this->expandAncestors($direct);
	}

	/**
	 * @param string $uid the user id
	 * @return list<string>
	 */
	private function getUserIdGroupIds(string $uid): array {
		if (!isset($this->cachedUserGroups[$uid])) {
			$groups = [];
			foreach ($this->backends as $backend) {
				if ($groupIds = $backend->getUserGroups($uid)) {
					$groups = array_merge($groups, $groupIds);
				}
			}
			$this->cachedUserGroups[$uid] = $groups;
		}

		return $this->cachedUserGroups[$uid];
	}

	/**
	 * Locate the (single) nested-group-aware backend, if any.
	 */
	private function getNestedGroupBackend(): ?INestedGroupBackend {
		foreach ($this->backends as $backend) {
			if ($backend instanceof INestedGroupBackend) {
				return $backend;
			}
		}
		return null;
	}

	/**
	 * Direct children (one level) of $gid, without walking the subtree.
	 *
	 * Internal helper used by the admin UI's subgroup listing and by the
	 * sub-admin ancestor lookup.
	 *
	 * @return list<string>
	 */
	public function getDirectChildGroupIds(string $gid): array {
		$backend = $this->getNestedGroupBackend();
		if ($backend === null) {
			return [];
		}
		return array_values($backend->getChildGroups($gid));
	}

	/**
	 * Direct parents (one level) of $gid.
	 *
	 * @return list<string>
	 */
	public function getDirectParentGroupIds(string $gid): array {
		$backend = $this->getNestedGroupBackend();
		if ($backend === null) {
			return [];
		}
		return array_values($backend->getParentGroups($gid));
	}

	public function getGroupEffectiveDescendantIds(IGroup $group): array {
		return $this->expandDescendants($group->getGID());
	}

	public function getGroupEffectiveAncestorIds(IGroup $group): array {
		return $this->expandAncestors([$group->getGID()]);
	}

	/**
	 * Expand a list of gids to include all transitive ancestors
	 * (every group reachable by following child -> parent edges).
	 *
	 * The result always contains the input gids and is deduplicated.
	 * Results are memoized per gid for the lifetime of the Manager instance.
	 *
	 * @param list<string> $gids
	 * @return list<string>
	 */
	public function expandAncestors(array $gids): array {
		$backend = $this->getNestedGroupBackend();
		if ($backend === null || $gids === []) {
			return array_values(array_unique($gids));
		}
		$seen = [];
		$toWalk = [];
		foreach ($gids as $gid) {
			if (isset($seen[$gid])) {
				continue;
			}
			if (isset($this->cachedAncestors[$gid])) {
				foreach ($this->cachedAncestors[$gid] as $anc) {
					$seen[$anc] = true;
				}
				continue;
			}
			$seen[$gid] = true;
			$toWalk[] = $gid;
		}

		if ($toWalk !== []) {
			// Per-input-gid we need to know which ancestors are reachable so
			// we can memoize correctly; this is a BFS from each starting gid
			// but we batch the parent lookup per level across all active
			// frontiers to keep DB round-trips down.
			$perRoot = [];
			foreach ($toWalk as $root) {
				$perRoot[$root] = [$root => true];
			}
			$frontier = $toWalk;
			$frontierOrigin = [];
			foreach ($toWalk as $root) {
				$frontierOrigin[$root] = [$root];
			}
			while ($frontier !== []) {
				$parentsMap = $backend->getParentGroupsBatch(array_values(array_unique($frontier)));
				$nextFrontier = [];
				$nextOrigin = [];
				foreach ($frontier as $node) {
					$parents = $parentsMap[$node] ?? [];
					$origins = $frontierOrigin[$node] ?? [];
					foreach ($parents as $parent) {
						foreach ($origins as $origin) {
							if (isset($perRoot[$origin][$parent])) {
								continue;
							}
							$perRoot[$origin][$parent] = true;
							$seen[$parent] = true;
							if (!isset($nextOrigin[$parent])) {
								$nextOrigin[$parent] = [];
								$nextFrontier[] = $parent;
							}
							$nextOrigin[$parent][] = $origin;
						}
					}
				}
				$frontier = $nextFrontier;
				$frontierOrigin = $nextOrigin;
			}
			foreach ($perRoot as $root => $ancestors) {
				$this->cachedAncestors[$root] = array_keys($ancestors);
			}
		}

		return array_keys($seen);
	}

	/**
	 * Expand $gid to include itself plus all transitive descendants
	 * (every group reachable by following parent -> child edges).
	 *
	 * Results are memoized per gid for the lifetime of the Manager instance.
	 *
	 * @return list<string>
	 */
	public function expandDescendants(string $gid): array {
		if (isset($this->cachedDescendants[$gid])) {
			return $this->cachedDescendants[$gid];
		}
		$backend = $this->getNestedGroupBackend();
		if ($backend === null) {
			return $this->cachedDescendants[$gid] = [$gid];
		}
		$seen = [$gid => true];
		$frontier = [$gid];
		while ($frontier !== []) {
			$childMap = $backend->getChildGroupsBatch($frontier);
			$next = [];
			foreach ($childMap as $children) {
				foreach ($children as $child) {
					if (!isset($seen[$child])) {
						$seen[$child] = true;
						$next[] = $child;
					}
				}
			}
			$frontier = $next;
		}
		return $this->cachedDescendants[$gid] = array_keys($seen);
	}

	/**
	 * Add $child as a direct subgroup of $parent.
	 *
	 * After the edge is inserted, dispatches {@see SubGroupAddedEvent} and
	 * synthesizes {@see UserAddedEvent} for every user who becomes a new
	 * effective member of $parent, so listeners such as the server-side
	 * encryption app can re-key files shared with $parent.
	 *
	 * To bound worst-case request duration, if the number of newly-effective
	 * users exceeds {@see self::MAX_SYNTHESIZED_USER_EVENTS} the per-user
	 * events are skipped and a warning is logged; admins must then manually
	 * trigger any dependent rebuilds (e.g. encryption re-keying).
	 *
	 * @throws CycleDetectedException if the edge would create a cycle
	 * @throws NestedGroupsNotSupportedException if no nested-group backend is registered
	 */
	public function addSubGroup(IGroup $parent, IGroup $child): bool {
		$backend = $this->getNestedGroupBackend();
		if ($backend === null) {
			throw new NestedGroupsNotSupportedException('No nested-group backend registered');
		}
		$parentGid = $parent->getGID();
		$childGid = $child->getGID();

		// Snapshot users directly or transitively reachable from $parent
		// *before* the edge. These are the users we will NOT emit events for.
		$before = $this->collectEffectiveUserIds($parentGid);

		try {
			if (!$backend->addGroupToGroup($childGid, $parentGid)) {
				return false;
			}
		} catch (\InvalidArgumentException $e) {
			// Preserve the typed exception for callers; CycleDetectedException
			// already extends InvalidArgumentException.
			if ($e instanceof CycleDetectedException) {
				throw $e;
			}
			throw new CycleDetectedException($e->getMessage(), 0, $e);
		}
		$this->invalidateNestingCaches();

		// Users now effectively in $parent via the new child subtree are
		// exactly the users in expandDescendants($childGid) that were not
		// already in $before. One descendant sweep, not two.
		$childSide = $this->collectEffectiveUserIds($childGid);
		$added = array_diff_key($childSide, $before);

		$this->dispatcher->dispatchTyped(new SubGroupAddedEvent($parent, $child));
		$this->dispatchUserEventsForDelta(UserAddedEvent::class, $parent, $added);
		return true;
	}

	/**
	 * Remove the direct edge $parent -> $child.
	 *
	 * Dispatches {@see SubGroupRemovedEvent} and synthesizes
	 * {@see UserRemovedEvent} for every user who loses effective membership
	 * in $parent, subject to the synthesis cap.
	 *
	 * @throws NestedGroupsNotSupportedException if no nested-group backend is registered
	 */
	public function removeSubGroup(IGroup $parent, IGroup $child): bool {
		$backend = $this->getNestedGroupBackend();
		if ($backend === null) {
			throw new NestedGroupsNotSupportedException('No nested-group backend registered');
		}
		$parentGid = $parent->getGID();
		$childGid = $child->getGID();

		$before = $this->collectEffectiveUserIds($parentGid);

		if (!$backend->removeGroupFromGroup($childGid, $parentGid)) {
			return false;
		}
		$this->invalidateNestingCaches();

		// Users that lose membership are those only reachable via the
		// removed child branch. Compute "after" once.
		$after = $this->collectEffectiveUserIds($parentGid);
		$removed = array_diff_key($before, $after);

		$this->dispatcher->dispatchTyped(new SubGroupRemovedEvent($parent, $child));
		$this->dispatchUserEventsForDelta(UserRemovedEvent::class, $parent, $removed);
		return true;
	}

	private function invalidateNestingCaches(): void {
		$this->cachedAncestors = [];
		$this->cachedDescendants = [];
		$this->cachedUserGroups = [];
	}

	/**
	 * Collect every uid that is transitively a member of $gid (self included).
	 *
	 * Returned as a map uid => true for O(1) diffing.
	 *
	 * @return array<string, true>
	 */
	private function collectEffectiveUserIds(string $gid): array {
		$uids = [];
		foreach ($this->expandDescendants($gid) as $descendant) {
			$group = $this->get($descendant);
			if ($group === null) {
				continue;
			}
			foreach ($group->searchUsers('') as $user) {
				$uids[$user->getUID()] = true;
			}
		}
		return $uids;
	}

	/**
	 * Dispatch UserAddedEvent or UserRemovedEvent for each uid in $delta.
	 *
	 * If the delta exceeds the synthesis cap, skip and log a warning — admins
	 * must then perform any dependent rebuilds manually. This bounds the
	 * request time for bulk nesting mutations at the cost of leaving
	 * listener-driven state (encryption keys, activity, …) stale for large
	 * user sets.
	 *
	 * @param class-string<UserAddedEvent|UserRemovedEvent> $eventClass
	 * @param array<string, true> $delta
	 */
	private function dispatchUserEventsForDelta(string $eventClass, IGroup $group, array $delta): void {
		$count = count($delta);
		if ($count === 0) {
			return;
		}
		if ($count > self::MAX_SYNTHESIZED_USER_EVENTS) {
			$this->logger->warning(
				'Skipped synthesizing {event} for {count} users on group {gid}: exceeds cap {cap}. '
				. 'Dependent state (encryption keys, activity, …) may be out of sync for affected users; '
				. 'admins must trigger a manual rebuild.',
				[
					'event' => $eventClass,
					'count' => $count,
					'gid' => $group->getGID(),
					'cap' => self::MAX_SYNTHESIZED_USER_EVENTS,
				],
			);
			return;
		}
		foreach ($delta as $uid => $_) {
			$user = $this->userManager->get($uid);
			if ($user === null) {
				continue;
			}
			$this->dispatcher->dispatchTyped(new $eventClass($group, $user));
		}
	}

	/**
	 * @param string $groupId
	 * @return ?string
	 */
	public function getDisplayName(string $groupId): ?string {
		return $this->displayNameCache->getDisplayName($groupId);
	}

	/**
	 * get an array of groupid and displayName for a user
	 *
	 * @param IUser $user
	 * @return array ['displayName' => displayname]
	 */
	public function getUserGroupNames(IUser $user) {
		return array_map(function ($group) {
			return ['displayName' => $this->displayNameCache->getDisplayName($group->getGID())];
		}, $this->getUserGroups($user));
	}

	public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$group = $this->get($gid);
		if (is_null($group)) {
			return [];
		}

		$search = trim($search);
		$groupUsers = [];

		if (!empty($search)) {
			// only user backends have the capability to do a complex search for users
			$searchOffset = 0;
			$searchLimit = $limit * 100;
			if ($limit === -1) {
				$searchLimit = 500;
			}

			do {
				$filteredUsers = $this->userManager->searchDisplayName($search, $searchLimit, $searchOffset);
				foreach ($filteredUsers as $filteredUser) {
					if ($group->inGroup($filteredUser)) {
						$groupUsers[] = $filteredUser;
					}
				}
				$searchOffset += $searchLimit;
			} while (count($groupUsers) < $searchLimit + $offset && count($filteredUsers) >= $searchLimit);

			if ($limit === -1) {
				$groupUsers = array_slice($groupUsers, $offset);
			} else {
				$groupUsers = array_slice($groupUsers, $offset, $limit);
			}
		} else {
			$groupUsers = $group->searchUsers('', $limit, $offset);
		}

		$matchingUsers = [];
		foreach ($groupUsers as $groupUser) {
			$matchingUsers[(string)$groupUser->getUID()] = $groupUser->getDisplayName();
		}
		return $matchingUsers;
	}

	/**
	 * @return SubAdmin
	 */
	public function getSubAdmin() {
		if (!$this->subAdmin) {
			$this->subAdmin = new SubAdmin(
				$this->userManager,
				$this,
				Server::get(IDBConnection::class),
				$this->dispatcher
			);
		}

		return $this->subAdmin;
	}
}
