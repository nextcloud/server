/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

export default class Config {

	/**
	 * Is public upload allowed on link shares ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isPublicUploadEnabled() {
		return document.getElementById('filestable')
			&& document.getElementById('filestable').dataset.allowPublicUpload === 'yes'
	}

	/**
	 * Are link share allowed ?
	 *
	 * @returns {boolean}
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
	 * @returns {string}
	 * @readonly
	 * @memberof Config
	 */
	get federatedShareDocLink() {
		return OC.appConfig.core.federatedCloudShareDoc
	}

	/**
	 * Get the default expiration date as string
	 *
	 * @returns {string}
	 * @readonly
	 * @memberof Config
	 */
	get defaultExpirationDateString() {
		let expireDateString = ''
		if (this.isDefaultExpireDateEnabled) {
			const date = window.moment.utc()
			const expireAfterDays = this.defaultExpireDate
			date.add(expireAfterDays, 'days')
			expireDateString = date.format('YYYY-MM-DD')
		}
		return expireDateString
	}

	/**
	 * Are link shares password-enforced ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get enforcePasswordForPublicLink() {
		return OC.appConfig.core.enforcePasswordForPublicLink === true
	}

	/**
	 * Is password asked by default on link shares ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get enableLinkPasswordByDefault() {
		return OC.appConfig.core.enableLinkPasswordByDefault === true
	}

	/**
	 * Is link shares expiration enforced ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultExpireDateEnforced() {
		return OC.appConfig.core.defaultExpireDateEnforced === true
	}

	/**
	 * Is there a default expiration date for new link shares ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isDefaultExpireDateEnabled() {
		return OC.appConfig.core.defaultExpireDateEnabled === true
	}

	/**
	 * Are users on this server allowed to send shares to other servers ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isRemoteShareAllowed() {
		return OC.appConfig.core.remoteShareAllowed === true
	}

	/**
	 * Is sharing my mail (link share) enabled ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isMailShareAllowed() {
		return OC.appConfig.shareByMailEnabled !== undefined
	}

	/**
	 * Get the default days to expiration
	 *
	 * @returns {int}
	 * @readonly
	 * @memberof Config
	 */
	get defaultExpireDate() {
		return OC.appConfig.core.defaultExpireDate
	}

	/**
	 * Is resharing allowed ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isResharingAllowed() {
		return OC.appConfig.core.resharingAllowed === true
	}

	/**
	 * Is password enforced for mail shares ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get isPasswordForMailSharesRequired() {
		return (OC.appConfig.shareByMail === undefined) ? false : OC.appConfig.shareByMail.enforcePasswordProtection === true
	}

	/**
	 * Is sharing with groups allowed ?
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get allowGroupSharing() {
		return OC.appConfig.core.allowGroupSharing === true
	}

	/**
	 * Get the maximum results of a share search
	 *
	 * @returns {int}
	 * @readonly
	 * @memberof Config
	 */
	get maxAutocompleteResults() {
		return parseInt(OC.config['sharing.maxAutocompleteResults'], 10) || 200
	}

	/**
	 * Get the minimal string length
	 * to initiate a share search
	 *
	 * @returns {int}
	 * @readonly
	 * @memberof Config
	 */
	get minSearchStringLength() {
		return parseInt(OC.config['sharing.minSearchStringLength'], 10) || 0
	}

	/**
	 * Get the password policy config
	 *
	 * @returns {Object}
	 * @readonly
	 * @memberof Config
	 */
	get passwordPolicy() {
		const capabilities = OC.getCapabilities()
		return capabilities.password_policy ? capabilities.password_policy : {}
	}

}
