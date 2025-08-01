<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Collaborators;

use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;

class SearchResult implements ISearchResult {
	protected array $result = [
		'exact' => [],
	];

	protected array $exactIdMatches = [];

	public function addResultSet(SearchResultType $type, array $matches, ?array $exactMatches = null): void {
		$type = $type->getLabel();
		if (!isset($this->result[$type])) {
			$this->result[$type] = [];
			$this->result['exact'][$type] = [];
		}

		$this->result[$type] = array_merge($this->result[$type], $matches);
		if (is_array($exactMatches)) {
			$this->result['exact'][$type] = array_merge($this->result['exact'][$type], $exactMatches);
		}
	}

	public function markExactIdMatch(SearchResultType $type): void {
		$this->exactIdMatches[$type->getLabel()] = 1;
	}

	public function hasExactIdMatch(SearchResultType $type): bool {
		return isset($this->exactIdMatches[$type->getLabel()]);
	}

	public function hasResult(SearchResultType $type, $collaboratorId): bool {
		$type = $type->getLabel();
		if (!isset($this->result[$type])) {
			return false;
		}

		$resultArrays = [$this->result['exact'][$type], $this->result[$type]];
		foreach ($resultArrays as $resultArray) {
			foreach ($resultArray as $result) {
				if ($result['value']['shareWith'] === $collaboratorId) {
					return true;
				}
			}
		}

		return false;
	}

	public function asArray(): array {
		return $this->result;
	}

	public function unsetResult(SearchResultType $type): void {
		$type = $type->getLabel();
		$this->result[$type] = [];
		if (isset($this->result['exact'][$type])) {
			$this->result['exact'][$type] = [];
		}
	}

	public function removeCollaboratorResult(SearchResultType $type, string $collaboratorId): bool {
		$type = $type->getLabel();
		if (!isset($this->result[$type])) {
			return false;
		}

		$actionDone = false;
		$resultArrays = [&$this->result['exact'][$type], &$this->result[$type]];
		foreach ($resultArrays as &$resultArray) {
			foreach ($resultArray as $k => $result) {
				if ($result['value']['shareWith'] === $collaboratorId) {
					unset($resultArray[$k]);
					$actionDone = true;
				}
			}
		}

		return $actionDone;
	}
}
