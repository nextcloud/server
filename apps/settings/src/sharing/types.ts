/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * The admin sharing settings as provided by the initial state `sharingSettings`.
 */
export interface ISharingSettings {
	enabled: boolean
	allowGroupSharing: boolean
	allowLinks: boolean
	allowLinksExcludeGroups: string[]
	allowPublicUpload: boolean
	allowResharing: boolean
	allowShareDialogUserEnumeration: boolean
	allowFederationOnPublicShares: boolean
	restrictUserEnumerationToGroup: boolean
	restrictUserEnumerationToPhone: boolean
	restrictUserEnumerationFullMatch: boolean
	restrictUserEnumerationFullMatchUserId: boolean
	restrictUserEnumerationFullMatchDisplayname: boolean
	restrictUserEnumerationFullMatchEmail: boolean
	restrictUserEnumerationFullMatchIgnoreSecondDN: boolean
	enforceLinksPassword: boolean
	enforceLinksPasswordExcludedGroups: string[]
	enforceLinksPasswordExcludedGroupsEnabled: boolean
	onlyShareWithGroupMembers: boolean
	onlyShareWithGroupMembersExcludeGroupList: string[]
	defaultExpireDate: boolean
	expireAfterNDays: string
	enforceExpireDate: boolean
	excludeGroups: 'no' | 'yes' | 'allow'
	excludeGroupsList: string[]
	publicShareDisclaimerText: string
	enableLinkPasswordByDefault: boolean
	defaultPermissions: number
	defaultInternalExpireDate: boolean
	internalExpireAfterNDays: string
	enforceInternalExpireDate: boolean
	defaultRemoteExpireDate: boolean
	remoteExpireAfterNDays: string
	enforceRemoteExpireDate: boolean
	allowCustomTokens: boolean
	allowViewWithoutDownload: boolean
}
