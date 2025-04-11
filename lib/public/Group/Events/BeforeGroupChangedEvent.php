<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group\Events;

use OCP\EventDispatcher\Event;
use OCP\IGroup;

/**
 * @since 26.0.0
 */
class BeforeGroupChangedEvent extends Event {
	private IGroup $group;
	private string $feature;
	/** @var mixed */
	private $value;
	/** @var mixed */
	private $oldValue;

	/**
	 * @since 26.0.0
	 */
	public function __construct(IGroup $group,
		string $feature,
		$value,
		$oldValue = null) {
		parent::__construct();
		$this->group = $group;
		$this->feature = $feature;
		$this->value = $value;
		$this->oldValue = $oldValue;
	}

	/**
	 *
	 * @since 26.0.0
	 *
	 * @return IGroup
	 */
	public function getGroup(): IGroup {
		return $this->group;
	}

	/**
	 *
	 * @since 26.0.0
	 *
	 * @return string
	 */
	public function getFeature(): string {
		return $this->feature;
	}

	/**
	 * @since 26.0.0
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 *
	 * @since 26.0.0
	 *
	 * @return mixed
	 */
	public function getOldValue() {
		return $this->oldValue;
	}
}
