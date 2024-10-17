<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Service;

use OCP\Files\Folder;
use OCP\ITags;

/**
 * Service class to manage tags on files.
 */
class TagService {

	/** @var ITags|null */
	private $tagger;
	/** @var Folder|null */
	private $homeFolder;

	public function __construct(
		?ITags $tagger,
		?Folder $homeFolder,
	) {
		$this->tagger = $tagger;
		$this->homeFolder = $homeFolder;
	}

	/**
	 * Updates the tags of the specified file path.
	 * The passed tags are absolute, which means they will
	 * replace the actual tag selection.
	 *
	 * @param string $path path
	 * @param array $tags array of tags
	 * @return array list of tags
	 * @throws \OCP\Files\NotFoundException if the file does not exist
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
				$this->tagger->addToFavorites($fileId, $path);
				continue;
			}
			$this->tagger->tagAs($fileId, $tag);
		}
		$deletedTags = array_diff($currentTags, $tags);
		foreach ($deletedTags as $tag) {
			if ($tag === ITags::TAG_FAVORITE) {
				$this->tagger->removeFromFavorites($fileId, $path);
				continue;
			}
			$this->tagger->unTag($fileId, $tag);
		}

		// TODO: re-read from tagger to make sure the
		// list is up to date, in case of concurrent changes ?
		return $tags;
	}
}
