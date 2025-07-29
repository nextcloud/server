<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 *     additional_mail: list<string>,
 *     additional_mailScope?: list<Provisioning_APIUserDetailsScope>,
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
 *     groups: list<string>,
 *     headline: string,
 *     headlineScope?: Provisioning_APIUserDetailsScope,
 *     id: string,
 *     language: string,
 *     firstLoginTimestamp: int,
 *     lastLoginTimestamp: int,
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
 *     pronouns: string,
 *     pronounsScope?: Provisioning_APIUserDetailsScope,
 *     quota: Provisioning_APIUserDetailsQuota,
 *     role: string,
 *     roleScope?: Provisioning_APIUserDetailsScope,
 *     storageLocation?: string,
 *     subadmin: list<string>,
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
