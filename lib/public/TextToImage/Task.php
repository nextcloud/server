<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
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
 */

namespace OCP\TextToImage;

use DateTime;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IImage;
use OCP\Image;

/**
 * This is a text to image task
 *
 * @since 28.0.0
 */
final class Task implements \JsonSerializable {
	protected ?int $id = null;

	protected ?DateTime $completionExpectedAt = null;

	/**
	 * @since 28.0.0
	 */
	public const STATUS_FAILED = 4;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_SUCCESSFUL = 3;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_RUNNING = 2;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_SCHEDULED = 1;
	/**
	 * @since 28.0.0
	 */
	public const STATUS_UNKNOWN = 0;

	/**
	 * @psalm-var self::STATUS_*
	 */
	protected int $status = self::STATUS_UNKNOWN;

	/**
	 * @param string $input
	 * @param string $appId
	 * @param int $numberOfImages
	 * @param string|null $userId
	 * @param null|string $identifier An arbitrary identifier for this task. max length: 255 chars
	 * @since 28.0.0
	 */
	final public function __construct(
		protected string $input,
		protected string $appId,
		protected int $numberOfImages,
		protected ?string $userId,
		protected ?string $identifier = '',
	) {
	}

	/**
	 * @return IImage[]|null
	 * @since 28.0.0
	 */
	final public function getOutputImages(): ?array {
		$appData = \OCP\Server::get(IAppDataFactory::class)->get('core');
		try {
			$folder = $appData->getFolder('text2image')->getFolder((string)$this->getId());
			$images = [];
			for ($i = 0; $i < $this->getNumberOfImages(); $i++) {
				$image = new Image();
				$image->loadFromFileHandle($folder->getFile((string) $i)->read());
				$images[] = $image;
			}
			return $images;
		} catch (NotFoundException|NotPermittedException) {
			return null;
		}
	}

	/**
	 * @return int
	 * @since 28.0.0
	 */
	final public function getNumberOfImages(): int {
		return $this->numberOfImages;
	}

	/**
	 * @psalm-return self::STATUS_*
	 * @since 28.0.0
	 */
	final public function getStatus(): int {
		return $this->status;
	}

	/**
	 * @psalm-param self::STATUS_* $status
	 * @since 28.0.0
	 */
	final public function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @param ?DateTime $at
	 * @since 28.0.0
	 */
	final public function setCompletionExpectedAt(?DateTime $at): void {
		$this->completionExpectedAt = $at;
	}

	/**
	 * @return ?DateTime
	 * @since 28.0.0
	 */
	final public function getCompletionExpectedAt(): ?DateTime {
		return $this->completionExpectedAt;
	}

	/**
	 * @return int|null
	 * @since 28.0.0
	 */
	final public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @param int|null $id
	 * @since 28.0.0
	 */
	final public function setId(?int $id): void {
		$this->id = $id;
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	final public function getInput(): string {
		return $this->input;
	}

	/**
	 * @return string
	 * @since 28.0.0
	 */
	final public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @return null|string
	 * @since 28.0.0
	 */
	final public function getIdentifier(): ?string {
		return $this->identifier;
	}

	/**
	 * @return string|null
	 * @since 28.0.0
	 */
	final public function getUserId(): ?string {
		return $this->userId;
	}

	/**
	 * @psalm-return array{id: ?int, status: self::STATUS_*, userId: ?string, appId: string, input: string, identifier: ?string, numberOfImages: int, completionExpectedAt: ?int}
	 * @since 28.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'status' => $this->getStatus(),
			'userId' => $this->getUserId(),
			'appId' => $this->getAppId(),
			'numberOfImages' => $this->getNumberOfImages(),
			'input' => $this->getInput(),
			'identifier' => $this->getIdentifier(),
			'completionExpectedAt' => $this->getCompletionExpectedAt()->getTimestamp(),
		];
	}
}
