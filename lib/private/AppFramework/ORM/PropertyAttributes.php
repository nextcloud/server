<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\AppFramework\ORM\Attribute\JoinColumn;
use OCP\AppFramework\ORM\Attribute\ManyToOne;
use OCP\AppFramework\ORM\Attribute\OneToOne;

class PropertyAttributes {
	public ?Id $id = null;
	public ?Column $column = null;
	public ?OneToOne $oneToOne = null;
	public ?ManyToOne $manyToOne = null;
	public ?JoinColumn $joinColumn = null;

	public function __construct(
		public readonly \ReflectionProperty $property,
	) {
	}

	public function getOwningRelationTarget(): ?string {
		if ($this->joinColumn === null) {
			return null;
		}
		if ($this->manyToOne !== null) {
			return $this->manyToOne->targetEntity;
		}
		if ($this->oneToOne !== null && $this->oneToOne->invertedBy !== null) {
			return $this->oneToOne->targetEntity;
		}
		return null;
	}

	public function isRelation(): bool {
		return $this->oneToOne !== null || $this->manyToOne !== null;
	}
}
