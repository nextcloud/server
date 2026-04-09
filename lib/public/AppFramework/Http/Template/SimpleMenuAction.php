<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http\Template;

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
	 * The icon CSS class to use.
	 *
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
	 * Custom render function.
	 * The returned HTML must be wrapped within a listitem (`<li>...</li>`).
	 * * If an empty string is returned, the default design is used (based on the label and link specified).
	 * @return string
	 * @since 14.0.0
	 */
	public function render(): string {
		return '';
	}

	/**
	 * Return JSON data to let the frontend render the menu entry.
	 * @return array{id: string, label: string, href: string, icon: string, details: string|null}
	 * @since 31.0.0
	 */
	public function getData(): array {
		return [
			'id' => $this->id,
			'label' => $this->label,
			'href' => $this->link,
			'icon' => $this->icon,
			'details' => $this->detail,
		];
	}
}
