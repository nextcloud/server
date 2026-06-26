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
	/**
	 * @since 30.0.0
	 */
	case RichText = 'rich-text';
	/**
	 * @since 30.0.0
	 */
	case CheckBox = 'checkbox';
	/**
	 * @since 30.0.0
	 */
	case DropDownList = 'drop-down-list';
	/**
	 * @since 30.0.0
	 */
	case Picture = 'picture';
	/**
	 * @since 30.0.0
	 */
	case Date = 'date';
}
