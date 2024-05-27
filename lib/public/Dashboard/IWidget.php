<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

/**
 * Interface IWidget
 *
 * @since 20.0.0
 */
interface IWidget {
	/**
	 * @return string Unique id that identifies the widget, e.g. the app id
	 * @since 20.0.0
	 */
	public function getId(): string;

	/**
	 * @return string User facing title of the widget
	 * @since 20.0.0
	 */
	public function getTitle(): string;

	/**
	 * @return int Initial order for widget sorting
	 * @since 20.0.0
	 */
	public function getOrder(): int;

	/**
	 * @return string css class that displays an icon next to the widget title
	 * @since 20.0.0
	 */
	public function getIconClass(): string;

	/**
	 * @return string|null The absolute url to the apps own view
	 * @since 20.0.0
	 */
	public function getUrl(): ?string;

	/**
	 * Execute widget bootstrap code like loading scripts and providing initial state
	 * @since 20.0.0
	 */
	public function load(): void;
}
