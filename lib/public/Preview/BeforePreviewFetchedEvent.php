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
use OCP\IPreview;

/**
 * @since 25.0.1
 */
class BeforePreviewFetchedEvent extends \OCP\EventDispatcher\Event {
	/**
	 * @since 25.0.1
	 */
	public function __construct(
		private Node $node,
		/** @deprecated 28.0.0 null deprecated **/
		private ?int $width = null,
		/** @deprecated 28.0.0 null deprecated **/
		private ?int $height = null,
		/** @deprecated 28.0.0 null deprecated **/
		private ?bool $crop = null,
		/** @deprecated 28.0.0 null deprecated **/
		private ?string $mode = null,
	) {
		parent::__construct();
	}

	/**
	 * @since 25.0.1
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * @since 28.0.0
	 */
	public function getWidth(): ?int {
		return $this->width;
	}

	/**
	 * @since 28.0.0
	 */
	public function getHeight(): ?int {
		return $this->height;
	}

	/**
	 * @since 28.0.0
	 */
	public function isCrop(): ?bool {
		return $this->crop;
	}

	/**
	 * @since 28.0.0
	 * @return null|IPreview::MODE_FILL|IPreview::MODE_COVER
	 */
	public function getMode(): ?string {
		return $this->mode;
	}
}
