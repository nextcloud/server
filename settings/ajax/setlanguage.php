<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
$l = \OC::$server->getL10N('settings');

OC_JSON::checkLoggedIn();
OCP\JSON::callCheck();


// Get data
if( isset( $_POST['lang'] ) ) {
	$languageCodes = \OC::$server->getL10NFactory()->findAvailableLanguages();
	$lang = (string)$_POST['lang'];
	if(array_search($lang, $languageCodes) or $lang === 'en') {
		\OC::$server->getConfig()->setUserValue( OC_User::getUser(), 'core', 'lang', $lang );
		OC_JSON::success(array("data" => array( "message" => $l->t("Language changed") )));
	}else{
		OC_JSON::error(array("data" => array( "message" => $l->t("Invalid request") )));
	}
}else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Invalid request") )));
}
