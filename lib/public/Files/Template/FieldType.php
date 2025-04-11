<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

/**
 * @since 30.0.0
 */
enum FieldType: string {
	case RichText = 'rich-text';
	case CheckBox = 'checkbox';
	case DropDownList = 'drop-down-list';
	case Picture = 'picture';
	case Date = 'date';
}
