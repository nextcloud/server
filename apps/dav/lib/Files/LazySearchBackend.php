<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
