<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<component :is="tag"
		v-show="!deleted && !isLimbo"
		:class="{'comment--loading': loading}"
		class="comment">
		<!-- Comment header toolbar -->
		<div class="comment__side">
			<!-- Author -->
			<NcAvatar class="comment__avatar"
				:display-name="actorDisplayName"
				:user="actorId"
				:size="32" />
		</div>
		<div class="comment__body">
			<div class="comment__header">
				<span class="comment__author">{{ actorDisplayName }}</span>

				<!-- Comment actions,
					show if we have a message id and current user is author -->
				<NcActions v-if="isOwnComment && id && !loading" class="comment__actions">
					<template v-if="!editing">
						<NcActionButton close-after-click
							@click="onEdit">
							<template #icon>
								<IconEdit :size="20" />
							</template>
							{{ t('comments', 'Edit comment') }}
						</NcActionButton>
						<NcActionSeparator />
						<NcActionButton close-after-click
							@click="onDeleteWithUndo">
							<template #icon>
								<IconDelete :size="20" />
							</template>
							{{ t('comments', 'Delete comment') }}
						</NcActionButton>
					</template>

					<NcActionButton v-else @click="onEditCancel">
						<template #icon>
							<IconClose :size="20" />
						</template>
						{{ t('comments', 'Cancel edit') }}
					</NcActionButton>
				</NcActions>

				<!-- Show loading if we're editing or deleting, not on new ones -->
				<div v-if="id && loading" class="comment_loading icon-loading-small" />

				<!-- Relative time to the comment creation -->
				<NcDateTime v-else-if="creationDateTime"
					class="comment__timestamp"
					:timestamp="timestamp"
					:ignore-seconds="true" />
			</div>

			<!-- Message editor -->
			<form v-if="editor || editing" class="comment__editor" @submit.prevent>
				<div class="comment__editor-group">
					<NcRichContenteditable ref="editor"
						:auto-complete="autoComplete"
						:contenteditable="!loading"
						:label="editor ? t('comments', 'New comment') : t('comments', 'Edit comment')"
						:placeholder="t('comments', 'Write a comment …')"
						:value="localMessage"
						:user-data="userData"
						aria-describedby="tab-comments__editor-description"
						@update:value="updateLocalMessage"
						@submit="onSubmit" />
					<div class="comment__submit">
						<NcButton type="tertiary-no-background"
							native-type="submit"
							:aria-label="t('comments', 'Post comment')"
							:disabled="isEmptyMessage"
							@click="onSubmit">
							<template #icon>
								<NcLoadingIcon v-if="loading" />
								<IconArrowRight v-else :size="20" />
							</template>
						</NcButton>
					</div>
				</div>
				<div id="tab-comments__editor-description" class="comment__editor-description">
					{{ t('comments', '@ for mentions, : for emoji, / for smart picker') }}
				</div>
			</form>

			<!-- Message content -->
			<NcRichText v-else
				class="comment__message"
				:class="{'comment__message--expanded': expanded}"
				:text="richContent.message"
				:arguments="richContent.mentions"
				@click="onExpand" />
		</div>
	</component>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'

import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'

import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import IconClose from 'vue-material-design-icons/Close.vue'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import IconEdit from 'vue-material-design-icons/Pencil.vue'

import CommentMixin from '../mixins/CommentMixin.js'
import { mapStores } from 'pinia'
import { useDeletedCommentLimbo } from '../store/deletedCommentLimbo.js'

// Dynamic loading
const NcRichContenteditable = () => import('@nextcloud/vue/components/NcRichContenteditable')
const NcRichText = () => import('@nextcloud/vue/components/NcRichText')

