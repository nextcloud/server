<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

class MissingPrimaryKeyInformation {
	private array $listOfMissingPrimaryKeys = [];

	public function addHintForMissingPrimaryKey(string $tableName): void {
		$this->listOfMissingPrimaryKeys[] = [
			'tableName' => $tableName,
		];
	}

	public function getListOfMissingPrimaryKeys(): array {
		return $this->listOfMissingPrimaryKeys;
	}
}
