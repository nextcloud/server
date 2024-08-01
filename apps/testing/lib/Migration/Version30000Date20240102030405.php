<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Migration;

use Closure;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\DropTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[DropTable(table: 'old_table')]
#[CreateTable(table: 'new_table', description: 'Table is used to store things, but also to get more things', notes: ['this is a notice', 'and another one, if really needed'])]
#[AddColumn(table: 'my_table')]
#[AddColumn(table: 'my_table', name: 'another_field')]
#[AddColumn(table: 'other_table', name: 'last_one', type: ColumnType::DATE)]
#[AddIndex(table: 'my_table')]
#[AddIndex(table: 'my_table', type: IndexType::PRIMARY)]
#[DropColumn(table: 'other_table')]
#[DropColumn(table: 'other_table', name: 'old_column', description: 'field is not used anymore and replaced by \'last_one\'')]
#[DropIndex(table: 'other_table')]
#[ModifyColumn(table: 'other_table')]
#[ModifyColumn(table: 'other_table', name: 'this_field')]
#[ModifyColumn(table: 'other_table', name: 'this_field', type: ColumnType::BIGINT)]
class Version30000Date20240102030405 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		return null;
	}
}
