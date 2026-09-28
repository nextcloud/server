<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

/**
 * Interface IManager
 *
 * @since 20.0.0
 */
interface IManager {
	/**
	 * @param string $widgetClass
	 * @since 20.0.0
	 */
	public function lazyRegisterWidget(string $widgetClass, string $appId): void;

	/**
	 * @since 20.0.0
	 *
	 * @return array<string, IWidget>
	 */
	public function getWidgets(): array;
}
