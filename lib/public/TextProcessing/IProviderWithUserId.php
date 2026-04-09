<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TextProcessing;

/**
 * This interface allows providers to access the user that initiated the task being run.
 * @since 28.0.0
 * @template T of ITaskType
 * @template-extends IProvider<T>
 * @deprecated 30.0.0
 */
interface IProviderWithUserId extends IProvider {
	/**
	 * @param ?string $userId the current user's id
	 * @since 28.0.0
	 */
	public function setUserId(?string $userId): void;
}
