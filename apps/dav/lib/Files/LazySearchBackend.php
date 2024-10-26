<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Files;

use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Query\Query;

class LazySearchBackend implements ISearchBackend {
	/**
	 * @var ISearchBackend $backend
	 */
	private $backend = null;

	public function setBackend(ISearchBackend $backend) {
		$this->backend = $backend;
	}

	public function getArbiterPath(): string {
		if ($this->backend) {
			return $this->backend->getArbiterPath();
		} else {
			return '';
		}
	}

	public function isValidScope(string $href, $depth, ?string $path): bool {
		if ($this->backend) {
			return $this->backend->getArbiterPath();
		}
		return false;
	}

	public function getPropertyDefinitionsForScope(string $href, ?String $path): array {
		if ($this->backend) {
			return $this->backend->getPropertyDefinitionsForScope($href, $path);
		}
		return [];
	}

	public function search(Query $query): array {
		if ($this->backend) {
			return $this->backend->search($query);
		}
		return [];
	}

	public function preloadPropertyFor(array $nodes, array $requestProperties): void {
		if ($this->backend) {
			$this->backend->preloadPropertyFor($nodes, $requestProperties);
		}
	}
}
