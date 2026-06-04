<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TextProcessing;

/**
 * This interface  allows the system to learn the provider's expected runtime
 * @since 28.0.0
 * @template T of ITaskType
 * @template-extends IProvider<T>
 * @deprecated 30.0.0
 */
interface IProviderWithExpectedRuntime extends IProvider {
	/**
	 * @return int The expected average runtime of a task in seconds
	 * @since 28.0.0
	 */
	public function getExpectedRuntime(): int;
}
