<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 *
 * @copyright Copyright (c) 2016, Bjoern Schiessle
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the 
 * License, or (at your opinion) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

\OC_Util::checkAdminUser();

$config = \OC::$server->getConfig();
$l = \OC::$server->getL10N('theming');
$urlGenerator = \OC::$server->getURLGenerator();
$init = new \OCA\Theming\Init($config, \OC::$server->getLogger());

$theming = new \OCA\Theming\Template(
	$config,
	$l,
	\OC::$server->getURLGenerator(),
	$init
);

$themable = true;
$errorMessage = '';
$theme = $config->getSystemValue('theme', 'default');

if ($theme !== 'theming-app' && $theme !== 'default') {
	$themable = false;
	$errorMessage = $l->t('You already use a custom theme');
} elseif (!is_writable(\OC::$SERVERROOT . '/themes')) {
	$themable = false;
	$errorMessage = $l->t('Themes folder is read-only, please update the permissions to read-write');
}

$template = new OCP\Template('theming', 'settings-admin');

$template->assign('themable', $themable);
$template->assign('errorMessage', $errorMessage);
$template->assign('name', $theming->getName());
$template->assign('url', $theming->getUrl());
$template->assign('slogan', $theming->getSlogan());
$template->assign('color', $theming->getColor());
$path = $urlGenerator->linkToRoute('theming.Theming.updateLogo');
$template->assign('uploadLogoRoute', $path);

return $template->fetchPage();
