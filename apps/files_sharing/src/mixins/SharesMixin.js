/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { ShareType } from '@nextcloud/sharing'
import { emit } from '@nextcloud/event-bus'

import PQueue from 'p-queue'
import debounce from 'debounce'

import GeneratePassword from '../utils/GeneratePassword.ts'
import Share from '../models/Share.ts'
import SharesRequests from './ShareRequests.js'
import Config from '../services/ConfigService.ts'
import logger from '../services/logger.ts'

import {
	BUNDLED_PERMISSIONS,
} from '../lib/SharePermissionsToolBox.js'
import { fetchNode } from '../../../files/src/services/WebdavClient.ts'

export default {
	mixins: [SharesRequests],

	props: {
		fileInfo: {
			type: Object,
			default: () => { },
			required: true,
		},
		share: {
			type: Share,
			default: null,
		},
		isUnique: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			config: new Config(),
			node: null,
			ShareType,

			// errors helpers
			errors: {},

			// component status toggles
			loading: false,
			saving: false,
			open: false,

			/** @type {boolean | undefined} */
			passwordProtectedState: undefined,

			// concurrency management queue
			// we want one queue per share
			updateQueue: new PQueue({ concurrency: 1 }),

			/**
			 * ! This allow vue to make the Share class state reactive
			 * ! do not remove it ot you'll lose all reactivity here
			 */
			reactiveState: this.share?.state,
		}
	},

	computed: {
		path() {
			return (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/')
		},
		/**
		 * Does the current share have a note
		 *
		 * @return {boolean}
		 */
		hasNote: {
			get() {
				return this.share.note !== ''
			},
			set(enabled) {
				this.share.note = enabled
					? null // enabled but user did not changed the content yet
					: '' // empty = no note = disabled
			},
		},

		dateTomorrow() {
			return new Date(new Date().setDate(new Date().getDate() + 1))
		},

		// Datepicker language
		lang() {
			const weekdaysShort = window.dayNamesShort
				? window.dayNamesShort // provided by Nextcloud
				: ['Sun.', 'Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.']
			const monthsShort = window.monthNamesShort
				? window.monthNamesShort // provided by Nextcloud
				: ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.']
			const firstDayOfWeek = window.firstDay ? window.firstDay : 0

			return {
				formatLocale: {
					firstDayOfWeek,
					monthsShort,
					weekdaysMin: weekdaysShort,
					weekdaysShort,
				},
				monthFormat: 'MMM',
			}
		},
		isNewShare() {
			return !this.share.id
		},
		isFolder() {
			return this.fileInfo.type === 'dir'
		},
		isPublicShare() {
			const shareType = this.share.shareType ?? this.share.type
			return [ShareType.Link, ShareType.Email].includes(shareType)
		},
		isRemoteShare() {
			return this.share.type === ShareType.RemoteGroup || this.share.type === ShareType.Remote
		},
		isShareOwner() {
			return this.share && this.share.owner === getCurrentUser().uid
		},
		isExpiryDateEnforced() {
			if (this.isPublicShare) {
				return this.config.isDefaultExpireDateEnforced
			}
			if (this.isRemoteShare) {
				return this.config.isDefaultRemoteExpireDateEnforced
			}
			return this.config.isDefaultInternalExpireDateEnforced
		},
		hasCustomPermissions() {
			const bundledPermissions = [
				BUNDLED_PERMISSIONS.ALL,
				BUNDLED_PERMISSIONS.READ_ONLY,
				BUNDLED_PERMISSIONS.FILE_DROP,
			]
			return !bundledPermissions.includes(this.share.permissions)
		},
		maxExpirationDateEnforced() {
			if (this.isExpiryDateEnforced) {
				if (this.isPublicShare) {
					return this.config.defaultExpirationDate
				}
				if (this.isRemoteShare) {
					return this.config.defaultRemoteExpirationDateString
				}
				// If it get's here then it must be an internal share
				return this.config.defaultInternalExpirationDate
			}
			return null
		},
		/**
		 * Is the current share password protected ?
		 *
		 * @return {boolean}
		 */
		isPasswordProtected: {
			get() {
				if (this.config.enforcePasswordForPublicLink) {
					return true
				}
				if (this.passwordProtectedState !== undefined) {
					return this.passwordProtectedState
				}
				return typeof this.share.newPassword === 'string'
					|| typeof this.share.password === 'string'
			},
			async set(enabled) {
				if (enabled) {
					this.passwordProtectedState = true
					this.$set(this.share, 'newPassword', await GeneratePassword(true))
				} else {
					this.passwordProtectedState = false
					this.$set(this.share, 'newPassword', '')
				}
			},
		},
	},

	methods: {
		/**
		 * Fetch WebDAV node
		 *
		 * @return {Node}
		 */
		async getNode() {
			const node = { path: this.path }
			try {
				this.node = await fetchNode(node.path)
				logger.info('Fetched node:', { node: this.node })
			} catch (error) {
				logger.error('Error:', error)
			}
		},

		/**
		 * Check if a share is valid before
		 * firing the request
		 *
		 * @param {Share} share the share to check
		 * @return {boolean}
		 */
		checkShare(share) {
			if (share.password) {
				if (typeof share.password !== 'string' || share.password.trim() === '') {
					return false
				}
			}
			if (share.newPassword) {
				if (typeof share.newPassword !== 'string') {
					return false
				}
			}
			if (share.expirationDate) {
				const date = share.expirationDate
				if (!date.isValid()) {
					return false
				}
			}
			return true
		},

		/**
		 * @param {Date} date the date to format
		 * @return {string} date a date with YYYY-MM-DD format
		 */
		formatDateToString(date) {
			// Force utc time. Drop time information to be timezone-less
			const utcDate = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
			// Format to YYYY-MM-DD
			return utcDate.toISOString().split('T')[0]
		},

		/**
		 * Save given value to expireDate and trigger queueUpdate
		 *
		 * @param {Date} date
		 */
		onExpirationChange(date) {
			if (!date) {
				this.share.expireDate = null
				this.$set(this.share, 'expireDate', null)
				return
			}
			const parsedDate = (date instanceof Date) ? date : new Date(date)
			this.share.expireDate = this.formatDateToString(parsedDate)
		},

		/**
		 * Note changed, let's save it to a different key
		 *
		 * @param {string} note the share note
		 */
		onNoteChange(note) {
			this.$set(this.share, 'newNote', note.trim())
		},

		/**
		 * When the note change, we trim, save and dispatch
		 *
		 */
		onNoteSubmit() {
			if (this.share.newNote) {
				this.share.note = this.share.newNote
				this.$delete(this.share, 'newNote')
				this.queueUpdate('note')
			}
		},

		/**
		 * Delete share button handler
		 */
		async onDelete() {
			try {
				this.loading = true
				this.open = false
				await this.deleteShare(this.share.id)
				logger.debug('Share deleted', { shareId: this.share.id })
				const message = this.share.itemType === 'file'
					? t('files_sharing', 'File "{path}" has been unshared', { path: this.share.path })
					: t('files_sharing', 'Folder "{path}" has been unshared', { path: this.share.path })
				showSuccess(message)
				this.$emit('remove:share', this.share)
				await this.getNode()
				emit('files:node:updated', this.node)
			} catch (error) {
				// re-open menu if error
				this.open = true
			} finally {
				this.loading = false
			}
		},

		/**
		 * Send an update of the share to the queue
		 *
		 * @param {Array<string>} propertyNames the properties to sync
		 */
		queueUpdate(...propertyNames) {
			if (propertyNames.length === 0) {
				// Nothing to update
				return
			}

			if (this.share.id) {
				const properties = {}
				// force value to string because that is what our
				// share api controller accepts
				for (const name of propertyNames) {
					if (name === 'password') {
						if (this.share.newPassword !== undefined) {
							properties[name] = this.share.newPassword
						}
						continue
					}

					if (this.share[name] === null || this.share[name] === undefined) {
						properties[name] = ''
					} else if ((typeof this.share[name]) === 'object') {
						properties[name] = JSON.stringify(this.share[name])
					} else {
						properties[name] = this.share[name].toString()
					}
				}

				return this.updateQueue.add(async () => {
					this.saving = true
					this.errors = {}
					try {
						const updatedShare = await this.updateShare(this.share.id, properties)

						if (propertyNames.includes('password')) {
							// reset password state after sync
							this.share.password = this.share.newPassword || undefined
							this.$delete(this.share, 'newPassword')

							// updates password expiration time after sync
							this.share.passwordExpirationTime = updatedShare.password_expiration_time
						}

						// clear any previous errors
						for (const property of propertyNames) {
							this.$delete(this.errors, property)
						}
						showSuccess(this.updateSuccessMessage(propertyNames))
					} catch (error) {
						logger.error('Could not update share', { error, share: this.share, propertyNames })

						const { message } = error
						if (message && message !== '') {
							for (const property of propertyNames) {
								this.onSyncError(property, message)
							}
							showError(message)
						} else {
							// We do not have information what happened, but we should still inform the user
							showError(t('files_sharing', 'Could not update share'))
						}
					} finally {
						this.saving = false
					}
				})
			}

			// This share does not exists on the server yet
			console.debug('Updated local share', this.share)
		},

		/**
		 * @param {string[]} names Properties changed
		 */
		updateSuccessMessage(names) {
			if (names.length !== 1) {
				return t('files_sharing', 'Share saved')
			}

			switch (names[0]) {
			case 'expireDate':
				return t('files_sharing', 'Share expiry date saved')
			case 'hideDownload':
				return t('files_sharing', 'Share hide-download state saved')
			case 'label':
				return t('files_sharing', 'Share label saved')
			case 'note':
				return t('files_sharing', 'Share note for recipient saved')
			case 'password':
				return t('files_sharing', 'Share password saved')
			case 'permissions':
				return t('files_sharing', 'Share permissions saved')
			default:
				return t('files_sharing', 'Share saved')
			}
		},

		/**
		 * Manage sync errors
		 *
		 * @param {string} property the errored property, e.g. 'password'
		 * @param {string} message the error message
		 */
		onSyncError(property, message) {
			if (property === 'password' && this.share.newPassword !== undefined) {
				if (this.share.newPassword === this.share.password) {
					this.share.password = ''
				}
				this.$delete(this.share, 'newPassword')
			}

			// re-open menu if closed
			this.open = true
			switch (property) {
			case 'password':
			case 'pending':
			case 'expireDate':
			case 'label':
			case 'note': {
				// show error
				this.$set(this.errors, property, message)

				let propertyEl = this.$refs[property]
				if (propertyEl) {
					if (propertyEl.$el) {
						propertyEl = propertyEl.$el
					}
					// focus if there is a focusable action element
					const focusable = propertyEl.querySelector('.focusable')
					if (focusable) {
						focusable.focus()
					}
				}
				break
			}
			case 'sendPasswordByTalk': {
				// show error
				this.$set(this.errors, property, message)

				// Restore previous state
				this.share.sendPasswordByTalk = !this.share.sendPasswordByTalk
				break
			}
			}
		},
		/**
		 * Debounce queueUpdate to avoid requests spamming
		 * more importantly for text data
		 *
		 * @param {string} property the property to sync
		 */
		debounceQueueUpdate: debounce(function(property) {
			this.queueUpdate(property)
		}, 500),
	},
}
