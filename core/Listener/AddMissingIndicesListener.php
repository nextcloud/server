<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<AddMissingIndicesEvent>
 */
class AddMissingIndicesListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof AddMissingIndicesEvent)) {
			return;
		}

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
			'filecache',
			'fs_name_hash',
			['name']
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

		$event->replaceIndex(
			'cards_properties',
			['cards_prop_abid'],
			'cards_prop_abid_name_value',
			['addressbookid', 'name', 'value'],
			false,
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
			'properties',
			'properties_name_path_user',
			['propertyname', 'propertypath', 'userid']
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
			'prefs_uid_lazy_i',
			['userid', 'lazy']
		);
		$event->addMissingIndex(
			'preferences',
			'prefs_app_key_ind_fl_i',
			['appid', 'configkey', 'indexed', 'flags']
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

		$event->addMissingIndex(
			'systemtag_object_mapping',
			'systag_objecttype',
			['objecttype']
		);
	}
}
