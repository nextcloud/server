/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCapabilities } from '@nextcloud/capabilities'
import { loadState } from '@nextcloud/initial-state'

type PasswordPolicyCapabilities = {
	enforceNonCommonPassword: boolean
	enforceNumericCharacters: boolean
	enforceSpecialCharacters: boolean
	enforceUpperLowerCase: boolean
	minLength: number
}

type FileSharingCapabilities = {
	api_enabled: boolean
	public: {
		enabled: boolean
		password: {
			enforced: boolean
			askForOptionalPassword: boolean
		}
		expire_date: {
			enabled: boolean
			days: number
			enforced: boolean
		}
		multiple_links: boolean
		expire_date_internal: {
			enabled: boolean
		}
		expire_date_remote: {
			enabled: boolean
		}
		send_mail: boolean
		upload: boolean
		upload_files_drop: boolean
		custom_tokens: boolean
	}
	resharing: boolean
	user: {
		send_mail: boolean
		expire_date: {
			enabled: boolean
		}
	}
	group_sharing: boolean
	group: {
		enabled: boolean
		expire_date: {
			enabled: true
		}
	}
	default_permissions: number
	federation: {
		outgoing: boolean
		incoming: boolean
		expire_date: {
			enabled: boolean
		}
		expire_date_supported: {
			enabled: boolean
		}
	}
	sharee: {
		query_lookup_default: boolean
		always_show_unique: boolean
	}
	sharebymail: {
		enabled: boolean
		send_password_by_mail: boolean
		upload_files_drop: {
			enabled: boolean
		}
		password: {
			enabled: boolean
			enforced: boolean
		}
		expire_date: {
			enabled: boolean
			enforced: boolean
		}
	}
}

type Capabilities = {
	files_sharing: FileSharingCapabilities
	password_policy: PasswordPolicyCapabilities
}

export default class Config {
	_capabilities: Capabilities

	constructor() {
		this._capabilities = getCapabilities() as Capabilities
	}

	/**
	 * Get default share permissions, if any
	 */
	get defaultPermissions(): number {
		return this._capabilities.files_sharing?.default_permissions
	}

	/**
	 * Is public upload allowed on link shares ?
	 * This covers File request and Full upload/edit option.
	 */
	get isPublicUploadEnabled(): boolean {
		return this._capabilities.files_sharing?.public?.upload === true
	}

	/**
	 * Get the federated sharing documentation link
	 */
	get federatedShareDocLink() {
		return window.OC.appConfig.core.federatedCloudShareDoc
	}

	/**
	 * Get the default link share expiration date
	 */
	get defaultExpirationDate(): Date | null {
		if (this.isDefaultExpireDateEnabled && this.defaultExpireDate !== null) {
			return new Date(new Date().setDate(new Date().getDate() + this.defaultExpireDate))
		}
		return null
	}

	/**
	 * Get the default internal expiration date
	 */
	get defaultInternalExpirationDate(): Date | null {
		if (this.isDefaultInternalExpireDateEnabled && this.defaultInternalExpireDate !== null) {
			return new Date(new Date().setDate(new Date().getDate() + this.defaultInternalExpireDate))
		}
		return null
	}

	/**
	 * Get the default remote expiration date
	 */
	get defaultRemoteExpirationDateString(): Date | null {
		if (this.isDefaultRemoteExpireDateEnabled && this.defaultRemoteExpireDate !== null) {
			return new Date(new Date().setDate(new Date().getDate() + this.defaultRemoteExpireDate))
		}
		return null
	}

	/**
	 * Are link shares password-enforced ?
	 */
	get enforcePasswordForPublicLink(): boolean {
		return window.OC.appConfig.core.enforcePasswordForPublicLink === true
	}

	/**
	 * Is password asked by default on link shares ?
	 */
	get enableLinkPasswordByDefault(): boolean {
		return window.OC.appConfig.core.enableLinkPasswordByDefault === true
	}

	/**
	 * Is link shares expiration enforced ?
	 */
	get isDefaultExpireDateEnforced(): boolean {
		return window.OC.appConfig.core.defaultExpireDateEnforced === true
	}

	/**
	 * Is there a default expiration date for new link shares ?
	 */
	get isDefaultExpireDateEnabled(): boolean {
		return window.OC.appConfig.core.defaultExpireDateEnabled === true
	}

