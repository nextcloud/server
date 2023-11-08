<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OC\Contacts\ContactsMenu;

use OCP\Contacts\ContactsMenu\IAction;
use OCP\Contacts\ContactsMenu\IEntry;
use function array_merge;

class Entry implements IEntry {
	public const PROPERTY_STATUS_MESSAGE_TIMESTAMP = 'statusMessageTimestamp';

	/** @var string|int|null */
	private $id = null;

	private string $fullName = '';

	/** @var string[] */
	private array $emailAddresses = [];

	private ?string $avatar = null;

	private ?string $profileTitle = null;

	private ?string $profileUrl = null;

	/** @var IAction[] */
	private array $actions = [];

	private array $properties = [];

	private ?string $status = null;
	private ?string $statusMessage = null;
	private ?int $statusMessageTimestamp = null;
	private ?string $statusIcon = null;

	public function setId(string $id): void {
		$this->id = $id;
	}

	public function setFullName(string $displayName): void {
		$this->fullName = $displayName;
	}

	public function getFullName(): string {
		return $this->fullName;
	}

	public function addEMailAddress(string $address): void {
		$this->emailAddresses[] = $address;
	}

	/**
	 * @return string[]
	 */
	public function getEMailAddresses(): array {
		return $this->emailAddresses;
	}

	public function setAvatar(string $avatar): void {
		$this->avatar = $avatar;
	}

	public function getAvatar(): ?string {
		return $this->avatar;
	}

	public function setProfileTitle(string $profileTitle): void {
		$this->profileTitle = $profileTitle;
	}

	public function getProfileTitle(): ?string {
		return $this->profileTitle;
	}

	public function setProfileUrl(string $profileUrl): void {
		$this->profileUrl = $profileUrl;
	}

	public function getProfileUrl(): ?string {
		return $this->profileUrl;
	}

	public function addAction(IAction $action): void {
		$this->actions[] = $action;
		$this->sortActions();
	}

	public function setStatus(string $status,
		string $statusMessage = null,
		int $statusMessageTimestamp = null,
		string $icon = null): void {
		$this->status = $status;
		$this->statusMessage = $statusMessage;
		$this->statusMessageTimestamp = $statusMessageTimestamp;
		$this->statusIcon = $icon;
	}

	/**
	 * @return IAction[]
	 */
	public function getActions(): array {
		return $this->actions;
	}

	/**
	 * sort the actions by priority and name
	 */
	private function sortActions(): void {
		usort($this->actions, function (IAction $action1, IAction $action2) {
			$prio1 = $action1->getPriority();
			$prio2 = $action2->getPriority();

			if ($prio1 === $prio2) {
				// Ascending order for same priority
				return strcasecmp($action1->getName(), $action2->getName());
			}

			// Descending order when priority differs
			return $prio2 - $prio1;
		});
	}

	public function setProperty(string $propertyName, mixed $value) {
		$this->properties[$propertyName] = $value;
	}

	/**
	 * @param array $properties key-value array containing additional properties
	 */
	public function setProperties(array $properties): void {
		$this->properties = array_merge($this->properties, $properties);
	}

	public function getProperty(string $key): mixed {
		if (!isset($this->properties[$key])) {
			return null;
		}
		return $this->properties[$key];
	}

	/**
	 * @return array{id: int|string|null, fullName: string, avatar: string|null, topAction: mixed, actions: array, lastMessage: '', emailAddresses: string[], profileTitle: string|null, profileUrl: string|null, status: string|null, statusMessage: null|string, statusMessageTimestamp: null|int, statusIcon: null|string, isUser: bool, uid: mixed}
	 */
	public function jsonSerialize(): array {
		$topAction = !empty($this->actions) ? $this->actions[0]->jsonSerialize() : null;
		$otherActions = array_map(function (IAction $action) {
			return $action->jsonSerialize();
		}, array_slice($this->actions, 1));

		return [
			'id' => $this->id,
			'fullName' => $this->fullName,
			'avatar' => $this->getAvatar(),
			'topAction' => $topAction,
			'actions' => $otherActions,
			'lastMessage' => '',
			'emailAddresses' => $this->getEMailAddresses(),
			'profileTitle' => $this->profileTitle,
			'profileUrl' => $this->profileUrl,
			'status' => $this->status,
			'statusMessage' => $this->statusMessage,
			'statusMessageTimestamp' => $this->statusMessageTimestamp,
			'statusIcon' => $this->statusIcon,
			'isUser' => $this->getProperty('isUser') === true,
			'uid' => $this->getProperty('UID'),
		];
	}

	public function getStatusMessage(): ?string {
		return $this->statusMessage;
	}

	public function getStatusMessageTimestamp(): ?int {
		return $this->statusMessageTimestamp;
	}
}
