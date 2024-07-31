<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Mario Danic <mario@lovelyhq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core;

use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Listeners\RemoteWipeActivityListener;
use OC\Authentication\Listeners\RemoteWipeEmailListener;
use OC\Authentication\Listeners\RemoteWipeNotificationsListener;
use OC\Authentication\Listeners\UserDeletedFilesCleanupListener;
use OC\Authentication\Listeners\UserDeletedStoreCleanupListener;
use OC\Authentication\Listeners\UserDeletedTokenCleanupListener;
use OC\Authentication\Listeners\UserDeletedWebAuthnCleanupListener;
use OC\Authentication\Notifications\Notifier as AuthenticationNotifier;
use OC\Core\Listener\BeforeTemplateRenderedListener;
use OC\Core\Notification\CoreNotifier;
use OC\TagManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\DB\Events\AddMissingColumnsEvent;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\DB\Events\AddMissingPrimaryKeyEvent;
use OCP\DB\Types;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;

/**
 * Class Application
 *
 * @package OC\Core
 */
class Application extends App {
	public function __construct() {
		parent::__construct('core');

		$container = $this->getContainer();

		$container->registerService('defaultMailAddress', function () {
			return Util::getDefaultEmailAddress('lostpassword-noreply');
		});

		$server = $container->getServer();
		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = $server->get(IEventDispatcher::class);

		$notificationManager = $server->getNotificationManager();
		$notificationManager->registerNotifierService(CoreNotifier::class);
		$notificationManager->registerNotifierService(AuthenticationNotifier::class);

		$eventDispatcher->addListener(AddMissingIndicesEvent::class, function (AddMissingIndicesEvent $event) {
			$event->addMissingIndex(
				'share',
				'share_with_index',
				['share_with']
			);
			$event->addMissingIndex(
				'share',
				'parent_index',
				['parent']
			);
			$event->addMissingIndex(
				'share',
				'owner_index',
				['uid_owner']
			);
			$event->addMissingIndex(
				'share',
				'initiator_index',
				['uid_initiator']
			);

			$event->addMissingIndex(
				'filecache',
				'fs_mtime',
				['mtime']
			);
			$event->addMissingIndex(
				'filecache',
				'fs_size',
				['size']
			);
			$event->addMissingIndex(
				'filecache',
				'fs_id_storage_size',
				['fileid', 'storage', 'size']
			);
			$event->addMissingIndex(
				'filecache',
				'fs_storage_path_prefix',
				['storage', 'path'],
				['lengths' => [null, 64]]
			);
			$event->addMissingIndex(
				'filecache',
				'fs_parent',
				['parent']
			);

			$event->addMissingIndex(
				'twofactor_providers',
				'twofactor_providers_uid',
				['uid']
			);

			$event->addMissingUniqueIndex(
				'login_flow_v2',
				'poll_token',
				['poll_token'],
				[],
				true
			);
			$event->addMissingUniqueIndex(
				'login_flow_v2',
				'login_token',
				['login_token'],
				[],
				true
			);
			$event->addMissingIndex(
				'login_flow_v2',
				'timestamp',
				['timestamp'],
				[],
				true
			);

			$event->addMissingIndex(
				'whats_new',
				'version',
				['version'],
				[],
				true
			);

			$event->addMissingIndex(
				'cards',
				'cards_abiduri',
				['addressbookid', 'uri'],
				[],
				true
			);
			$event->addMissingIndex(
				'cards',
				'cards_abid',
				['addressbookid'],
				[],
				true
			);
			$event->addMissingIndex(
				'cards',
				'cards_abiduri',
				['addressbookid', 'uri'],
				[],
				true
			);

			$event->addMissingIndex(
				'cards_properties',
				'cards_prop_abid',
				['addressbookid'],
				[],
				true
			);

			$event->addMissingIndex(
				'calendarobjects_props',
				'calendarobject_calid_index',
				['calendarid', 'calendartype']
			);

			$event->addMissingIndex(
				'schedulingobjects',
				'schedulobj_principuri_index',
				['principaluri']
			);

			$event->addMissingIndex(
				'schedulingobjects',
				'schedulobj_lastmodified_idx',
				['lastmodified']
			);

			$event->addMissingIndex(
				'properties',
				'properties_path_index',
				['userid', 'propertypath']
			);
			$event->addMissingIndex(
				'properties',
				'properties_pathonly_index',
				['propertypath']
			);


			$event->addMissingIndex(
				'jobs',
				'job_lastcheck_reserved',
				['last_checked', 'reserved_at']
			);

			$event->addMissingIndex(
				'direct_edit',
				'direct_edit_timestamp',
				['timestamp']
			);

			$event->addMissingIndex(
				'preferences',
				'preferences_app_key',
				['appid', 'configkey']
			);

			$event->addMissingIndex(
				'mounts',
				'mounts_class_index',
				['mount_provider_class']
			);
			$event->addMissingIndex(
				'mounts',
				'mounts_user_root_path_index',
				['user_id', 'root_id', 'mount_point'],
				['lengths' => [null, null, 128]]
			);

			$event->addMissingIndex(
				'systemtag_object_mapping',
				'systag_by_tagid',
				['systemtagid', 'objecttype']
			);

			$event->addMissingIndex(
				'systemtag_object_mapping',
				'systag_by_objectid',
				['objectid']
			);
		});

		$eventDispatcher->addListener(AddMissingPrimaryKeyEvent::class, function (AddMissingPrimaryKeyEvent $event) {
			$event->addMissingPrimaryKey(
				'federated_reshares',
				'federated_res_pk',
				['share_id'],
				'share_id_index'
			);

			$event->addMissingPrimaryKey(
				'systemtag_object_mapping',
				'som_pk',
				['objecttype', 'objectid', 'systemtagid'],
				'mapping'
			);

			$event->addMissingPrimaryKey(
				'comments_read_markers',
				'crm_pk',
				['user_id', 'object_type', 'object_id'],
				'comments_marker_index'
			);

			$event->addMissingPrimaryKey(
				'collres_resources',
				'crr_pk',
				['collection_id', 'resource_type', 'resource_id'],
				'collres_unique_res'
			);

			$event->addMissingPrimaryKey(
				'collres_accesscache',
				'cra_pk',
				['user_id', 'collection_id', 'resource_type', 'resource_id'],
				'collres_unique_user'
			);

			$event->addMissingPrimaryKey(
				'filecache_extended',
				'fce_pk',
				['fileid'],
				'fce_fileid_idx'
			);
		});

		$eventDispatcher->addListener(AddMissingColumnsEvent::class, function (AddMissingColumnsEvent $event) {
			$event->addMissingColumn(
				'comments',
				'reference_id',
				Types::STRING,
				[
					'notnull' => false,
					'length' => 64,
				]
			);
		});

		$eventDispatcher->addServiceListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$eventDispatcher->addServiceListener(BeforeLoginTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		$eventDispatcher->addServiceListener(RemoteWipeStarted::class, RemoteWipeActivityListener::class);
		$eventDispatcher->addServiceListener(RemoteWipeStarted::class, RemoteWipeNotificationsListener::class);
		$eventDispatcher->addServiceListener(RemoteWipeStarted::class, RemoteWipeEmailListener::class);
		$eventDispatcher->addServiceListener(RemoteWipeFinished::class, RemoteWipeActivityListener::class);
		$eventDispatcher->addServiceListener(RemoteWipeFinished::class, RemoteWipeNotificationsListener::class);
		$eventDispatcher->addServiceListener(RemoteWipeFinished::class, RemoteWipeEmailListener::class);
		$eventDispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedStoreCleanupListener::class);
		$eventDispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedTokenCleanupListener::class);
		$eventDispatcher->addServiceListener(BeforeUserDeletedEvent::class, UserDeletedFilesCleanupListener::class);
		$eventDispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedFilesCleanupListener::class);
		$eventDispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedWebAuthnCleanupListener::class);

		// Tags
		$eventDispatcher->addServiceListener(UserDeletedEvent::class, TagManager::class);
	}
}
