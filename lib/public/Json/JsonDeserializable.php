<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Json;

/**
 * Interface for objects that can be deserialized from JSON data.
 *
 * @since 33.0.0
 */
interface JsonDeserializable {

	public static function jsonDeserialize(array|string $data): static;

}
