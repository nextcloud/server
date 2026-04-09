<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\RichObjectStrings;

/**
 * Parse rich text and format it with the richobjects
 *
 * @since 31.0.0
 */
interface IRichTextFormatter {
	/**
	 * @since 31.0.0
	 * @param string $message
	 * @param array<string,array<string,string>> $parameters
	 * @throws \InvalidArgumentException if a parameter has no name or no type
	 */
	public function richToParsed(string $message, array $parameters): string;
}
