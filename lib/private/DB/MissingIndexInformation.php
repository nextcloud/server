<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

class MissingIndexInformation {
	private array $listOfMissingIndices = [];

	public function addHintForMissingIndex(string $tableName, string $indexName): void {
		$this->listOfMissingIndices[] = [
			'tableName' => $tableName,
			'indexName' => $indexName
		];
	}

	public function getListOfMissingIndices(): array {
		return $this->listOfMissingIndices;
	}
}
