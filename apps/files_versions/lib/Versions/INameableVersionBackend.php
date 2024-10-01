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
interface INameableVersionBackend {
	/**
	 * Set the label for a version.
	 * @deprecated 29.0.0
	 * @since 26.0.0
	 */
	public function setVersionLabel(IVersion $version, string $label): void;
}
