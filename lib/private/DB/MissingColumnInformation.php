<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

class MissingColumnInformation {
	private array $listOfMissingColumns = [];

	public function addHintForMissingColumn(string $tableName, string $columnName): void {
		$this->listOfMissingColumns[] = [
			'tableName' => $tableName,
			'columnName' => $columnName,
		];
	}

	public function getListOfMissingColumns(): array {
		return $this->listOfMissingColumns;
	}
}
