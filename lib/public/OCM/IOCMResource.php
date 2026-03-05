<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM;

use JsonSerializable;

/**
 * Model based on the Open Cloud Mesh Discovery API
 *
 * @link https://github.com/cs3org/OCM-API/
 * @since 28.0.0
 */
interface IOCMResource extends JsonSerializable {
	/**
	 * set name of the resource
	 *
	 * @param string $name
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function setName(string $name): static;

	/**
	 * get name of the resource
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getName(): string;

	/**
	 * set share types
	 *
	 * @param list<string> $shareTypes
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function setShareTypes(array $shareTypes): static;

	/**
	 * get share types
	 *
	 * @return list<string>
	 * @since 28.0.0
	 */
	public function getShareTypes(): array;

	/**
	 * set available protocols
	 *
	 * @param array<string, string> $protocols
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function setProtocols(array $protocols): static;

	/**
	 * get configured protocols
	 *
	 * @return array<string, string>
	 * @since 28.0.0
	 */
	public function getProtocols(): array;

	/**
	 * import data from an array
	 *
	 * @param array $data
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function import(array $data): static;

	/**
	 * @return array{
	 *     name: string,
	 *     shareTypes: list<string>,
	 *     protocols: array<string, string>
	 * }
	 * @since 28.0.0
	 */
	public function jsonSerialize(): array;
}
