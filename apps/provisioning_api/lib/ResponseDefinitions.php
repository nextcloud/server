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
 * @psalm-type Provisioning_APIUserDetailsScope = 'v2-private'|'v2-local'|'v2-federated'|'v2-published'|'private'|'contacts'|'public'
 *
 * @psalm-type Provisioning_APIUserDetails = array{
 *     additional_mail: string[],
 *     additional_mailScope?: Provisioning_APIUserDetailsScope[],
 *     address: string,
 *     addressScope?: Provisioning_APIUserDetailsScope,
 *     avatarScope?: Provisioning_APIUserDetailsScope,
 *     backend: string,
 *     backendCapabilities: array{
 *         setDisplayName: bool,
 *         setPassword: bool
 *     },
 *     biography: string,
 *     biographyScope?: Provisioning_APIUserDetailsScope,
 *     display-name: string,
 *     displayname: string,
 *     displaynameScope?: Provisioning_APIUserDetailsScope,
 *     email: ?string,
 *     emailScope?: Provisioning_APIUserDetailsScope,
 *     enabled?: bool,
 *     fediverse: string,
 *     fediverseScope?: Provisioning_APIUserDetailsScope,
 *     groups: string[],
 *     headline: string,
 *     headlineScope?: Provisioning_APIUserDetailsScope,
 *     id: string,
 *     language: string,
 *     lastLogin: int,
 *     locale: string,
 *     manager: string,
 *     notify_email: ?string,
 *     organisation: string,
 *     organisationScope?: Provisioning_APIUserDetailsScope,
 *     phone: string,
 *     phoneScope?: Provisioning_APIUserDetailsScope,
 *     profile_enabled: string,
 *     profile_enabledScope?: Provisioning_APIUserDetailsScope,
 *     quota: Provisioning_APIUserDetailsQuota,
 *     role: string,
 *     roleScope?: Provisioning_APIUserDetailsScope,
 *     storageLocation?: string,
 *     subadmin: string[],
 *     twitter: string,
 *     twitterScope?: Provisioning_APIUserDetailsScope,
 *     website: string,
 *     websiteScope?: Provisioning_APIUserDetailsScope,
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
