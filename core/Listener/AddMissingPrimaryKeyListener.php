<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\DB\Events\AddMissingPrimaryKeyEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<AddMissingPrimaryKeyEvent>
 */
class AddMissingPrimaryKeyListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof AddMissingPrimaryKeyEvent)) {
			return;
		}

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
	}
}
