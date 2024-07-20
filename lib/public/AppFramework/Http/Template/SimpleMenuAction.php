<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http\Template;

use OCP\Util;

/**
 * Class SimpleMenuAction
 *
 * @since 14.0.0
 */
class SimpleMenuAction implements IMenuAction {
	/** @var string */
	private $id;

	/** @var string */
	private $label;

	/** @var string */
	private $icon;

	/** @var string */
	private $link;

	/** @var int */
	private $priority;

	/** @var string */
	private $detail;

	/**
	 * SimpleMenuAction constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $icon
	 * @param string $link
	 * @param int $priority
	 * @param string $detail
	 * @since 14.0.0
	 */
	public function __construct(string $id, string $label, string $icon, string $link = '', int $priority = 100, string $detail = '') {
		$this->id = $id;
		$this->label = $label;
		$this->icon = $icon;
		$this->link = $link;
		$this->priority = $priority;
		$this->detail = $detail;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function getIcon(): string {
		return $this->icon;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function getLink(): string {
		return $this->link;
	}

	/**
	 * @return int
	 * @since 14.0.0
	 */
	public function getPriority(): int {
		return $this->priority;
	}

	/**
	 * @return string
	 * @since 14.0.0
	 */
	public function render(): string {
		$detailContent = ($this->detail !== '') ? '&nbsp;<span class="download-size">(' . Util::sanitizeHTML($this->detail) . ')</span>' : '';
		return sprintf(
			'<li id="%s"><a href="%s"><span class="icon %s"></span>%s %s</a></li>',
			Util::sanitizeHTML($this->id), Util::sanitizeHTML($this->link), Util::sanitizeHTML($this->icon), Util::sanitizeHTML($this->label), $detailContent
		);
	}
}
