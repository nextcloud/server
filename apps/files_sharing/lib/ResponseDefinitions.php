<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

/**
 * @psalm-type Files_SharingShare = array{
 *     attributes: ?string,
 *     can_delete: bool,
 *     can_edit: bool,
 *     displayname_file_owner: string,
 *     displayname_owner: string,
 *     expiration: ?string,
 *     file_parent: int,
 *     file_source: int,
 *     file_target: string,
 *     has_preview: bool,
 *     hide_download: 0|1,
 *     is_trusted_server?: bool,
 *     is-mount-root: bool,
 *     id: string,
 *     item_mtime: int,
 *     item_permissions?: int,
 *     item_size: float|int,
 *     item_source: int,
 *     item_type: 'file'|'folder',
 *     label: string,
 *     mail_send: 0|1,
 *     mimetype: string,
 *     mount-type: string,
 *     note: string,
 *     parent: null,
 *     password?: null|string,
 *     password_expiration_time?: ?string,
 *     path: ?string,
 *     permissions: int,
 *     send_password_by_talk?: bool,
 *     share_type: int,
 *     share_with?: null|string,
 *     share_with_avatar?: string,
 *     share_with_displayname?: string,
 *     share_with_displayname_unique?: ?string,
 *     share_with_link?: string,
 *     status?: array{clearAt: int|null, icon: ?string, message: ?string, status: string},
 *     stime: int,
 *     storage: int,
 *     storage_id: string,
 *     token: ?string,
 *     uid_file_owner: string,
 *     uid_owner: string,
 *     url?: string,
 * }
 *
 * @psalm-type Files_SharingDeletedShare = array{
 *     id: string,
 *     share_type: int,
 *     uid_owner: string,
 *     displayname_owner: string,
 *     permissions: int,
 *     stime: int,
 *     uid_file_owner: string,
 *     displayname_file_owner: string,
 *     path: string,
 *     item_type: string,
 *     mimetype: string,
 *     storage: int,
 *     item_source: int,
 *     file_source: int,
 *     file_parent: int,
 *     file_target: int,
 *     expiration: string|null,
 *     share_with: string|null,
 *     share_with_displayname: string|null,
 *     share_with_link: string|null,
 * }
 *
 * @psalm-type Files_SharingRemoteShare = array{
 *     accepted: bool,
 *     file_id: int|null,
 *     id: int,
 *     mimetype: string|null,
 *     mountpoint: string,
 *     mtime: int|null,
 *     name: string,
 *     owner: string,
 *     parent: int|null,
 *     permissions: int|null,
 *     remote: string,
 *     remote_id: string,
 *     share_token: string,
 *     share_type: int,
 *     type: string|null,
 *     user: string,
 * }
 *
 * @psalm-type Files_SharingSharee = array{
 *     label: string,
 * }
 *
 * @psalm-type Files_SharingShareeValue = array{
 *     shareType: int,
 *     shareWith: string,
 * }
 *
 * @psalm-type Files_SharingShareeGroup = Files_SharingSharee&array{
 *     value: Files_SharingShareeValue,
 * }
 *
 * @psalm-type Files_SharingShareeRoom = Files_SharingSharee&array{
 *     value: Files_SharingShareeValue,
 * }
 *
 * @psalm-type Files_SharingShareeUser = Files_SharingSharee&array{
 *     subline: string,
 *     icon: string,
 *     shareWithDisplayNameUnique: string,
 *     status: array{
 *         status: string,
 *         message: string,
 *         icon: string,
 *         clearAt: int|null,
 *     },
 *     value: Files_SharingShareeValue,
 * }
 *
 * @psalm-type Files_SharingShareeRemoteGroup = Files_SharingSharee&array{
 *     guid: string,
 *     name: string,
 *     value: Files_SharingShareeValue&array{
 *         server: string,
 *     }
 * }
 *
 * @psalm-type Files_SharingLookup = array{
 *     value: string,
 *     verified: int,
 * }
 *
 * @psalm-type Files_SharingShareeLookup = Files_SharingSharee&array{
 *     extra: array{
 *         federationId: string,
 *         name: Files_SharingLookup|null,
 *         email: Files_SharingLookup|null,
 *         address: Files_SharingLookup|null,
 *         website: Files_SharingLookup|null,
 *         twitter: Files_SharingLookup|null,
 *         phone: Files_SharingLookup|null,
 *         twitter_signature: Files_SharingLookup|null,
 *         website_signature: Files_SharingLookup|null,
 *         userid: Files_SharingLookup|null,
 *     },
 *     value: Files_SharingShareeValue&array{
 *         globalScale: bool,
 *     }
 * }
 *
 * @psalm-type Files_SharingShareeEmail = Files_SharingSharee&array{
 *     uuid: string,
 *     name: string,
 *     type: string,
 *     shareWithDisplayNameUnique: string,
 *     value: Files_SharingShareeValue,
 * }
 *
 * @psalm-type Files_SharingShareeRemote = Files_SharingSharee&array{
 *     uuid: string,
 *     name: string,
 *     type: string,
 *     value: Files_SharingShareeValue&array{
 *         server: string,
 *     }
 * }
 *
 * @psalm-type Files_SharingShareeCircle = Files_SharingSharee&array{
 *     shareWithDescription: string,
 *     value: Files_SharingShareeValue&array{
 *         circle: string,
 *     }
 * }
 *
 * @psalm-type Files_SharingShareesSearchResult = array{
 *     exact: array{
 *         circles: list<Files_SharingShareeCircle>,
 *         emails: list<Files_SharingShareeEmail>,
 *         groups: list<Files_SharingShareeGroup>,
 *         remote_groups: list<Files_SharingShareeRemoteGroup>,
 *         remotes: list<Files_SharingShareeRemote>,
 *         rooms: list<Files_SharingShareeRoom>,
 *         users: list<Files_SharingShareeUser>,
 *     },
 *     circles: list<Files_SharingShareeCircle>,
 *     emails: list<Files_SharingShareeEmail>,
 *     groups: list<Files_SharingShareeGroup>,
 *     lookup: list<Files_SharingShareeLookup>,
 *     remote_groups: list<Files_SharingShareeRemoteGroup>,
 *     remotes: list<Files_SharingShareeRemote>,
 *     rooms: list<Files_SharingShareeRoom>,
 *     users: list<Files_SharingShareeUser>,
 *     lookupEnabled: bool,
 * }
 *
 * @psalm-type Files_SharingShareesRecommendedResult = array{
 *     exact: array{
 *         emails: list<Files_SharingShareeEmail>,
 *         groups: list<Files_SharingShareeGroup>,
 *         remote_groups: list<Files_SharingShareeRemoteGroup>,
 *         remotes: list<Files_SharingShareeRemote>,
 *         users: list<Files_SharingShareeUser>,
 *     },
 *     emails: list<Files_SharingShareeEmail>,
 *     groups: list<Files_SharingShareeGroup>,
 *     remote_groups: list<Files_SharingShareeRemoteGroup>,
 *     remotes: list<Files_SharingShareeRemote>,
 *     users: list<Files_SharingShareeUser>,
 * }
 *
 * @psalm-type Files_SharingShareInfo = array{
 *     id: int,
 *     parentId: int,
 *     mtime: int,
 *     name: string,
 *     permissions: int,
 *     mimetype: string,
 *     size: int|float,
 *     type: string,
 *     etag: string,
 *     children?: list<array<string, mixed>>,
 * }
 */
class ResponseDefinitions {
}
