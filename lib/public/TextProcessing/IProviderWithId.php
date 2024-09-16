<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TextProcessing;

/**
 * @since 28.0.0
 * @extends IProvider<T>
 * @template T of ITaskType
 * @deprecated 30.0.0
 */
interface IProviderWithId extends IProvider {
	/**
	 * The id of this provider
	 * @since 28.0.0
	 */
	public function getId(): string;
}
