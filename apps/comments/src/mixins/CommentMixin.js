/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

import { showError, showUndo, TOAST_UNDO_TIMEOUT } from '@nextcloud/dialogs'
import NewComment from '../services/NewComment.js'
import DeleteComment from '../services/DeleteComment.js'
import EditComment from '../services/EditComment.js'
import logger from '../logger.js'

export default {
	props: {
		id: {
			type: Number,
			default: null,
		},
		message: {
			type: String,
			default: '',
		},
		resourceId: {
			type: [String, Number],
			required: true,
		},
		resourceType: {
			type: String,
			default: 'files',
		},
	},

	data() {
		return {
			deleted: false,
			editing: false,
			loading: false,
		}
	},

	methods: {
		// EDITION
		onEdit() {
			this.editing = true
		},
		onEditCancel() {
			this.editing = false
			// Restore original value
			this.updateLocalMessage(this.message)
		},
		async onEditComment(message) {
			this.loading = true
			try {
				await EditComment(this.resourceType, this.resourceId, this.id, message)
				logger.debug('Comment edited', { resourceType: this.resourceType, resourceId: this.resourceId, id: this.id, message })
				this.$emit('update:message', message)
				this.editing = false
			} catch (error) {
				showError(t('comments', 'An error occurred while trying to edit the comment'))
				console.error(error)
			} finally {
				this.loading = false
			}
		},

		// DELETION
		onDeleteWithUndo() {
			this.deleted = true
			const timeOutDelete = setTimeout(this.onDelete, TOAST_UNDO_TIMEOUT)
			showUndo(t('comments', 'Comment deleted'), () => {
				clearTimeout(timeOutDelete)
				this.deleted = false
			})
		},
		async onDelete() {
			try {
				await DeleteComment(this.resourceType, this.resourceId, this.id)
				logger.debug('Comment deleted', { resourceType: this.resourceType, resourceId: this.resourceId, id: this.id })
				this.$emit('delete', this.id)
			} catch (error) {
				showError(t('comments', 'An error occurred while trying to delete the comment'))
				console.error(error)
				this.deleted = false
			}
		},

		// CREATION
		async onNewComment(message) {
			this.loading = true
			try {
				const newComment = await NewComment(this.resourceType, this.resourceId, message)
				logger.debug('New comment posted', { resourceType: this.resourceType, resourceId: this.resourceId, newComment })
				this.$emit('new', newComment)

				// Clear old content
				this.$emit('update:message', '')
				this.localMessage = ''
			} catch (error) {
				showError(t('comments', 'An error occurred while trying to create the comment'))
				console.error(error)
			} finally {
				this.loading = false
			}
		},
	},
}
