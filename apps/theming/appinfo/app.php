<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

$linkToCSS = \OC::$server->getURLGenerator()->linkToRoute(
	'theming.Theming.getStylesheet',
	[
		'v' => \OC::$server->getConfig()->getAppValue('theming', 'cachebuster', '0'),
	]
);
\OCP\Util::addHeader(
	'link',
	[
		'rel' => 'stylesheet',
		'href' => $linkToCSS,
	]
);

$linkToJs = \OC::$server->getURLGenerator()->linkToRoute(
	'theming.Theming.getJavascript',
	[
		'v' => \OC::$server->getConfig()->getAppValue('theming', 'cachebuster', '0'),
	]
);
\OCP\Util::addHeader(
	'script',
	[
		'src' => $linkToJs,
	], ''
);

$app = new \OCP\AppFramework\App('theming');
$app->getContainer()->registerCapability('OCA\Theming\Capabilities');
