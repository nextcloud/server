<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
