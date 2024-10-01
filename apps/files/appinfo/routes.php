<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\AppInfo;

return [
	'routes' => [
		[
			'name' => 'view#index',
			'url' => '/',
			'verb' => 'GET',
		],
		[
			'name' => 'View#showFile',
			'url' => '/f/{fileid}',
			'verb' => 'GET',
			'root' => '',
		],
		[
			'name' => 'Api#getThumbnail',
			'url' => '/api/v1/thumbnail/{x}/{y}/{file}',
			'verb' => 'GET',
			'requirements' => ['file' => '.+']
		],
		[
			'name' => 'Api#updateFileTags',
			'url' => '/api/v1/files/{path}',
			'verb' => 'POST',
			'requirements' => ['path' => '.+'],
		],
		[
			'name' => 'Api#getRecentFiles',
			'url' => '/api/v1/recent/',
			'verb' => 'GET'
		],
		[
			'name' => 'Api#getStorageStats',
			'url' => '/api/v1/stats',
			'verb' => 'GET'
		],
		[
			'name' => 'Api#setViewConfig',
			'url' => '/api/v1/views/{view}/{key}',
			'verb' => 'PUT'
		],
		[
			'name' => 'Api#setViewConfig',
			'url' => '/api/v1/views',
			'verb' => 'PUT'
		],
		[
			'name' => 'Api#getViewConfigs',
			'url' => '/api/v1/views',
			'verb' => 'GET'
		],
		[
			'name' => 'Api#setConfig',
			'url' => '/api/v1/config/{key}',
			'verb' => 'PUT'
		],
		[
			'name' => 'Api#getConfigs',
			'url' => '/api/v1/configs',
			'verb' => 'GET'
		],
		[
			'name' => 'Api#showHiddenFiles',
			'url' => '/api/v1/showhidden',
			'verb' => 'POST'
		],
		[
			'name' => 'Api#cropImagePreviews',
			'url' => '/api/v1/cropimagepreviews',
			'verb' => 'POST'
		],
		[
			'name' => 'Api#showGridView',
			'url' => '/api/v1/showgridview',
			'verb' => 'POST'
		],
		[
			'name' => 'Api#getGridView',
			'url' => '/api/v1/showgridview',
			'verb' => 'GET'
		],
		[
			'name' => 'DirectEditingView#edit',
			'url' => '/directEditing/{token}',
			'verb' => 'GET'
		],
		[
			'name' => 'Api#serviceWorker',
			'url' => '/preview-service-worker.js',
			'verb' => 'GET'
		],
		[
			'name' => 'view#indexView',
			'url' => '/{view}',
			'verb' => 'GET',
		],
		[
			'name' => 'view#indexViewFileid',
			'url' => '/{view}/{fileid}',
			'verb' => 'GET',
		],
	],
	'ocs' => [
		[
			'name' => 'DirectEditing#info',
			'url' => '/api/v1/directEditing',
			'verb' => 'GET'
		],
		[
			'name' => 'DirectEditing#templates',
			'url' => '/api/v1/directEditing/templates/{editorId}/{creatorId}',
			'verb' => 'GET'
		],
		[
			'name' => 'DirectEditing#open',
			'url' => '/api/v1/directEditing/open',
			'verb' => 'POST'
		],
		[
			'name' => 'DirectEditing#create',
			'url' => '/api/v1/directEditing/create',
			'verb' => 'POST'
		],
		[
			'name' => 'Template#list',
			'url' => '/api/v1/templates',
			'verb' => 'GET'
		],
		[
			'name' => 'Template#create',
			'url' => '/api/v1/templates/create',
			'verb' => 'POST'
		],
		[
			'name' => 'Template#path',
			'url' => '/api/v1/templates/path',
			'verb' => 'POST'
		],
		[
			'name' => 'TransferOwnership#transfer',
			'url' => '/api/v1/transferownership',
			'verb' => 'POST',
		],
		[
			'name' => 'TransferOwnership#accept',
			'url' => '/api/v1/transferownership/{id}',
			'verb' => 'POST',
		],
		[
			'name' => 'TransferOwnership#reject',
			'url' => '/api/v1/transferownership/{id}',
			'verb' => 'DELETE',
		],
		[
			/** @see OpenLocalEditorController::create() */
			'name' => 'OpenLocalEditor#create',
			'url' => '/api/v1/openlocaleditor',
			'verb' => 'POST',
		],
		[
			/** @see OpenLocalEditorController::validate() */
			'name' => 'OpenLocalEditor#validate',
			'url' => '/api/v1/openlocaleditor/{token}',
			'verb' => 'POST',
		],
	]
];
