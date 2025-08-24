<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Contacts\ContactsMenu\Actions;

use OCP\Contacts\ContactsMenu\ILinkAction;

class LinkAction implements ILinkAction {
	private string $icon = '';
	private string $name = '';
	private string $href = '';
	private int $priority = 10;
	private string $appId = '';

	/**
	 * @param string $icon absolute URI to an icon
	 */
	public function setIcon(string $icon): void {
		$this->icon = $icon;
	}

	public function setName(string $name): void {
		$this->name = $name;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setPriority(int $priority): void {
		$this->priority = $priority;
	}

	public function getPriority(): int {
		return $this->priority;
	}

	public function setHref(string $href): void {
		$this->href = $href;
	}

	public function getHref(): string {
		return $this->href;
	}

	/**
	 * @since 23.0.0
	 */
	public function setAppId(string $appId): void {
		$this->appId = $appId;
	}

	/**
	 * @since 23.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @return array{title: string, icon: string, hyperlink: string, appId: string}
	 */
	public function jsonSerialize(): array {
		return [
			'title' => $this->name,
			'icon' => $this->icon,
			'hyperlink' => $this->href,
			'appId' => $this->appId,
		];
	}
}
