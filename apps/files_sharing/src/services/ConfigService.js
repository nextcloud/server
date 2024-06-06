/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCapabilities } from '@nextcloud/capabilities'

export default class Config {

	constructor() {
		this._capabilities = getCapabilities()
	}

	/**
	 * Get default share permissions, if any
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	 get defaultPermissions() {
		return this._capabilities.files_sharing?.default_permissions
	}

	/**
	 * Is public upload allowed on link shares ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isPublicUploadEnabled() {
		return this._capabilities.files_sharing?.public.upload
	}

	/**
	 * Are link share allowed ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isShareWithLinkAllowed() {
		return document.getElementById('allowShareWithLink')
			&& document.getElementById('allowShareWithLink').value === 'yes'
	}

	/**
	 * Get the federated sharing documentation link
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Config
	 */
	get federatedShareDocLink() {
		return OC.appConfig.core.federatedCloudShareDoc
	}

	/**
	 * Get the default link share expiration date
	 *
	 * @return {Date|null}
	 * @readonly
	 * @memberof Config
	 */
	get defaultExpirationDate() {
		if (this.isDefaultExpireDateEnabled) {
			return new Date(new Date().setDate(new Date().getDate() + this.defaultExpireDate))
		}
		return null
	}

	/**
	 * Get the default internal expiration date
	 *
	 * @return {Date|null}
	 * @readonly
	 * @memberof Config
	 */
	get defaultInternalExpirationDate() {
		if (this.isDefaultInternalExpireDateEnabled) {
			return new Date(new Date().setDate(new Date().getDate() + this.defaultInternalExpireDate))
		}
		return null
	}

	/**
	 * Get the default remote expiration date
	 *
	 * @return {Date|null}
	 * @readonly
	 * @memberof Config
	 */
	get defaultRemoteExpirationDateString() {
		if (this.isDefaultRemoteExpireDateEnabled) {
			return new Date(new Date().setDate(new Date().getDate() + this.defaultRemoteExpireDate))
		}
		return null
	}

	/**
	 * Are link shares password-enforced ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get enforcePasswordForPublicLink() {
		return OC.appConfig.core.enforcePasswordForPublicLink === true
	}

	/**
	 * Is password asked by default on link shares ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get enableLinkPasswordByDefault() {
		return OC.appConfig.core.enableLinkPasswordByDefault === true
	}

	/**
	 * Is link shares expiration enforced ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultExpireDateEnforced() {
		return OC.appConfig.core.defaultExpireDateEnforced === true
	}

	/**
	 * Is there a default expiration date for new link shares ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultExpireDateEnabled() {
		return OC.appConfig.core.defaultExpireDateEnabled === true
	}

	/**
	 * Is internal shares expiration enforced ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultInternalExpireDateEnforced() {
		return OC.appConfig.core.defaultInternalExpireDateEnforced === true
	}

	/**
	 * Is remote shares expiration enforced ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultRemoteExpireDateEnforced() {
		return OC.appConfig.core.defaultRemoteExpireDateEnforced === true
	}

	/**
	 * Is there a default expiration date for new internal shares ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultInternalExpireDateEnabled() {
		return OC.appConfig.core.defaultInternalExpireDateEnabled === true
	}

	/**
	 * Is there a default expiration date for new remote shares ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultRemoteExpireDateEnabled() {
		return OC.appConfig.core.defaultRemoteExpireDateEnabled === true
	}

	/**
	 * Are users on this server allowed to send shares to other servers ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isRemoteShareAllowed() {
		return OC.appConfig.core.remoteShareAllowed === true
	}

	/**
	 * Is sharing my mail (link share) enabled ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isMailShareAllowed() {
		// eslint-disable-next-line camelcase
		return this._capabilities?.files_sharing?.sharebymail !== undefined
			// eslint-disable-next-line camelcase
			&& this._capabilities?.files_sharing?.public?.enabled === true
	}

	/**
	 * Get the default days to link shares expiration
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Config
	 */
	get defaultExpireDate() {
		return OC.appConfig.core.defaultExpireDate
	}

	/**
	 * Get the default days to internal shares expiration
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Config
	 */
	get defaultInternalExpireDate() {
		return OC.appConfig.core.defaultInternalExpireDate
	}

	/**
	 * Get the default days to remote shares expiration
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Config
	 */
	get defaultRemoteExpireDate() {
		return OC.appConfig.core.defaultRemoteExpireDate
	}

	/**
	 * Is resharing allowed ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isResharingAllowed() {
		return OC.appConfig.core.resharingAllowed === true
	}

	/**
	 * Is password enforced for mail shares ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isPasswordForMailSharesRequired() {
		return (this._capabilities.files_sharing.sharebymail === undefined) ? false : this._capabilities.files_sharing.sharebymail.password.enforced
	}

	/**
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get shouldAlwaysShowUnique() {
		return (this._capabilities.files_sharing?.sharee?.always_show_unique === true)
	}

	/**
	 * Is sharing with groups allowed ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get allowGroupSharing() {
		return OC.appConfig.core.allowGroupSharing === true
	}

	/**
	 * Get the maximum results of a share search
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Config
	 */
	get maxAutocompleteResults() {
		return parseInt(OC.config['sharing.maxAutocompleteResults'], 10) || 25
	}

	/**
	 * Get the minimal string length
	 * to initiate a share search
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Config
	 */
	get minSearchStringLength() {
		return parseInt(OC.config['sharing.minSearchStringLength'], 10) || 0
	}

	/**
	 * Get the password policy config
	 *
	 * @return {object}
	 * @readonly
	 * @memberof Config
	 */
	get passwordPolicy() {
		return this._capabilities.password_policy ? this._capabilities.password_policy : {}
	}

}
