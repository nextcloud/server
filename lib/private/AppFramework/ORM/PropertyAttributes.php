<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\AppFramework\ORM\Attribute\JoinColumn;
use OCP\AppFramework\ORM\Attribute\OneToOne;

class PropertyAttributes {
	public ?Id $id = null;
	public ?Column $column = null;
	public ?OneToOne $oneToOne = null;
	public ?JoinColumn $joinColumn = null;

	public function __construct(
		public readonly \ReflectionProperty $property,
	) {
	}
}
