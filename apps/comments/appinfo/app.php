<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function() {
		\OCP\Util::addScript('oc-backbone-webdav');
		\OCP\Util::addScript('comments', 'merged');
		\OCP\Util::addStyle('comments', 'autocomplete');
		\OCP\Util::addStyle('comments', 'comments');
	}
);

$eventDispatcher->addListener(\OCP\Comments\CommentsEntityEvent::EVENT_ENTITY, function(\OCP\Comments\CommentsEntityEvent $event) {
	$event->addEntityCollection('files', function($name) {
		$nodes = \OC::$server->getUserFolder()->getById(intval($name));
		return !empty($nodes);
	});
});

$notificationManager = \OC::$server->getNotificationManager();
$notificationManager->registerNotifier(
	function() {
		$application = new \OCP\AppFramework\App('comments');
		return $application->getContainer()->query(\OCA\Comments\Notification\Notifier::class);
	},
	function () {
		$l = \OC::$server->getL10N('comments');
		return ['id' => 'comments', 'name' => $l->t('Comments')];
	}
);

$commentsManager = \OC::$server->getCommentsManager();
$commentsManager->registerEventHandler(function () {
	$application = new \OCP\AppFramework\App('comments');
	/** @var \OCA\Comments\EventHandler $handler */
	$handler = $application->getContainer()->query(\OCA\Comments\EventHandler::class);
	return $handler;
});
