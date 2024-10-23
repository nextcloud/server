<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$directories = [
	__DIR__ . '/../core/l10n',
];

$isDebug = in_array('--debug', $argv, true) || in_array('-d', $argv, true);

$txConfig = file_get_contents(__DIR__ . '/../.tx/config');

$untranslatedApps = [
	'testing',
];

// Next line only looks messed up, but it works. Don't touch it!
$rtlCharacters = [
	'\x{061C}', // ARABIC LETTER MARK
	'\x{0623}', // ARABIC LETTER ALEF WITH HAMZA ABOVE
	'\x{200E}', // LEFT-TO-RIGHT MARK
	'\x{200F}', // RIGHT-TO-LEFT MARK
	'\x{202A}', // LEFT-TO-RIGHT EMBEDDING
	'\x{202B}', // RIGHT-TO-LEFT EMBEDDING
	'\x{202C}', // POP DIRECTIONAL FORMATTING
	'\x{202D}', // LEFT-TO-RIGHT OVERRIDE
	'\x{202E}', // RIGHT-TO-LEFT OVERRIDE
	'\x{2066}', // LEFT-TO-RIGHT ISOLATE
	'\x{2067}', // RIGHT-TO-LEFT ISOLATE
	'\x{2068}', // FIRST STRONG ISOLATE
	'\x{2069}', // POP DIRECTIONAL ISOLATE
	'\x{206C}', // INHIBIT ARABIC FORM SHAPING
	'\x{206D}', // ACTIVATE ARABIC FORM SHAPING
];

$rtlLanguages = [
	'ar', // Arabic
	'fa', // Persian
	'he', // Hebrew
	'ps', // Pashto,
	'ug', // 'Uyghurche / Uyghur
	'ur_PK', // Urdu
];

$valid = 0;
$errors = [];
$apps = new \DirectoryIterator(__DIR__ . '/../apps');
foreach ($apps as $app) {
	if ($app->isDot() || in_array($app->getBasename(), $untranslatedApps, true)) {
		continue;
	}

	if (!file_exists($app->getPathname() . '/l10n')) {
		if (!str_contains($txConfig, '[o:nextcloud:p:nextcloud:r:' . $app->getBasename() . ']')) {
			$errors[] = $app->getBasename() . "\n" . '  App is not translation synced via transifex and also not marked as untranslated' . "\n";
		}
		continue;
	}

	$directories[] = $app->getPathname() . '/l10n';
}

foreach ($directories as $dir) {
	if (!file_exists($dir)) {
		continue;
	}
	$directory = new \DirectoryIterator($dir);

	foreach ($directory as $file) {
		if ($file->getExtension() !== 'json') {
			continue;
		}

		$content = file_get_contents($file->getPathname());

		$language = pathinfo($file->getFilename(), PATHINFO_FILENAME);
		if (!in_array($language, $rtlLanguages, true) && preg_match_all('/^(.+[' . implode('', $rtlCharacters) . '].+)$/mu', $content, $matches)) {
			$errors[] = $file->getPathname() . "\n" . '  ' . 'Contains a RTL limited characters in the translations. Offending strings:' . "\n" . implode("\n", $matches[0]) . "\n";
		}

		$json = json_decode($content, true);

		$translations = json_encode($json['translations']);
		if (strpos($translations, '|') !== false) {
			$errors[] = $file->getPathname() . "\n" . '  ' . 'Contains a | in the translations.' . "\n";
		}

		if (json_last_error() !== JSON_ERROR_NONE) {
			$errors[] = $file->getPathname() . "\n" . '  ' . json_last_error_msg() . "\n";
		} else {
			$valid++;
		}

		if ($isDebug && $file->getFilename() === 'en_GB.json') {
			$sourceStrings = json_encode(array_keys($json['translations']));

			if (strpos($sourceStrings, '\u2019') !== false) {
				$errors[] = $file->getPathname() . "\n"
					. '  ' . 'Contains a unicode single quote "’" in the english source string, please replace with normal single quotes.' . "\n"
					. '  ' . 'Please note that this only updates after a sync to transifex.' . "\n";
			}
		}
	}
}

if (count($errors) > 0) {
	echo "\033[0;31m" . sprintf('ERROR: There were %d errors:', count($errors)) . "\033[0m\n\n";
	echo implode("\n", $errors);
	exit(1);
}

echo "\033[0;32m" . 'OK: ' . $valid . ' files parse' . "\033[0m\n";
exit(0);
