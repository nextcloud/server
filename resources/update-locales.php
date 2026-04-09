<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
