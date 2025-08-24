<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'resources' => [
		'ExternalShares' => ['url' => '/api/externalShares'],
	],
	'routes' => [
		[
			'name' => 'Share#showShare',
			'url' => '/s/{token}',
			'verb' => 'GET',
			'root' => '',
		],
		[
			'name' => 'Share#showAuthenticate',
			'url' => '/s/{token}/authenticate/{redirect}',
			'verb' => 'GET',
			'root' => '',
		],
		[
			'name' => 'Share#authenticate',
			'url' => '/s/{token}/authenticate/{redirect}',
			'verb' => 'POST',
			'root' => '',
		],
		[
			'name' => 'Share#downloadShare',
			'url' => '/s/{token}/download/{filename}',
			'verb' => 'GET',
			'root' => '',
			'defaults' => ['filename' => '']
		],
		[
			'name' => 'PublicPreview#directLink',
			'url' => '/s/{token}/preview',
			'verb' => 'GET',
			'root' => '',
		],
		[
			'name' => 'PublicPreview#getPreview',
			'url' => '/publicpreview/{token}',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareInfo#info',
			'url' => '/shareinfo',
			'verb' => 'POST',
		],
		[
			'name' => 'Settings#setDefaultAccept',
			'url' => '/settings/defaultAccept',
			'verb' => 'PUT',
		],
		[
			'name' => 'Settings#setUserShareFolder',
			'url' => '/settings/shareFolder',
			'verb' => 'PUT',
		],
		[
			'name' => 'Settings#resetUserShareFolder',
			'url' => '/settings/shareFolder',
			'verb' => 'DELETE',
		],
		[
			'name' => 'Accept#accept',
			'url' => '/accept/{shareId}',
			'verb' => 'GET',
		],
	],
	'ocs' => [
		/*
		 * OCS Share API
		 */
		[
			'name' => 'ShareAPI#getShares',
			'url' => '/api/v1/shares',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareAPI#getInheritedShares',
			'url' => '/api/v1/shares/inherited',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareAPI#createShare',
			'url' => '/api/v1/shares',
			'verb' => 'POST',
		],
		[
			'name' => 'ShareAPI#pendingShares',
			'url' => '/api/v1/shares/pending',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareAPI#getShare',
			'url' => '/api/v1/shares/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareAPI#updateShare',
			'url' => '/api/v1/shares/{id}',
			'verb' => 'PUT',
		],
		[
			'name' => 'ShareAPI#deleteShare',
			'url' => '/api/v1/shares/{id}',
			'verb' => 'DELETE',
		],
		[
			'name' => 'ShareAPI#sendShareEmail',
			'url' => '/api/v1/shares/{id}/send-email',
			'verb' => 'POST',
		],
		[
			'name' => 'ShareAPI#acceptShare',
			'url' => '/api/v1/shares/pending/{id}',
			'verb' => 'POST',
		],
		/*
		 * Deleted Shares
		 */
		[
			'name' => 'DeletedShareAPI#index',
			'url' => '/api/v1/deletedshares',
			'verb' => 'GET',
		],
		[
			'name' => 'DeletedShareAPI#undelete',
			'url' => '/api/v1/deletedshares/{id}',
			'verb' => 'POST',
		],
		/*
		 * OCS Sharee API
		 */
		[
			'name' => 'ShareesAPI#search',
			'url' => '/api/v1/sharees',
			'verb' => 'GET',
		],
		[
			'name' => 'ShareesAPI#findRecommended',
			'url' => '/api/v1/sharees_recommended',
			'verb' => 'GET',
		],
		/*
		 * Remote Shares
		 */
		[
			'name' => 'Remote#getShares',
			'url' => '/api/v1/remote_shares',
			'verb' => 'GET',
		],
		[
			'name' => 'Remote#getOpenShares',
			'url' => '/api/v1/remote_shares/pending',
			'verb' => 'GET',
		],
		[
			'name' => 'Remote#acceptShare',
			'url' => '/api/v1/remote_shares/pending/{id}',
			'verb' => 'POST',
		],
		[
			'name' => 'Remote#declineShare',
			'url' => '/api/v1/remote_shares/pending/{id}',
			'verb' => 'DELETE',
		],
		[
			'name' => 'Remote#getShare',
			'url' => '/api/v1/remote_shares/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'Remote#unshare',
			'url' => '/api/v1/remote_shares/{id}',
			'verb' => 'DELETE',
		],
	],
];
