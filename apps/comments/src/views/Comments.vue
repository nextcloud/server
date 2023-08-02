<!--
  - @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div v-observe-visibility="onVisibilityChange"
		class="comments"
		:class="{ 'icon-loading': isFirstLoading }">
		<!-- Editor -->
		<Comment v-bind="editorData"
			:auto-complete="autoComplete"
			:user-data="userData"
			:editor="true"
			:ressource-id="ressourceId"
			class="comments__writer"
			@new="onNewComment" />

		<template v-if="!isFirstLoading">
			<NcEmptyContent v-if="!hasComments && done"
				class="comments__empty"
				:name="t('comments', 'No comments yet, start the conversation!')">
				<template #icon>
					<MessageReplyTextIcon />
				</template>
			</NcEmptyContent>
			<ul v-else>
				<!-- Comments -->
				<Comment v-for="comment in comments"
					:key="comment.props.id"
					tag="li"
					v-bind="comment.props"
					:auto-complete="autoComplete"
					:message.sync="comment.props.message"
					:ressource-id="ressourceId"
					:user-data="genMentionsData(comment.props.mentions)"
					class="comments__list"
					@delete="onDelete" />
			</ul>

			<!-- Loading more message -->
			<div v-if="loading && !isFirstLoading" class="comments__info icon-loading" />

			<div v-else-if="hasComments && done" class="comments__info">
				{{ t('comments', 'No more messages') }}
			</div>

			<!-- Error message -->
			<template v-else-if="error">
				<NcEmptyContent class="comments__error" :name="error">
					<template #icon>
						<AlertCircleOutlineIcon />
					</template>
				</NcEmptyContent>
				<NcButton class="comments__retry" @click="getComments">
					<template #icon>
						<RefreshIcon />
					</template>
					{{ t('comments', 'Retry') }}
				</NcButton>
			</template>
		</template>
	</div>
</template>

<script>
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import VTooltip from 'v-tooltip'
import Vue from 'vue'
import VueObserveVisibility from 'vue-observe-visibility'

import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import MessageReplyTextIcon from 'vue-material-design-icons/MessageReplyText.vue'
import AlertCircleOutlineIcon from 'vue-material-design-icons/AlertCircleOutline.vue'

import Comment from '../components/Comment.vue'
import { getComments, DEFAULT_LIMIT } from '../services/GetComments.ts'
import cancelableRequest from '../utils/cancelableRequest.js'
import { markCommentsAsRead } from '../services/ReadComments.ts'

Vue.use(VTooltip)
Vue.use(VueObserveVisibility)

export default {
	name: 'Comments',

	components: {
		// Avatar,
		Comment,
		NcEmptyContent,
		NcButton,
		RefreshIcon,
		MessageReplyTextIcon,
		AlertCircleOutlineIcon,
	},

	data() {
		return {
			error: '',
			loading: false,
			done: false,

			ressourceId: null,
			offset: 0,
			comments: [],

			cancelRequest: () => {},

			editorData: {
				actorDisplayName: getCurrentUser().displayName,
				actorId: getCurrentUser().uid,
				key: 'editor',
			},

			Comment,
			userData: {},
		}
	},

	computed: {
		hasComments() {
			return this.comments.length > 0
		},
		isFirstLoading() {
			return this.loading && this.offset === 0
		},
	},

	methods: {
		async onVisibilityChange(isVisible) {
			if (isVisible) {
				try {
					await markCommentsAsRead(this.commentsType, this.ressourceId, new Date())
				} catch (e) {
					showError(e.message || t('comments', 'Failed to mark comments as read'))
				}
			}
		},

		/**
		 * Update current ressourceId and fetch new data
		 *
		 * @param {number} ressourceId the current ressourceId (fileId...)
		 */
		async update(ressourceId) {
			this.ressourceId = ressourceId
			this.resetState()
			this.getComments()
		},

		/**
		 * Ran when the bottom of the tab is reached
		 */
		onScrollBottomReached() {
			/**
			 * Do not fetch more if we:
			 * - are showing an error
			 * - already fetched everything
			 * - are currently loading
			 */
			if (this.error || this.done || this.loading) {
				return
			}
			this.getComments()
		},

		/**
		 * Make sure we have all mentions as Array of objects
		 *
		 * @param {any[]} mentions the mentions list
		 * @return {Record<string, object>}
		 */
		genMentionsData(mentions) {
			Object.values(mentions)
				.flat()
				.forEach(mention => {
					this.userData[mention.mentionId] = {
						// TODO: support groups
						icon: 'icon-user',
						id: mention.mentionId,
						label: mention.mentionDisplayName,
						source: 'users',
						primary: getCurrentUser().uid === mention.mentionId,
					}
				})
			return this.userData
		},

		/**
		 * Get the existing shares infos
		 */
		async getComments() {
			// Cancel any ongoing request
			this.cancelRequest('cancel')

			try {
				this.loading = true
				this.error = ''

				// Init cancellable request
				const { request, abort } = cancelableRequest(getComments)
				this.cancelRequest = abort

				// Fetch comments
				const { data: comments } = await request({
					commentsType: this.commentsType,
					ressourceId: this.ressourceId,
				}, { offset: this.offset }) || { data: [] }

				this.logger.debug(`Processed ${comments.length} comments`, { comments })

				// We received less than the requested amount,
				// we're done fetching comments
				if (comments.length < DEFAULT_LIMIT) {
					this.done = true
				}

				// Insert results
				this.comments.push(...comments)

				// Increase offset for next fetch
				this.offset += DEFAULT_LIMIT
			} catch (error) {
				if (error.message === 'cancel') {
					return
				}
				this.error = t('comments', 'Unable to load the comments list')
				console.error('Error loading the comments list', error)
			} finally {
				this.loading = false
			}
		},

		/**
		 * Autocomplete @mentions
		 *
		 * @param {string} search the query
		 * @param {Function} callback the callback to process the results with
		 */
		async autoComplete(search, callback) {
			const results = await axios.get(generateOcsUrl('core/autocomplete/get'), {
				params: {
					search,
					itemType: 'files',
					itemId: this.ressourceId,
					sorter: 'commenters|share-recipients',
					limit: loadState('comments', 'maxAutoCompleteResults'),
				},
			})
			// Save user data so it can be used by the editor to replace mentions
			results.data.ocs.data.forEach(user => { this.userData[user.id] = user })
			return callback(Object.values(this.userData))
		},

		/**
		 * Add newly created comment to the list
		 *
		 * @param {object} comment the new comment
		 */
		onNewComment(comment) {
			this.comments.unshift(comment)
		},

		/**
		 * Remove deleted comment from the list
		 *
		 * @param {number} id the deleted comment
		 */
		onDelete(id) {
			const index = this.comments.findIndex(comment => comment.props.id === id)
			if (index > -1) {
				this.comments.splice(index, 1)
			} else {
				console.error('Could not find the deleted comment in the list', id)
			}
		},

		/**
		 * Reset the current view to its default state
		 */
		resetState() {
			this.error = ''
			this.loading = false
			this.done = false
			this.offset = 0
			this.comments = []
		},
	},
}
</script>

<style lang="scss" scoped>
.comments {
	// Do not add emptycontent top margin
	&__empty,
	&__error {
		margin-top: 0 !important;
	}

	&__retry {
		margin: 0 auto;
	}

	&__info {
		height: 60px;
		color: var(--color-text-maxcontrast);
		text-align: center;
		line-height: 60px;
	}
}
</style>
