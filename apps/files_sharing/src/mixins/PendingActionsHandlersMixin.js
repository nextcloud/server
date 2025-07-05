/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { emit } from '@nextcloud/event-bus'
import moment from '@nextcloud/moment'
import { ShareType } from '@nextcloud/sharing'
import GeneratePassword from '../utils/GeneratePassword.ts'
import Share from '../models/Share.ts'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import SharesMixin from './SharesMixin.js'
import logger from '../services/logger.ts'
import Config from '../services/ConfigService.ts'

/**
 * @mixin PendingActionsHandlersMixin
 *
 * This mixin provides the logic for handling the creation of new link shares,
 * including showing a pending actions dialog and processing the share creation
 * asynchronously.
 *
 * It follows a "template method" pattern. The main algorithm for creating a share
 * is defined in `pushNewLinkShare`, but specific steps are delegated to the
 * component that uses this mixin.
 *
 * IMPORTANT: Any component using this mixin MUST implement the following methods:
 *
 * - `_handleShareAdded(share, resolve)`: This method is called after a new share
 *   is successfully created. It is responsible for adding the new share to the
 *   component's state and updating the UI.
 *
 * - `_handleShareUpdated(share, resolve)`: This method is called when an existing
 *   share is updated (e.g., a new link share is created for a file that already
 *   had one). It should update the share in the component's state.
 *
 * The `resolve` function passed to both handlers MUST be called with the Vue
 * component instance corresponding to the newly added/updated share. This instance
 * is expected to have a `copyLink()` method, which will be called by `pushNewLinkShare`
 * to copy the new share link to the clipboard.
 */
export default {
	data() {
		return {
			open: false,
			config: new Config(),
			shareCreationComplete: false,
			pending: false,
			loading: false,
			defaultExpirationDateEnabled: false,
			errors: {},
			logger,
		}
	},
	computed: {
		passwordExpirationTime() {
			if (this.share.passwordExpirationTime === null) {
				return null
			}

			const expirationTime = moment(this.share.passwordExpirationTime)

			if (expirationTime.diff(moment()) < 0) {
				return false
			}

			return expirationTime.fromNow()
		},

		/**
		 * Is Talk enabled?
		 *
		 * @return {boolean}
		 */
		isTalkEnabled() {
			return OC.appswebroots.spreed !== undefined
		},

		/**
		 * Is it possible to protect the password by Talk?
		 *
		 * @return {boolean}
		 */
		isPasswordProtectedByTalkAvailable() {
			return this.isPasswordProtected && this.isTalkEnabled
		},

		/**
		 * Is the current share password protected by Talk?
		 *
		 * @return {boolean}
		 */
		isPasswordProtectedByTalk: {
			get() {
				return this.share.sendPasswordByTalk
			},
			async set(enabled) {
				this.share.sendPasswordByTalk = enabled
			},
		},

		/**
		 * Is the current share an email share ?
		 *
		 * @return {boolean}
		 */
		isEmailShareType() {
			return this.share
				? this.share.type === ShareType.Email
				: false
		},

		canTogglePasswordProtectedByTalkAvailable() {
			if (!this.isPasswordProtected) {
				// Makes no sense
				return false
			} else if (this.isEmailShareType && !this.hasUnsavedPassword) {
				// For email shares we need a new password in order to enable or
				// disable
				return false
			}

			// Anything else should be fine
			return true
		},

		/**
		 * Pending data.
		 * If the share still doesn't have an id, it is not synced
		 * Therefore this is still not valid and requires user input
		 *
		 * @return {boolean}
		 */
		pendingDataIsMissing() {
			return this.pendingPassword || this.pendingEnforcedPassword || this.pendingDefaultExpirationDate || this.pendingEnforcedExpirationDate
		},
		pendingPassword() {
			return this.config.enableLinkPasswordByDefault && this.isPendingShare
		},
		pendingEnforcedPassword() {
			return this.config.enforcePasswordForPublicLink && this.isPendingShare
		},
		pendingEnforcedExpirationDate() {
			return this.config.isDefaultExpireDateEnforced && this.isPendingShare
		},
		pendingDefaultExpirationDate() {
			return (this.config.defaultExpirationDate instanceof Date || !isNaN(new Date(this.config.defaultExpirationDate).getTime())) && this.isPendingShare
		},
		isPendingShare() {
			return !!(this.share && !this.share.id)
		},
		sharePolicyHasEnforcedProperties() {
			return this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced
		},

		enforcedPropertiesMissing() {
			// Ensure share exist and the share policy has required properties
			if (!this.sharePolicyHasEnforcedProperties) {
				return false
			}

			if (!this.share) {
				// if no share, we can't tell if properties are missing or not so we assume properties are missing
				return true
			}

			// If share has ID, then this is an incoming link share created from the existing link share
			// Hence assume required properties
			if (this.share.id) {
				return true
			}
			// Check if either password or expiration date is missing and enforced
			const isPasswordMissing = this.config.enforcePasswordForPublicLink && !this.share.password
			const isExpireDateMissing = this.config.isDefaultExpireDateEnforced && !this.share.expireDate

			return isPasswordMissing || isExpireDateMissing
		},
		// if newPassword exists, but is empty, it means
		// the user deleted the original password
		hasUnsavedPassword() {
			return this.share.newPassword !== undefined
		},
		/**
		 * Whether the share policy has enforced properties.
		 * @return {boolean}
		 */
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
			this.logger.debug('onNewLinkShare shareReviewComplete', shareReviewComplete)
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
					this._handleBeforeAddShare(share, resolve)
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
						this._handleShareUpdated(newShare, resolve)
					})
				} else {
					// adding new share to the array and copying link to clipboard
					// using promise so that we can copy link in the same click function
					// and avoid firefox copy permissions issue
					component = await new Promise(resolve => {
						this._handleShareAdded(newShare, resolve)
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
					// throw the original error to be caught by the caller
					throw data
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
			this.open = false
		},
	},
}
