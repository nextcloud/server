/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Gary Kim <gary@garykim.dev>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
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

import { showError, showSuccess } from '@nextcloud/dialogs'
import { getCurrentUser } from '@nextcloud/auth'
// eslint-disable-next-line import/no-unresolved, n/no-missing-import
import PQueue from 'p-queue'
import debounce from 'debounce'

import Share from '../models/Share.js'
import SharesRequests from './ShareRequests.js'
import ShareTypes from './ShareTypes.js'
import Config from '../services/ConfigService.js'

import {
	BUNDLED_PERMISSIONS,
} from '../lib/SharePermissionsToolBox.js'

export default {
	mixins: [SharesRequests, ShareTypes],

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

			// errors helpers
			errors: {},

			// component status toggles
			loading: false,
			saving: false,
			open: false,

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
				? window.dayNamesShort // provided by nextcloud
				: ['Sun.', 'Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.']
			const monthsShort = window.monthNamesShort
				? window.monthNamesShort // provided by nextcloud
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
		isFolder() {
			return this.fileInfo.type === 'dir'
		},
		isPublicShare() {
			const shareType = this.share.shareType ?? this.share.type
			return [this.SHARE_TYPES.SHARE_TYPE_LINK, this.SHARE_TYPES.SHARE_TYPE_EMAIL].includes(shareType)
		},
		isShareOwner() {
			return this.share && this.share.owner === getCurrentUser().uid
		},
		hasCustomPermissions() {
			const bundledPermissions = [
				BUNDLED_PERMISSIONS.ALL,
				BUNDLED_PERMISSIONS.READ_ONLY,
				BUNDLED_PERMISSIONS.FILE_DROP,
			]
			return !bundledPermissions.includes(this.share.permissions)
		},
	},

	methods: {
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
			if (share.expirationDate) {
				const date = share.expirationDate
				if (!date.isValid()) {
					return false
				}
			}
			return true
		},

		/**
		 * @param {string} date a date with YYYY-MM-DD format
		 * @return {Date} date
		 */
		parseDateString(date) {
			if (!date) {
				return
			}
			const regex = /([0-9]{4}-[0-9]{2}-[0-9]{2})/i
			return new Date(date.match(regex)?.pop())
		},

		/**
		 * @param {Date} date
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
			this.share.expireDate = this.formatDateToString(new Date(date))
		},

		/**
		 * Uncheck expire date
		 * We need this method because @update:checked
		 * is ran simultaneously as @uncheck, so
		 * so we cannot ensure data is up-to-date
		 */
		onExpirationDisable() {
			this.share.expireDate = ''
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
				console.debug('Share deleted', this.share.id)
				const message = this.share.itemType === 'file'
					? t('files_sharing', 'File "{path}" has been unshared', { path: this.share.path })
					: t('files_sharing', 'Folder "{path}" has been unshared', { path: this.share.path })
				showSuccess(message)
				this.$emit('remove:share', this.share)
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
				propertyNames.forEach(name => {
					if ((typeof this.share[name]) === 'object') {
						properties[name] = JSON.stringify(this.share[name])
					} else {
						properties[name] = this.share[name].toString()
					}
				})

				this.updateQueue.add(async () => {
					this.saving = true
					this.errors = {}
					try {
						const updatedShare = await this.updateShare(this.share.id, properties)

						if (propertyNames.indexOf('password') >= 0) {
							// reset password state after sync
							this.$delete(this.share, 'newPassword')

							// updates password expiration time after sync
							this.share.passwordExpirationTime = updatedShare.password_expiration_time
						}

						// clear any previous errors
						this.$delete(this.errors, propertyNames[0])
						showSuccess(t('files_sharing', 'Share {propertyName} saved', { propertyName: propertyNames[0] }))
					} catch ({ message }) {
						if (message && message !== '') {
							this.onSyncError(propertyNames[0], message)
							showError(t('files_sharing', message))
						}
					} finally {
						this.saving = false
					}
				})
				return
			}

			// This share does not exists on the server yet
			console.debug('Updated local share', this.share)
		},

		/**
		 * Manage sync errors
		 *
		 * @param {string} property the errored property, e.g. 'password'
		 * @param {string} message the error message
		 */
		onSyncError(property, message) {
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
