<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use NCU\Sharing\Permission\ISharePermissionPreset;
use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Property\ISharePropertyType;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Source\IShareSourceType;

/**
 * Keep the following types in sync with apps/sharing/lib/ResponseDefinitions.php:
 *
 * @psalm-type SharingIconSVG = array{
 *     // An SVG using the currentColor value for dynamic theming.
 *     svg: non-empty-string,
 * }
 *
 * @psalm-type SharingIconURL = array{
 *     // An absolute URL to an image suitable for light theme.
 *     light: non-empty-string,
 *     // An absolute URL to an image suitable for dark theme.
 *     dark: non-empty-string,
 * }
 *
 * @psalm-type SharingIcon = SharingIconSVG|SharingIconURL
 *
 * @psalm-type SharingSource = array{
 *     class: class-string<IShareSourceType>,
 *     value: non-empty-string,
 *     display_name: non-empty-string,
 *     icon: ?SharingIcon,
 * }
 *
 * @psalm-type SharingUser = array{
 *     user_id: non-empty-string,
 *     instance: ?non-empty-string,
 *     display_name: non-empty-string,
 *     icon: SharingIcon,
 * }
 *
 * @psalm-type SharingPermission = array{
 *     class: class-string<ISharePermissionType>,
 *     source_class: ?class-string<IShareSourceType>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 *     priority: int<1, 100>,
 *     presets: list<class-string<ISharePermissionPreset>>,
 *     enabled: bool,
 * }
 *
 * @psalm-type SharingRecipient = array{
 *     class: class-string<IShareRecipientType>,
 *     value: non-empty-string,
 *     instance: ?non-empty-string,
 *     display_name: non-empty-string,
 *     icon: ?SharingIcon,
 *     secret: array{
 *         updatable: bool,
 *         value?: non-empty-string,
 *         url?: non-empty-string,
 *     },
 *     initiator: ?SharingUser,
 *     permissions: list<SharingPermission>
 * }
 *
 * @psalm-type SharingState = 'active'|'draft'|'deleted'
 *
 * @psalm-type SharingUserStatus = 'pending'|'accepted'|'rejected'
 *
 * @psalm-type SharingProperty = array{
 *     class: class-string<ISharePropertyType>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 *     priority: int<1, 100>,
 *     required: bool,
 *     advanced: bool,
 *     value: ?string,
 * }
 *
 * @psalm-type SharingPropertyBoolean = SharingProperty&array{
 *     type: 'boolean',
 * }
 *
 * @psalm-type SharingPropertyDate = SharingProperty&array{
 *     type: 'date',
 *     // ISO 8601
 *     min_date: ?non-empty-string,
 *     // ISO 8601
 *     max_date: ?non-empty-string,
 * }
 *
 * @psalm-type SharingPropertyEnum = SharingProperty&array{
 *     type: 'enum',
 *     valid_values: non-empty-list<string>,
 * }
 *
 * @psalm-type SharingPropertyPassword = SharingProperty&array{
 *     type: 'password',
 * }
 *
 * @psalm-type SharingPropertyString = SharingProperty&array{
 *     type: 'string',
 *     min_length: ?positive-int,
 *     max_length: ?positive-int,
 * }
 *
 * @psalm-type SharingPermissionPreset = array{
 *     class: class-string<ISharePermissionPreset>,
 *     display_name: non-empty-string,
 *     hint: ?non-empty-string,
 * }
 *
 * @psalm-type SharingSourceType = array{
 *     class: class-string<IShareSourceType>,
 * }
 *
 * @psalm-type SharingShare = array{
 *     id: non-empty-string,
 *     owner: SharingUser,
 *     // Unix time in milliseconds
 *     last_updated: numeric-string,
 *     state: SharingState,
 *     user_status: ?SharingUserStatus,
 *     sources: list<SharingSource>,
 *     recipients: list<SharingRecipient>,
 *     properties: list<SharingPropertyDate|SharingPropertyEnum|SharingPropertyBoolean|SharingPropertyPassword|SharingPropertyString>,
 *     permissions: list<SharingPermission>,
 *     permission_preset: ?class-string<ISharePermissionPreset>,
 * }
 */
final class ResponseDefinitions {
}
