<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OCP;

/**
 * TODO: Description
 */
interface IL10N {
	/**
	 * @brief Translating
	 * @param $text String The text we need a translation for
	 * @param array $parameters default:array() Parameters for sprintf
	 * @return \OC_L10N_String|string Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $text will be
	 * returned.
	 */
	public function t($text, $parameters = array());

	/**
	 * @brief Translating
	 * @param $text_singular String the string to translate for exactly one object
	 * @param $text_plural String the string to translate for n objects
	 * @param $count Integer Number of objects
	 * @param array $parameters default:array() Parameters for sprintf
	 * @return \OC_L10N_String|string Translation or the same text
	 *
	 * Returns the translation. If no translation is found, $text will be
	 * returned. %n will be replaced with the number of objects.
	 *
	 * The correct plural is determined by the plural_forms-function
	 * provided by the po file.
	 *
	 */
	public function n($text_singular, $text_plural, $count, $parameters = array());

	/**
	 * @brief Localization
	 * @param $type Type of localization
	 * @param $params parameters for this localization
	 * @returns String or false
	 *
	 * Returns the localized data.
	 *
	 * Implemented types:
	 *  - date
	 *    - Creates a date
	 *    - l10n-field: date
	 *    - params: timestamp (int/string)
	 *  - datetime
	 *    - Creates date and time
	 *    - l10n-field: datetime
	 *    - params: timestamp (int/string)
	 *  - time
	 *    - Creates a time
	 *    - l10n-field: time
	 *    - params: timestamp (int/string)
	 */
	public function l($type, $data);
}
