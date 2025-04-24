/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { emit } from '@nextcloud/event-bus'
import { ShareType } from '@nextcloud/sharing'
import GeneratePassword from '../utils/GeneratePassword'
import Share from '../models/Share'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import SharesMixin from '../mixins/SharesMixin.js'

export default {
	computed: {
		/**
		 * Whether the share policy has enforced properties.
		 * @return {boolean}
		 */
		sharePolicyHasEnforcedProperties() {
			return this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced
		},

		/**
		 * Whether required properties are missing.
		 * @return {boolean}
		 */
		enforcedPropertiesMissing() {
			if (!this.sharePolicyHasEnforcedProperties) return false
			if (!this.share) return true
			if (this.share.id) return true

			const isPasswordMissing = this.config.enforcePasswordForPublicLink && !this.share.password
			const isExpireDateMissing = this.config.isDefaultExpireDateEnforced && !this.share.expireDate

			return isPasswordMissing || isExpireDateMissing
		},
	},

	mixins: [SharesMixin],

	methods: {
		/**
		 * Check if the share requires review
		 *
		 * @param {boolean} shareReviewComplete if the share was reviewed
		 * @return {boolean}
		 */
		shareRequiresReview(shareReviewComplete) {
			// If a user clicks 'Create share' it means they have reviewed the share
			if (shareReviewComplete) {
				return false
			}
			return this.defaultExpirationDateEnabled || this.config.enableLinkPasswordByDefault
		},
		/**
		 * Handle the creation of a new link share.
		 * @param {boolean} shareReviewComplete Whether the share has been reviewed.
		 */
		async onNewLinkShare(shareReviewComplete = false) {
			this.logger.debug('onNewLinkShare called (with this.share)', this.share)
			if (this.loading) return

			const shareDefaults = {
				share_type: ShareType.Link,
			}

			if (this.config.isDefaultExpireDateEnforced) {
				shareDefaults.expiration = this.formatDateToString(this.config.defaultExpirationDate)
			}

			if (
				(this.sharePolicyHasEnforcedProperties && this.enforcedPropertiesMissing)
				|| this.shareRequiresReview(shareReviewComplete)
			) {
				this.pending = true
				this.shareCreationComplete = false

				if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink) {
					shareDefaults.password = await GeneratePassword(true)
				}

				const share = new Share(shareDefaults)
				const component = await new Promise((resolve) => {
					this.$emit('add:share', share, resolve)
				})

				this.open = false
				this.pending = false
				component.open = true
			} else {
				if (this.share && !this.share.id) {
					if (this.checkShare(this.share)) {
						try {
							await this.pushNewLinkShare(this.share, true)
							this.shareCreationComplete = true
						} catch (e) {
							this.pending = false
							this.logger.error('Error creating share', e)
							return
						}
						return
					} else {
						this.open = true
						showError(t('files_sharing', 'Error, please enter proper password and/or expiration date'))
						return
					}
				}

				const share = new Share(shareDefaults)
				await this.pushNewLinkShare(share)
				this.shareCreationComplete = true
			}
		},

		/**
		 * Push a new link share to the server
		 * And update or append to the list
		 * accordingly
		 *
		 * @param {Share} share the new share
		 * @param {boolean} [update] do we update the current share ?
		 */
		async pushNewLinkShare(share, update) {
			try {
				// do nothing if we're already pending creation
				if (this.loading) {
					return true
				}

				this.loading = true
				this.errors = {}

				const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/')
				const options = {
					path,
					shareType: ShareType.Link,
					password: share.password,
					expireDate: share.expireDate ?? '',
					attributes: JSON.stringify(this.fileInfo.shareAttributes),
					// we do not allow setting the publicUpload
					// before the share creation.
					// Todo: We also need to fix the createShare method in
					// lib/Controller/ShareAPIController.php to allow file requests
					// (currently not supported on create, only update)
				}

				console.debug('Creating link share with options', options)
				const newShare = await this.createShare(options)

				this.open = false
				this.shareCreationComplete = true
				console.debug('Link share created', newShare)
				// if share already exists, copy link directly on next tick
				let component
				if (update) {
					component = await new Promise(resolve => {
						this.$emit('update:share', newShare, resolve)
					})
				} else {
					// adding new share to the array and copying link to clipboard
					// using promise so that we can copy link in the same click function
					// and avoid firefox copy permissions issue
					component = await new Promise(resolve => {
						this.$emit('add:share', newShare, resolve)
					})
				}

				await this.getNode()
				emit('files:node:updated', this.node)

				// Execute the copy link method
				// freshly created share component
				// ! somehow does not works on firefox !
				if (!this.config.enforcePasswordForPublicLink) {
					// Only copy the link when the password was not forced,
					// otherwise the user needs to copy/paste the password before finishing the share.
					component.copyLink()
				}
				showSuccess(t('files_sharing', 'Link share created'))

			} catch (data) {
				const message = data?.response?.data?.ocs?.meta?.message
				if (!message) {
					showError(t('files_sharing', 'Error while creating the share'))
					console.error(data)
					return
				}

				if (message.match(/password/i)) {
					this.onSyncError('password', message)
				} else if (message.match(/date/i)) {
					this.onSyncError('expireDate', message)
				} else {
					this.onSyncError('pending', message)
				}
				throw data

			} finally {
				this.loading = false
				this.shareCreationComplete = true
			}
		},

		/**
		 * Handle password protection change.
		 * @param {boolean} enabled Whether password protection is enabled.
		 */
		onPasswordProtectedChange(enabled) {
			this.isPasswordProtected = enabled
		},

		/**
		 * Handle expiration date toggle change.
		 * @param {boolean} enabled Whether the expiration date is enabled.
		 */
		onExpirationDateToggleChange(enabled) {
			this.share.expireDate = enabled ? this.formatDateToString(this.config.defaultExpirationDate) : ''
		},

		/**
		 * Handle expiration date change.
		 * @param {Event} event The change event.
		 */
		expirationDateChanged(event) {
			const date = event.target.value
			this.onExpirationChange(date)
			this.defaultExpirationDateEnabled = !!date
		},

		/**
		 * Handle cancel action.
		 */
		onCancel() {
			if (!this.shareCreationComplete) {
				this.$emit('remove:share', this.share)
			}
		},
	},
}
