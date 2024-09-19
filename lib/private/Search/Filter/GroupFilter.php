<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Search\Filter;

use InvalidArgumentException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\Search\IFilter;

class GroupFilter implements IFilter {
	private IGroup $group;

	public function __construct(
		string $value,
		IGroupManager $groupManager,
	) {
		$group = $groupManager->get($value);
		if ($group === null) {
			throw new InvalidArgumentException('Group ' . $value . ' not found');
		}
		$this->group = $group;
	}

	public function get(): IGroup {
		return $this->group;
	}
}
