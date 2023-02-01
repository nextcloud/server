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
 * @psalm-type ShareItem = array{
 *     attributes: string|null,
 *     can_delete: bool,
 *     can_edit: bool,
 *     displayname_file_owner: string,
 *     displayname_owner: string,
 *     expiration: string|null,
 *     file_parent: int,
 *     file_source: int,
 *     file_target: string,
 *     has_preview: bool,
 *     id: string,
 *     item_source: int,
 *     item_type: string,
 *     label: string,
 *     mail_send: int,
 *     mimetype: string,
 *     note: string,
 *     password: string|null,
 *     password_expiration_time: string|null,
 *     path: string,
 *     permissions: int,
 *     send_password_by_talk: bool|null,
 *     share_type: int,
 *     share_with: string|null,
 *     share_with_avatar: string|null,
 *     share_with_displayname: string|null,
 *     share_with_link: string|null,
 *     status: array{status: string, message: string|null, icon: string|null, clearAt: int|null}|int|null,
 *     stime: int,
 *     storage: int,
 *     storage_id: string,
 *     token: string|null,
 *     uid_file_owner: string,
 *     uid_owner: string,
 *     url: string|null,
 * }
 *
 * @psalm-type DeletedShareItem = array{
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
 * @psalm-type RemoteShareItem = array{
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
 * @psalm-type Sharee = array{
 *     count: int|null,
 *     label: string,
 *     value: array{
 *         shareType: int,
 *         shareWith: string,
 *     }
 * }
 *
 * @psalm-type ShareeUser = Sharee&array{
 *     subline: string,
 *     icon: string,
 *     shareWithDisplayNameUnique: string,
 *     status: array{
 *         status: string,
 *         message: string,
 *         icon: string,
 *         clearAt: int|null,
 *     }
 * }
 *
 * @psalm-type ShareeRemoteGroup = Sharee&array{
 *     guid: string,
 *     name: string,
 *     value: array{
 *         server: string,
 *     }
 * }
 *
 * @psalm-type Lookup = array{
 *     value: string,
 *     verified: int,
 * }
 *
 * @psalm-type ShareeLookup = Sharee&array{
 *     extra: array{
 *         federationId: string,
 *         name: Lookup|null,
 *         email: Lookup|null,
 *         address: Lookup|null,
 *         website: Lookup|null,
 *         twitter: Lookup|null,
 *         phone: Lookup|null,
 *         twitter_signature: Lookup|null,
 *         website_signature: Lookup|null,
 *         userid: Lookup|null,
 *     },
 *     value: array{
 *         globalScale: bool,
 *     }
 * }
 *
 * @psalm-type ShareeEmail = Sharee&array{
 *     uuid: string,
 *     name: string,
 *     type: string,
 *     shareWithDisplayNameUnique: string,
 * }
 *
 * @psalm-type ShareeRemote = Sharee&array{
 *     uuid: string,
 *     name: string,
 *     type: string,
 *     value: array{
 *         server: string,
 *     }
 * }
 *
 * @psalm-type ShareeCircle = Sharee&array{
 *     shareWithDescription: string,
 *     value: array{
 *         circle: string,
 *     }
 * }
 *
 * @psalm-type ShareesSearchResult = array{
 *     exact: array{
 *         circles: ShareeCircle[],
 *         emails: ShareeEmail[],
 *         groups: Sharee[],
 *         remote_groups: ShareeRemoteGroup[],
 *         remotes: ShareeRemote[],
 *         rooms: Sharee[],
 *         users: ShareeUser[],
 *     },
 *     circles: ShareeCircle[],
 *     emails: ShareeEmail[],
 *     groups: Sharee[],
 *     lookup: ShareeLookup[],
 *     remote_groups: ShareeRemoteGroup[],
 *     remotes: ShareeRemote[],
 *     rooms: Sharee[],
 *     users: ShareeUser[],
 *     lookupEnabled: bool,
 * }
 *
 * @psalm-type ShareesRecommendedResult = array{
 *     exact: array{
 *         emails: ShareeEmail[],
 *         groups: Sharee[],
 *         remote_groups: ShareeRemoteGroup[],
 *         remotes: ShareeRemote[],
 *         users: ShareeUser[],
 *     },
 *     emails: ShareeEmail[],
 *     groups: Sharee[],
 *     remote_groups: ShareeRemoteGroup[],
 *     remotes: ShareeRemote[],
 *     users: ShareeUser[],
 * }
 */
class ResponseDefinitions {
}
