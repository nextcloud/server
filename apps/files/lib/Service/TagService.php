<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Service;

use OCA\Files\AppInfo\Application;
use OCP\Activity\IManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ITagManager;
use OCP\ITags;
use OCP\IUserSession;

/**
 * Service class to manage tags on files.
 */
class TagService {
	private ?Folder $homeFolder = null;
	private ?ITags $tagger;

	public function __construct(
		private IUserSession $userSession,
		private IManager $activityManager,
		ITagManager $tagManager,
		IRootFolder $rootFolder,
	) {
		$user = $this->userSession->getUser();
		if ($user) {
			$this->homeFolder = $rootFolder->getUserFolder($user->getUID());
		}
		$this->tagger = $tagManager->load(Application::APP_ID);
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
			$this->tagger->tagAs($fileId, $tag, $path);
		}
		$deletedTags = array_diff($currentTags, $tags);
		foreach ($deletedTags as $tag) {
			$this->tagger->unTag($fileId, $tag, $path);
		}

		// TODO: re-read from tagger to make sure the
		// list is up to date, in case of concurrent changes ?
		return $tags;
	}
}