	/**
	 * Is internal shares expiration enforced ?
	 */
	get isDefaultInternalExpireDateEnforced(): boolean {
		return window.OC.appConfig.core.defaultInternalExpireDateEnforced === true
	}

	/**
	 * Is there a default expiration date for new internal shares ?
	 */
	get isDefaultInternalExpireDateEnabled(): boolean {
		return window.OC.appConfig.core.defaultInternalExpireDateEnabled === true
	}

	/**
	 * Is remote shares expiration enforced ?
	 */
	get isDefaultRemoteExpireDateEnforced(): boolean {
		return window.OC.appConfig.core.defaultRemoteExpireDateEnforced === true
	}

	/**
	 * Is there a default expiration date for new remote shares ?
	 */
	get isDefaultRemoteExpireDateEnabled(): boolean {
		return window.OC.appConfig.core.defaultRemoteExpireDateEnabled === true
	}

	/**
	 * Are users on this server allowed to send shares to other servers ?
	 */
	get isRemoteShareAllowed(): boolean {
		return window.OC.appConfig.core.remoteShareAllowed === true
	}

	/**
	 * Is federation enabled ?
	 */
	get isFederationEnabled(): boolean {
		return this._capabilities?.files_sharing?.federation?.outgoing === true
	}

	/**
	 * Is public sharing enabled ?
	 */
	get isPublicShareAllowed(): boolean {
		return this._capabilities?.files_sharing?.public?.enabled === true
	}

	/**
	 * Is sharing my mail (link share) enabled ?
	 */
	get isMailShareAllowed(): boolean {
		return this._capabilities?.files_sharing?.sharebymail?.enabled === true

			&& this.isPublicShareAllowed === true
	}

	/**
	 * Get the default days to link shares expiration
	 */
	get defaultExpireDate(): number | null {
		return window.OC.appConfig.core.defaultExpireDate
	}

	/**
	 * Get the default days to internal shares expiration
	 */
	get defaultInternalExpireDate(): number | null {
		return window.OC.appConfig.core.defaultInternalExpireDate
	}

	/**
	 * Get the default days to remote shares expiration
	 */
	get defaultRemoteExpireDate(): number | null {
		return window.OC.appConfig.core.defaultRemoteExpireDate
	}

	/**
	 * Is resharing allowed ?
	 */
	get isResharingAllowed(): boolean {
		return window.OC.appConfig.core.resharingAllowed === true
	}

	/**
	 * Is password enforced for mail shares ?
	 */
	get isPasswordForMailSharesRequired(): boolean {
		return this._capabilities.files_sharing?.sharebymail?.password?.enforced === true
	}

	/**
	 * Always show the email or userid unique sharee label if enabled by the admin
	 */
	get shouldAlwaysShowUnique(): boolean {
		return this._capabilities.files_sharing?.sharee?.always_show_unique === true
	}

	/**
	 * Is sharing with groups allowed ?
	 */
	get allowGroupSharing(): boolean {
		return window.OC.appConfig.core.allowGroupSharing === true
	}

	/**
	 * Get the maximum results of a share search
	 */
	get maxAutocompleteResults(): number {
		return parseInt(window.OC.config['sharing.maxAutocompleteResults'], 10) || 25
	}

	/**
	 * Get the minimal string length
	 * to initiate a share search
	 */
	get minSearchStringLength(): number {
		return parseInt(window.OC.config['sharing.minSearchStringLength'], 10) || 0
	}

	/**
	 * Get the password policy configuration
	 */
	get passwordPolicy(): PasswordPolicyCapabilities {
		return this._capabilities?.password_policy || {}
	}

	/**
	 * Returns true if custom tokens are allowed
	 */
	get allowCustomTokens(): boolean {
		return this._capabilities?.files_sharing?.public?.custom_tokens
	}

	/**
	 * Show federated shares as internal shares
	 *
	 * @return
	 */
	get showFederatedSharesAsInternal(): boolean {
		return loadState('files_sharing', 'showFederatedSharesAsInternal', false)
	}

	/**
	 * Show federated shares to trusted servers as internal shares
	 *
	 * @return
	 */
	get showFederatedSharesToTrustedServersAsInternal(): boolean {
		return loadState('files_sharing', 'showFederatedSharesToTrustedServersAsInternal', false)
	}

	/**
	 * Show the external share ui
	 */
	get showExternalSharing(): boolean {
		return loadState('files_sharing', 'showExternalSharing', true)
	}
}
