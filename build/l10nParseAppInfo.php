<?php
/**
 * @copyright Copyright (c) 2017 Morris Jobke <hey@morrisjobke.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This little script parses the info.xml and extracts the app name as well
 * as the navigation entry name from the XML and writes it into a file named
 * specialAppInfoFakeDummyForL10nScript.php that is created and deleted during
 * l10n string extraction
 */

$fileName = getcwd() . '/appinfo/info.xml';
$strings = [];

if (!file_exists($fileName)) {
	exit();
}

$xml = simplexml_load_file($fileName);

if ($xml->name) {
	$strings[] = $xml->name->__toString();
}

if ($xml->navigations) {
	foreach ($xml->navigations as $navigation) {
		$name = $navigation->navigation->name->__toString();
		if (!in_array($name, $strings)) {
			$strings[] = $name;
		}
	}
}

print_r($strings);

$content = '<?php' . PHP_EOL;

foreach ($strings as $string) {
	$content .= '$l->t("' . $string . '");' . PHP_EOL;
}

file_put_contents('specialAppInfoFakeDummyForL10nScript.php', $content);

