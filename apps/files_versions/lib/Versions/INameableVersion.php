<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

/**
 * @deprecated 29.0.0
 * @since 26.0.0
 */
interface INameableVersion {
	/**
	 * Get the user created label
	 * @deprecated 29.0.0
	 * @return string
	 * @since 26.0.0
	 */
	public function getLabel(): string;
}
