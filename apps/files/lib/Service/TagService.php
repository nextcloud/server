<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Service;

use OCA\Files\Activity\FavoriteProvider;
use OCP\Activity\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\NodeAddedToFavorite;
use OCP\Files\Events\NodeRemovedFromFavorite;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\ITags;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Service class to manage tags on files.
 */
class TagService {

	public function __construct(
		private IUserSession $userSession,
		private IManager $activityManager,
		private ?ITags $tagger,
		private ?Folder $homeFolder,
		private IEventDispatcher $dispatcher,
	) {
	}

	/**
	 * Updates the tags of the specified file path.
	 * The passed tags are absolute, which means they will
	 * replace the actual tag selection.
	 *
	 * @param string $path path
	 * @param array $tags array of tags
	 * @return array list of tags
	 * @throws NotFoundException if the file does not exist
	 */
	public function updateFileTags($path, $tags) {
		if ($this->tagger === null) {
			throw new \RuntimeException('No tagger set');
		}
		if ($this->homeFolder === null) {
			throw new \RuntimeException('No homeFolder set');
		}

		$fileId = $this->homeFolder->get($path)->getId();

		$currentTags = $this->tagger->getTagsForObjects([$fileId]);

		if (!empty($currentTags)) {
			$currentTags = current($currentTags);
		}

		$newTags = array_diff($tags, $currentTags);
		foreach ($newTags as $tag) {
			if ($tag === ITags::TAG_FAVORITE) {
				$this->addActivity(true, $fileId, $path);
			}
			$this->tagger->tagAs($fileId, $tag);
		}
		$deletedTags = array_diff($currentTags, $tags);
		foreach ($deletedTags as $tag) {
			if ($tag === ITags::TAG_FAVORITE) {
				$this->addActivity(false, $fileId, $path);
			}
			$this->tagger->unTag($fileId, $tag);
		}

		// TODO: re-read from tagger to make sure the
		// list is up to date, in case of concurrent changes ?
		return $tags;
	}

	/**
	 * @param bool $addToFavorite
	 * @param int $fileId
	 * @param string $path
	 */
	protected function addActivity($addToFavorite, $fileId, $path) {
		$user = $this->userSession->getUser();
		if (!$user instanceof IUser) {
			return;
		}

		if ($addToFavorite) {
			$event = new NodeAddedToFavorite($user, $fileId, $path);
		} else {
			$event = new NodeRemovedFromFavorite($user, $fileId, $path);
		}
		$this->dispatcher->dispatchTyped($event);

		$event = $this->activityManager->generateEvent();
		try {
			$event->setApp('files')
				->setObject('files', $fileId, $path)
				->setType('favorite')
				->setAuthor($user->getUID())
				->setAffectedUser($user->getUID())
				->setTimestamp(time())
				->setSubject(
					$addToFavorite ? FavoriteProvider::SUBJECT_ADDED : FavoriteProvider::SUBJECT_REMOVED,
					['id' => $fileId, 'path' => $path]
				);
			$this->activityManager->publish($event);
		} catch (\InvalidArgumentException $e) {
		} catch (\BadMethodCallException $e) {
		}
	}
}
