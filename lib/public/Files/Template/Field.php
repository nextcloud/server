<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

class Field {
	public FieldType $type;

	public function __construct(FieldType $type) {
		$this->type = $type;
	}
}
