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

namespace OCA\Provisioning_API;

/**
 * @psalm-type Provisioning_APIUserDetailsQuota = array{
 *     free?: float|int,
 *     quota?: float|int|string,
 *     relative?: float|int,
 *     total?: float|int,
 *     used?: float|int,
 * }
 *
 * @psalm-type Provisioning_APIUserDetails = array{
 *     additional_mail: string[],
 *     additional_mailScope?: string[],
 *     address: string,
 *     addressScope?: string,
 *     avatarScope?: string,
 *     backend: string,
 *     backendCapabilities: array{
 *         setDisplayName: bool,
 *         setPassword: bool
 *     },
 *     biography: string,
 *     biographyScope?: string,
 *     display-name: string,
 *     displayname: string,
 *     displaynameScope?: string,
 *     email: ?string,
 *     emailScope?: string,
 *     enabled?: bool,
 *     fediverse: string,
 *     fediverseScope?: string,
 *     groups: string[],
 *     headline: string,
 *     headlineScope?: string,
 *     id: string,
 *     language: string,
 *     lastLogin: int,
 *     locale: string,
 *     manager: string,
 *     notify_email: ?string,
 *     organisation: string,
 *     organisationScope?: string,
 *     phone: string,
 *     phoneScope?: string,
 *     profile_enabled: string,
 *     profile_enabledScope?: string,
 *     quota: Provisioning_APIUserDetailsQuota,
 *     role: string,
 *     roleScope?: string,
 *     storageLocation?: string,
 *     subadmin: string[],
 *     twitter: string,
 *     twitterScope?: string,
 *     website: string,
 *     websiteScope?: string,
 * }
 *
 * @psalm-type Provisioning_APIGroupDetails = array{
 *     id: string,
 *     displayname: string,
 *     usercount: bool|int,
 *     disabled: bool|int,
 *     canAdd: bool,
 *     canRemove: bool,
 * }
 */
class ResponseDefinitions {
}
