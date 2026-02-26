<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\SystemTags\Activity;

use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IShareHelper;
use OCP\SystemTag\Events\AbstractTagEvent;
use OCP\SystemTag\Events\TagCreatedEvent;
use OCP\SystemTag\Events\TagDeletedEvent;
use OCP\SystemTag\Events\TagUpdatedEvent;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAssignedEvent;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagUnassignedEvent;

/**
 * @template-implements IEventListener<TagAssignedEvent|TagUnassignedEvent|TagUpdatedEvent|TagCreatedEvent|TagDeletedEvent>
 */
class TagListener implements IEventListener {
	public function __construct(
		private readonly IGroupManager $groupManager,
		private readonly IManager $activityManager,
		private readonly IUserSession $session,
		private readonly IUserConfig $userConfig,
		private readonly ISystemTagManager $tagManager,
		private readonly IAppManager $appManager,
		private readonly IMountProviderCollection $mountCollection,
		private readonly IRootFolder $rootFolder,
		private readonly IShareHelper $shareHelper,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof AbstractTagEvent) {
			$this->handleManagerEvent($event);
		} elseif ($event instanceof TagAssignedEvent || $event instanceof TagUnassignedEvent) {
			$this->handleTagEvent($event);
		}
	}

	public function handleManagerEvent(AbstractTagEvent $event): void {
		$actor = $this->session->getUser();
		if ($actor instanceof IUser) {
			$actor = $actor->getUID();
		} else {
			$actor = '';
		}
		$tag = $event->getTag();

		$activity = $this->activityManager->generateEvent();
		$activity->setApp('systemtags')
			->setType('systemtags')
			->setAuthor($actor)
			->setObject('systemtag', (int)$tag->getId(), $tag->getName());
		if ($event instanceof TagCreatedEvent) {
			$activity->setSubject(Provider::CREATE_TAG, [
				$actor,
				$this->prepareTagAsParameter($event->getTag()),
			]);
		} elseif ($event instanceof TagUpdatedEvent) {
			$activity->setSubject(Provider::UPDATE_TAG, [
				$actor,
				$this->prepareTagAsParameter($event->getTag()),
				$this->prepareTagAsParameter($event->getTagBefore()),
			]);
		} elseif ($event instanceof TagDeletedEvent) {
			$activity->setSubject(Provider::DELETE_TAG, [
				$actor,
				$this->prepareTagAsParameter($event->getTag()),
			]);
		} else {
			return;
		}

		$group = $this->groupManager->get('admin');
		if ($group instanceof IGroup) {
			foreach ($group->getUsers() as $user) {
				$activity->setAffectedUser($user->getUID());
				$this->activityManager->publish($activity);
			}
		}


		if ($actor !== '' && ($event instanceof TagCreatedEvent || $event instanceof TagUpdatedEvent)) {
			$this->updateLastUsedTags($actor, $event->getTag());
		}
	}

	private function handleTagEvent(TagAssignedEvent|TagUnassignedEvent $event) {
		$tagIds = $event->getTags();
		if ($event->getObjectType() !== 'files' || empty($tagIds)
			|| !$this->appManager->isEnabledForAnyone('activity')) {
			// System tags not for files, no tags, not (un-)assigning or no activity-app enabled (save the energy)
			return;
		}

		try {
			$tags = $this->tagManager->getTagsByIds($tagIds);
		} catch (TagNotFoundException $e) {
			// User assigned/unassigned a non-existing tag, ignore...
			return;
		}

		if (empty($tags)) {
			return;
		}

		// Get all mount point owners
		$cache = $this->mountCollection->getMountCache();
		foreach ($event->getObjectIds() as $objectId) {
			$mounts = $cache->getMountsForFileId((int)$objectId);
			if (empty($mounts)) {
				return;
			}

			$users = [];
			foreach ($mounts as $mount) {
				$owner = $mount->getUser()->getUID();
				$ownerFolder = $this->rootFolder->getUserFolder($owner);
				$nodes = $ownerFolder->getById((int)$objectId);
				if (!empty($nodes)) {
					/** @var Node $node */
					$node = array_shift($nodes);
					$al = $this->shareHelper->getPathsForAccessList($node);
					$users += $al['users'];
				}
			}

			$actor = $this->session->getUser();
			if ($actor instanceof IUser) {
				$actor = $actor->getUID();
			} else {
				$actor = '';
			}

			$activity = $this->activityManager->generateEvent();
			$activity->setApp('systemtags')
				->setType('systemtags')
				->setAuthor($actor)
				->setObject($event->getObjectType(), (int)$objectId);

			foreach ($users as $user => $path) {
				$user = (string)$user; // numerical ids could be ints which are not accepted everywhere
				$activity->setAffectedUser($user);

				foreach ($tags as $tag) {
					// don't publish activity for non-admins if tag is invisible
					if (!$tag->isUserVisible() && !$this->groupManager->isAdmin($user)) {
						continue;
					}
					if ($event instanceof TagAssignedEvent) {
						$activity->setSubject(Provider::ASSIGN_TAG, [
							$actor,
							$path,
							$this->prepareTagAsParameter($tag),
						]);
					} else {
						$activity->setSubject(Provider::UNASSIGN_TAG, [
							$actor,
							$path,
							$this->prepareTagAsParameter($tag),
						]);
					}

					$this->activityManager->publish($activity);
				}
			}

			if ($actor !== '' && $event instanceof TagAssignedEvent) {
				foreach ($tags as $tag) {
					$this->updateLastUsedTags($actor, $tag);
				}
			}
		}
	}

	protected function updateLastUsedTags(string $actor, ISystemTag $tag): void {
		$lastUsedTags = $this->userConfig->getValueArray($actor, 'systemtags', 'last_used');

		array_unshift($lastUsedTags, $tag->getId());
		$lastUsedTags = array_unique($lastUsedTags);
		$lastUsedTags = array_slice($lastUsedTags, 0, 10);

		$this->userConfig->setValueArray($actor, 'systemtags', 'last_used', $lastUsedTags);
	}

	protected function prepareTagAsParameter(ISystemTag $tag): string {
		return json_encode([
			'id' => $tag->getId(),
			'name' => $tag->getName(),
			'assignable' => $tag->isUserAssignable(),
			'visible' => $tag->isUserVisible(),
		]);
	}
}
