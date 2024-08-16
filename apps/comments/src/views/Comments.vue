<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div v-element-visibility="onVisibilityChange"
		class="comments"
		:class="{ 'icon-loading': isFirstLoading }">
		<!-- Editor -->
		<Comment v-bind="editorData"
			:auto-complete="autoComplete"
			:resource-type="resourceType"
			:editor="true"
			:user-data="userData"
			:resource-id="currentResourceId"
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
					:resource-type="resourceType"
					:message.sync="comment.props.message"
					:resource-id="currentResourceId"
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
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { vElementVisibility as elementVisibility } from '@vueuse/components'

import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import MessageReplyTextIcon from 'vue-material-design-icons/MessageReplyText.vue'
import AlertCircleOutlineIcon from 'vue-material-design-icons/AlertCircleOutline.vue'

import Comment from '../components/Comment.vue'
import CommentView from '../mixins/CommentView'
import cancelableRequest from '../utils/cancelableRequest.js'
import { getComments, DEFAULT_LIMIT } from '../services/GetComments.ts'
import { markCommentsAsRead } from '../services/ReadComments.ts'

export default {
	name: 'Comments',

	components: {
		Comment,
		NcEmptyContent,
		NcButton,
		RefreshIcon,
		MessageReplyTextIcon,
		AlertCircleOutlineIcon,
	},

	directives: {
		elementVisibility,
	},

	mixins: [CommentView],

	data() {
		return {
			error: '',
			loading: false,
			done: false,

			currentResourceId: this.resourceId,
			offset: 0,
			comments: [],

			cancelRequest: () => {},

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

	watch: {
		resourceId() {
			this.currentResourceId = this.resourceId
		},
	},

	methods: {
		t,

		async onVisibilityChange(isVisible) {
			if (isVisible) {
				try {
					await markCommentsAsRead(this.resourceType, this.currentResourceId, new Date())
				} catch (e) {
					showError(e.message || t('comments', 'Failed to mark comments as read'))
				}
			}
		},

		/**
		 * Update current resourceId and fetch new data
		 *
		 * @param {number} resourceId the current resourceId (fileId...)
		 */
		async update(resourceId) {
			this.currentResourceId = resourceId
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
					resourceType: this.resourceType,
					resourceId: this.currentResourceId,
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
	min-height: 100%;
	display: flex;
	flex-direction: column;

	&__empty,
	&__error {
		flex: 1 0;
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
