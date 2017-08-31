<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Collaboration\Collaborators;


use OCP\Collaboration\Collaborators\ISearchResult;

class SearchResult implements ISearchResult {

	protected $result = [
		'exact' => [
			'users' => [],
			'groups' => [],
			'remotes' => [],
			'emails' => [],
			'circles' => [],
		],
		'users' => [],
		'groups' => [],
		'remotes' => [],
		'emails' => [],
		'lookup' => [],
		'circles' => [],
	];

	protected $exactIdMatches = [];

	public function addResultSet($type, array $matches, array $exactMatches = null) {
		if(!isset($this->result[$type])) {
			throw new \InvalidArgumentException('Invalid type provided');
		}

		$this->result[$type] = array_merge($this->result[$type], $matches);
		if(is_array($exactMatches)) {
			$this->result['exact'][$type] = array_merge($this->result['exact'][$type], $exactMatches);
		}
	}

	public function markExactIdMatch($type) {
		$this->exactIdMatches[$type] = 1;
	}

	public function hasExactIdMatch($type) {
		return isset($this->exactIdMatches[$type]);
	}

	public function hasResult($type, $collaboratorId) {
		if(!isset($this->result[$type])) {
			throw new \InvalidArgumentException('Invalid type provided');
		}

		$resultArrays = [$this->result['exact'][$type], $this->result[$type]];
		foreach($resultArrays as $resultArray) {
			if ($resultArray['value']['shareWith'] === $collaboratorId) {
				return true;
			}
		}

		return false;
	}

	public function asArray() {
		return $this->result;
	}

	public function unsetResult($type) {
		if(!isset($this->result[$type])) {
			throw new \InvalidArgumentException('Invalid type provided');
		}

		$this->result[$type] = [];
		if(isset($this->$result['exact'][$type])) {
			$this->result['exact'][$type] = [];
		}
	}
}
