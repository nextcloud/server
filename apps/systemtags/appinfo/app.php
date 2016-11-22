<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

use OCP\SystemTag\ManagerEvent;
use OCP\SystemTag\MapperEvent;

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		// FIXME: no public API for these ?
		\OCP\Util::addScript('oc-backbone-webdav');
		\OCP\Util::addScript('systemtags/systemtags');
		\OCP\Util::addScript('systemtags/systemtagmodel');
		\OCP\Util::addScript('systemtags/systemtagsmappingcollection');
		\OCP\Util::addScript('systemtags/systemtagscollection');
		\OCP\Util::addScript('systemtags/systemtagsinputfield');
		\OCP\Util::addScript('systemtags', 'app');
		\OCP\Util::addScript('systemtags', 'systemtagsfilelist');
		\OCP\Util::addScript('systemtags', 'filesplugin');
		\OCP\Util::addScript('systemtags', 'systemtagsinfoview');
		\OCP\Util::addStyle('systemtags');
		\OCP\Util::addStyle('systemtags', 'systemtagsfilelist');
	}
);

$managerListener = function(ManagerEvent $event) {
	$application = new \OCP\AppFramework\App('systemtags');
	/** @var \OCA\SystemTags\Activity\Listener $listener */
	$listener = $application->getContainer()->query('OCA\SystemTags\Activity\Listener');
	$listener->event($event);
};

$eventDispatcher->addListener(ManagerEvent::EVENT_CREATE, $managerListener);
$eventDispatcher->addListener(ManagerEvent::EVENT_DELETE, $managerListener);
$eventDispatcher->addListener(ManagerEvent::EVENT_UPDATE, $managerListener);

$mapperListener = function(MapperEvent $event) {
	$application = new \OCP\AppFramework\App('systemtags');
	/** @var \OCA\SystemTags\Activity\Listener $listener */
	$listener = $application->getContainer()->query('OCA\SystemTags\Activity\Listener');
	$listener->mapperEvent($event);
};

$eventDispatcher->addListener(MapperEvent::EVENT_ASSIGN, $mapperListener);
$eventDispatcher->addListener(MapperEvent::EVENT_UNASSIGN, $mapperListener);

$l = \OC::$server->getL10N('systemtags');

\OCA\Files\App::getNavigationManager()->add(
	array(
		'id' => 'systemtagsfilter',
		'appname' => 'systemtags',
		'script' => 'list.php',
		'order' => 25,
		'name' => $l->t('Tags')
	)
);
