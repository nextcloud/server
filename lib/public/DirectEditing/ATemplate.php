<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DirectEditing;

use JsonSerializable;

/**
 * Class ATemplate
 *
 * @since 18.0.0
 */
abstract class ATemplate implements JsonSerializable {
	/**
	 * Return a unique id so the app can identify the template
	 *
	 * @since 18.0.0
	 * @return string
	 */
	abstract public function getId(): string;

	/**
	 * Return a title that is displayed to the user
	 *
	 * @since 18.0.0
	 * @return string
	 */
	abstract public function getTitle(): string;

	/**
	 * Return a link to the template preview image
	 *
	 * @since 18.0.0
	 * @return string
	 */
	abstract public function getPreview(): string;

	/**
	 * @since 18.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'preview' => $this->getPreview(),
		];
	}
}
