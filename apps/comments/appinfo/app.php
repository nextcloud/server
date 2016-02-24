<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		\OCP\Util::addScript('oc-backbone-webdav');
		\OCP\Util::addScript('comments', 'app');
		\OCP\Util::addScript('comments', 'commentmodel');
		\OCP\Util::addScript('comments', 'commentcollection');
		\OCP\Util::addScript('comments', 'commentsummarymodel');
		\OCP\Util::addScript('comments', 'commentstabview');
		\OCP\Util::addScript('comments', 'filesplugin');
		\OCP\Util::addStyle('comments', 'comments');
	}
);

$activityManager = \OC::$server->getActivityManager();
$activityManager->registerExtension(function() {
	$application = new \OCP\AppFramework\App('comments');
	/** @var \OCA\Comments\Activity\Extension $extension */
	$extension = $application->getContainer()->query('OCA\Comments\Activity\Extension');
	return $extension;
});

$managerListener = function(\OCP\Comments\CommentsEvent $event) use ($activityManager) {
	$application = new \OCP\AppFramework\App('comments');
	/** @var \OCA\Comments\Activity\Listener $listener */
	$listener = $application->getContainer()->query('OCA\Comments\Activity\Listener');
	$listener->commentEvent($event);
};

$eventDispatcher->addListener(\OCP\Comments\CommentsEvent::EVENT_ADD, $managerListener);

$eventDispatcher->addListener(\OCP\Comments\CommentsEntityEvent::EVENT_ENTITY, function(\OCP\Comments\CommentsEntityEvent $event) {
	$event->addEntityCollection('files', function($name) {
		$nodes = \OC::$server->getUserFolder()->getById(intval($name));
		return !empty($nodes);
	});
});
