<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use OCP\Settings\IIconSection;

class Section implements IIconSection {
	/** @var string */
	private $id;
	/** @var string */
	private $name;
	/** @var int */
	private $priority;
	/** @var string */
	private $icon;

	/**
	 * @param string $id
	 * @param string $name
	 * @param int $priority
	 * @param string $icon
	 */
	public function __construct($id, $name, $priority, $icon = '') {
		$this->id = $id;
		$this->name = $name;
		$this->priority = $priority;
		$this->icon = $icon;
	}

	public function getID() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getPriority() {
		return $this->priority;
	}

	public function getIcon() {
		return $this->icon;
	}
}
