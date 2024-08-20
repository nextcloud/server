<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
 *     password?: string,
 *     password_expiration_time?: ?string,
 *     path: ?string,
 *     permissions: int,
 *     send_password_by_talk?: bool,
 *     share_type: int,
 *     share_with?: string,
 *     share_with_avatar?: string,
 *     share_with_displayname?: string,
 *     share_with_displayname_unique?: ?string,
 *     share_with_link?: string,
 *     status?: array{clearAt?: int|null, icon?: ?string, message?: ?string, status?: string},
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
 *     count: int|null,
 *     label: string,
 * }
 *
 * @psalm-type Files_SharingShareeValue = array{
 *     shareType: int,
 *     shareWith: string,
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
 *         circles: Files_SharingShareeCircle[],
 *         emails: Files_SharingShareeEmail[],
 *         groups: Files_SharingSharee[],
 *         remote_groups: Files_SharingShareeRemoteGroup[],
 *         remotes: Files_SharingShareeRemote[],
 *         rooms: Files_SharingSharee[],
 *         users: Files_SharingShareeUser[],
 *     },
 *     circles: Files_SharingShareeCircle[],
 *     emails: Files_SharingShareeEmail[],
 *     groups: Files_SharingSharee[],
 *     lookup: Files_SharingShareeLookup[],
 *     remote_groups: Files_SharingShareeRemoteGroup[],
 *     remotes: Files_SharingShareeRemote[],
 *     rooms: Files_SharingSharee[],
 *     users: Files_SharingShareeUser[],
 *     lookupEnabled: bool,
 * }
 *
 * @psalm-type Files_SharingShareesRecommendedResult = array{
 *     exact: array{
 *         emails: Files_SharingShareeEmail[],
 *         groups: Files_SharingSharee[],
 *         remote_groups: Files_SharingShareeRemoteGroup[],
 *         remotes: Files_SharingShareeRemote[],
 *         users: Files_SharingShareeUser[],
 *     },
 *     emails: Files_SharingShareeEmail[],
 *     groups: Files_SharingSharee[],
 *     remote_groups: Files_SharingShareeRemoteGroup[],
 *     remotes: Files_SharingShareeRemote[],
 *     users: Files_SharingShareeUser[],
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
 *     children?: array<string, mixed>[],
 * }
 */
class ResponseDefinitions {
}
