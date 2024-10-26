<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Preview;

/**
 * Interface IMimeIconProvider
 *
 * @since 28.0.0
 */
interface IMimeIconProvider {
	/**
	 * Get the URL to the icon for the given mime type
	 * Used by the preview provider to show a mime icon
	 * if no preview is available.
	 * @since 28.0.0
	 */
	public function getMimeIconUrl(string $mime): ?string;
}
