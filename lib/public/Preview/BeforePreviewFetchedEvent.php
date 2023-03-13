<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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


namespace OCP\Preview;

use OCP\Files\Node;

/**
 * @since 25.0.1
 */
class BeforePreviewFetchedEvent extends \OCP\EventDispatcher\Event {
	private Node $node;
	private int $width;
	private int $height;
	private bool $crop;
	private string $mode;
	private ?string $mimeType;

	/**
	 * @since 25.0.1
	 */
	public function __construct(Node $node, int $width, int $height, bool $crop, string $mode, ?string $mimeType) {
		parent::__construct();
		$this->node = $node;
		$this->width = $width;
		$this->height = $height;
		$this->crop = $crop;
		$this->mode = $mode;
		$this->mimeType = $mimeType;
	}

	/**
	 * @since 25.0.1
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * @since 27.0.0
	 */
	public function getWidth(): int {
		return $this->width;
	}

	/**
	 * @since 27.0.0
	 */
	public function getHeight(): int {
		return $this->height;
	}

	/**
	 * @since 27.0.0
	 */
	public function isCrop(): bool {
		return $this->crop;
	}

	/**
	 * @since 27.0.0
	 */
	public function getMode(): string {
		return $this->mode;
	}

	/**
	 * @since 27.0.0
	 */
	public function getMimeType(): ?string {
		return $this->mimeType;
	}
}
