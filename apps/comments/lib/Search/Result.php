<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Search;

use OCP\Comments\IComment;
use OCP\Files\NotFoundException;
use OCP\Search\Result as BaseResult;

/**
 * @deprecated 20.0.0
 */
class Result extends BaseResult {
	/**
	 * @deprecated 20.0.0
	 */
	public $type = 'comment';
	/**
	 * @deprecated 20.0.0
	 */
	public $comment;
	/**
	 * @deprecated 20.0.0
	 */
	public $authorId;
	/**
	 * @deprecated 20.0.0
	 */
	public $path;
	/**
	 * @deprecated 20.0.0
	 */
	public $fileName;
	/**
	 * @deprecated 20.0.0
	 */
	public int $fileId;

	/**
	 * @throws NotFoundException
	 * @deprecated 20.0.0
	 */
	public function __construct(
		string $search,
		IComment $comment,
		/**
		 * @deprecated 20.0.0
		 */
		public string $authorName,
		string $path,
		int $fileId,
	) {
		parent::__construct(
			$comment->getId(),
			$comment->getMessage()
			/* @todo , [link to file] */
		);

		$this->comment = $this->getRelevantMessagePart($comment->getMessage(), $search);
		$this->authorId = $comment->getActorId();
		$this->fileName = basename($path);
		$this->path = $this->getVisiblePath($path);
		$this->fileId = $fileId;
	}

	/**
	 * @throws NotFoundException
	 */
	protected function getVisiblePath(string $path): string {
		$segments = explode('/', trim($path, '/'), 3);

		if (!isset($segments[2])) {
			throw new NotFoundException('Path not inside visible section');
		}

		return $segments[2];
	}

	/**
	 * @throws NotFoundException
	 */
	protected function getRelevantMessagePart(string $message, string $search): string {
		$start = mb_stripos($message, $search);
		if ($start === false) {
			throw new NotFoundException('Comment section not found');
		}

		$end = $start + mb_strlen($search);

		if ($start <= 25) {
			$start = 0;
			$prefix = '';
		} else {
			$start -= 25;
			$prefix = '…';
		}

		if ((mb_strlen($message) - $end) <= 25) {
			$end = mb_strlen($message);
			$suffix = '';
		} else {
			$end += 25;
			$suffix = '…';
		}

		return $prefix . mb_substr($message, $start, $end - $start) . $suffix;
	}
}
