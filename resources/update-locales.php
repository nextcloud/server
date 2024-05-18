<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (!extension_loaded('intl')) {
	echo 'Intl extension is required to run this script.';
	exit(1);
}

require '../3rdparty/autoload.php';

$locales = array_map(static function (string $localeCode) {
	return [
		'code' => $localeCode,
		'name' => Locale::getDisplayName($localeCode, 'en')
	];
}, ResourceBundle::getLocales(''));

$locales = array_filter($locales, static function (array $locale) {
	return is_array(Punic\Data::explodeLocale($locale['code']));
});

$locales = array_values($locales);

if (file_put_contents(__DIR__ . '/locales.json', json_encode($locales, JSON_PRETTY_PRINT)) === false) {
	echo 'Failed to update locales.json';
	exit(1);
}

echo 'Updated locales.json. Don\'t forget to commit the result.';
exit(0);
