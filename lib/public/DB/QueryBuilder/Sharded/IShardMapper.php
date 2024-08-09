<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\QueryBuilder\Sharded;

interface IShardMapper {
	public function getShardForKey(int $key, int $count): int;
}