export default {
	name: 'Comment',

	components: {
		IconArrowRight,
		IconClose,
		IconDelete,
		IconEdit,
		NcActionButton,
		NcActions,
		NcActionSeparator,
		NcAvatar,
		NcButton,
		NcDateTime,
		NcLoadingIcon,
		NcRichContenteditable,
		NcRichText,
	},
	mixins: [CommentMixin],

	inheritAttrs: false,

	props: {
		actorDisplayName: {
			type: String,
			required: true,
		},
		actorId: {
			type: String,
			required: true,
		},
		creationDateTime: {
			type: String,
			default: null,
		},

		/**
		 * Force the editor display
		 */
		editor: {
			type: Boolean,
			default: false,
		},

		/**
		 * Provide the autocompletion data
		 */
		autoComplete: {
			type: Function,
			required: true,
		},
		userData: {
			type: Object,
			default: () => ({}),
		},

		tag: {
			type: String,
			default: 'div',
		},
	},

	data() {
		return {
			expanded: false,
			// Only change data locally and update the original
			// parent data when the request is sent and resolved
			localMessage: '',
			submitted: false,
		}
	},

	computed: {
		...mapStores(useDeletedCommentLimbo),

		/**
		 * Is the current user the author of this comment
		 *
		 * @return {boolean}
		 */
		isOwnComment() {
			return getCurrentUser().uid === this.actorId
		},

		richContent() {
			const mentions = {}
			let message = this.localMessage

			Object.keys(this.userData).forEach((user, index) => {
				const key = `mention-${index}`
				const regex = new RegExp(`@${user}|@"${user}"`, 'g')
				message = message.replace(regex, `{${key}}`)
				mentions[key] = {
					component: NcUserBubble,
					props: {
						user,
						displayName: this.userData[user].label,
						primary: this.userData[user].primary,
					},
				}
			})

			return { mentions, message }
		},

		isEmptyMessage() {
			return !this.localMessage || this.localMessage.trim() === ''
		},

		/**
		 * Timestamp of the creation time (in ms UNIX time)
		 */
		timestamp() {
			return Date.parse(this.creationDateTime)
		},

		isLimbo() {
			return this.deletedCommentLimboStore.checkForId(this.id)
		},
	},

	watch: {
		// If the data change, update the local value
		message(message) {
			this.updateLocalMessage(message)
		},
	},

	beforeMount() {
		// Init localMessage
		this.updateLocalMessage(this.message)
	},

	methods: {
		t,

		/**
		 * Update local Message on outer change
		 *
		 * @param {string} message the message to set
		 */
		updateLocalMessage(message) {
			this.localMessage = message.toString()
			this.submitted = false
		},

		/**
		 * Dispatch message between edit and create
		 */
		onSubmit() {
			// Do not submit if message is empty
			if (this.localMessage.trim() === '') {
				return
			}

			if (this.editor) {
				this.onNewComment(this.localMessage.trim())
				this.$nextTick(() => {
					// Focus the editor again
					this.$refs.editor.$el.focus()
				})
				return
			}
			this.onEditComment(this.localMessage.trim())
		},

		onExpand() {
			this.expanded = true
		},
	},

}
</script>

<style lang="scss" scoped>
@use "sass:math";

$comment-padding: 10px;

.comment {
	display: flex;
	gap: 8px;
	padding: 5px $comment-padding;

	&__side {
		display: flex;
		align-items: flex-start;
		padding-top: 6px;
	}

	&__body {
		display: flex;
		flex-grow: 1;
		flex-direction: column;
	}

	&__header {
		display: flex;
		align-items: center;
		min-height: 44px;
	}

	&__actions {
		margin-inline-start: $comment-padding !important;
	}

	&__author {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		color: var(--color-text-maxcontrast);
	}

	&_loading,
	&__timestamp {
		margin-inline-start: auto;
		text-align: end;
		white-space: nowrap;
		color: var(--color-text-maxcontrast);
	}

	&__editor-group {
		position: relative;
	}

	&__editor-description {
		color: var(--color-text-maxcontrast);
		padding-block: var(--default-grid-baseline);
	}

	&__submit {
		position: absolute !important;
		bottom: 5px;
		inset-inline-end: 0;
	}

	&__message {
		white-space: pre-wrap;
		word-break: normal;
		max-height: 70px;
		overflow: hidden;
		margin-top: -6px;
		&--expanded {
			max-height: none;
			overflow: visible;
		}
	}
}

.rich-contenteditable__input {
	min-height: 44px;
	margin: 0;
	padding: $comment-padding;
}

</style>
