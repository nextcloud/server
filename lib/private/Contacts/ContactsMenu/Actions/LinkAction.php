<?php
/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
