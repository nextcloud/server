<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Search;

use OCP\Files\FileInfo;
use OCP\Files\Search\ISearchOrder;

class SearchOrder implements ISearchOrder {
	public function __construct(
		private string $direction,
		private string $field,
		private string $extra = '',
	) {
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getDirection(): string {
		return $this->direction;
	}

	/**
	 * @return string
	 */
	#[\Override]
	public function getField(): string {
		return $this->field;
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	#[\Override]
	public function getExtra(): string {
		return $this->extra;
	}

	#[\Override]
	public function sortFileInfo(FileInfo $a, FileInfo $b): int {
		$cmp = $this->sortFileInfoNoDirection($a, $b);
		return $cmp * ($this->direction === ISearchOrder::DIRECTION_ASCENDING ? 1 : -1);
	}

	private function sortFileInfoNoDirection(FileInfo $a, FileInfo $b): int {
		switch ($this->field) {
			case 'name':
				return $a->getName() <=> $b->getName();
			case 'mimetype':
				return $a->getMimetype() <=> $b->getMimetype();
			case 'mtime':
				return $a->getMtime() <=> $b->getMtime();
			case 'size':
				return $a->getSize() <=> $b->getSize();
			case 'fileid':
				return $a->getId() <=> $b->getId();
			case 'permissions':
				return $a->getPermissions() <=> $b->getPermissions();
			case 'last_activity':
				return $a->getLastActivity() <=> $b->getLastActivity();
			default:
				return 0;
		}
	}
}
