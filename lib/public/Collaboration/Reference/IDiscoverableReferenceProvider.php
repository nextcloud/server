<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Reference;

/**
 * @since 26.0.0
 */
interface IDiscoverableReferenceProvider extends IReferenceProvider {
	/**
	 * @return string Unique id that identifies the reference provider
	 * @since 26.0.0
	 */
	public function getId(): string;

	/**
	 * @return string User facing title of the widget
	 * @since 26.0.0
	 */
	public function getTitle(): string;

	/**
	 * @return int Initial order for reference provider sorting
	 * @since 26.0.0
	 */
	public function getOrder(): int;

	/**
	 * @return string url to an icon that can be displayed next to the reference provider title
	 * @since 26.0.0
	 */
	public function getIconUrl(): string;

	/**
	 * @return array representation of the provider
	 * @since 26.0.0
	 */
	public function jsonSerialize(): array;
}
