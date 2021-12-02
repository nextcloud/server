/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
	 * @return {boolean}
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
	 * Get the default link share expiration date as string
	 *
	 * @return {string}
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
	 * Get the default internal expiration date as string
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Config
	 */
	get defaultInternalExpirationDateString() {
		let expireDateString = ''
		if (this.isDefaultInternalExpireDateEnabled) {
			const date = window.moment.utc()
			const expireAfterDays = this.defaultInternalExpireDate
			date.add(expireAfterDays, 'days')
			expireDateString = date.format('YYYY-MM-DD')
		}
		return expireDateString
	}

	/**
	 * Get the default remote expiration date as string
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Config
	 */
	get defaultRemoteExpirationDateString() {
		let expireDateString = ''
		if (this.isDefaultRemoteExpireDateEnabled) {
			const date = window.moment.utc()
			const expireAfterDays = this.defaultRemoteExpireDate
			date.add(expireAfterDays, 'days')
			expireDateString = date.format('YYYY-MM-DD')
		}
		return expireDateString
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
		const capabilities = OC.getCapabilities()
		// eslint-disable-next-line camelcase
		return capabilities?.files_sharing?.sharebymail !== undefined
			// eslint-disable-next-line camelcase
			&& capabilities?.files_sharing?.public?.enabled === true
	}

	/**
	 * Get the default days to link shares expiration
	 *
	 * @return {int}
	 * @readonly
	 * @memberof Config
	 */
	get defaultExpireDate() {
		return OC.appConfig.core.defaultExpireDate
	}

	/**
	 * Get the default days to internal shares expiration
	 *
	 * @return {int}
	 * @readonly
	 * @memberof Config
	 */
	get defaultInternalExpireDate() {
		return OC.appConfig.core.defaultInternalExpireDate
	}

	/**
	 * Get the default days to remote shares expiration
	 *
	 * @return {int}
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
		return (OC.getCapabilities().files_sharing.sharebymail === undefined) ? false : OC.getCapabilities().files_sharing.sharebymail.password.enforced
	}

	/**
	 * @return {boolean}
	 * @readonly
	 * @memberof Config
	 */
	get shouldAlwaysShowUnique() {
		return (OC.getCapabilities().files_sharing?.sharee?.always_show_unique === true)
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
	 * @return {int}
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
	 * @return {int}
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
		const capabilities = OC.getCapabilities()
		return capabilities.password_policy ? capabilities.password_policy : {}
	}

}
