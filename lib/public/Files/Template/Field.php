<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Richdocuments\Template;

class Field {
	private FieldType $type;
	private int $index;
	private string $content;

	public function __construct(FieldType $type) {
		$this->type = $type;
	}
}
