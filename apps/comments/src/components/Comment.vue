<!--
  - @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
	<component :is="tag"
		v-show="!deleted"
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
						<NcActionButton :close-after-click="true"
							icon="icon-rename"
							@click="onEdit">
							{{ t('comments', 'Edit comment') }}
						</NcActionButton>
						<NcActionSeparator />
						<NcActionButton :close-after-click="true"
							icon="icon-delete"
							@click="onDeleteWithUndo">
							{{ t('comments', 'Delete comment') }}
						</NcActionButton>
					</template>

					<NcActionButton v-else
						icon="icon-close"
						@click="onEditCancel">
						{{ t('comments', 'Cancel edit') }}
					</NcActionButton>
				</NcActions>

				<!-- Show loading if we're editing or deleting, not on new ones -->
				<div v-if="id && loading" class="comment_loading icon-loading-small" />

				<!-- Relative time to the comment creation -->
				<Moment v-else-if="creationDateTime" class="comment__timestamp" :timestamp="timestamp" />
			</div>

			<!-- Message editor -->
			<div v-if="editor || editing" class="comment__editor ">
				<NcRichContenteditable ref="editor"
					:auto-complete="autoComplete"
					:contenteditable="!loading"
					:value="localMessage"
					:user-data="userData"
					@update:value="updateLocalMessage"
					@submit="onSubmit" />
				<NcButton class="comment__submit"
					type="tertiary-no-background"
					native-type="submit"
					:aria-label="t('comments', 'Post comment')"
					:disabled="isEmptyMessage"
					@click="onSubmit">
					<template #icon>
						<span v-if="loading" class="icon-loading-small" />
						<ArrowRight v-else :size="20" />
					</template>
				</NcButton>
			</div>

			<!-- Message content -->
			<!-- The html is escaped and sanitized before rendering -->
			<!-- eslint-disable vue/no-v-html-->
			<div v-else
				:class="{'comment__message--expanded': expanded}"
				class="comment__message"
				@click="onExpand"
				v-html="renderedContent" />
			<!-- eslint-enable vue/no-v-html-->
		</div>
	</component>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import moment from '@nextcloud/moment'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import RichEditorMixin from '@nextcloud/vue/dist/Mixins/richEditor.js'
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'

import Moment from './Moment.vue'
import CommentMixin from '../mixins/CommentMixin.js'

// Dynamic loading
const NcRichContenteditable = () => import('@nextcloud/vue/dist/Components/NcRichContenteditable.js')

export default {
	name: 'Comment',

	components: {
		NcActionButton,
		NcActions,
		NcActionSeparator,
		ArrowRight,
		NcAvatar,
		NcButton,
		Moment,
		NcRichContenteditable,
	},
	mixins: [RichEditorMixin, CommentMixin],

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
		}
	},

	computed: {

		/**
		 * Is the current user the author of this comment
		 *
		 * @return {boolean}
		 */
		isOwnComment() {
			return getCurrentUser().uid === this.actorId
		},

		/**
		 * Rendered content as html string
		 *
		 * @return {string}
		 */
		renderedContent() {
			if (this.isEmptyMessage) {
				return ''
			}
			return this.renderContent(this.localMessage)
		},

		isEmptyMessage() {
			return !this.localMessage || this.localMessage.trim() === ''
		},

		timestamp() {
			// seconds, not milliseconds
			return parseInt(moment(this.creationDateTime).format('x'), 10) / 1000
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
		/**
		 * Update local Message on outer change
		 *
		 * @param {string} message the message to set
		 */
		updateLocalMessage(message) {
			this.localMessage = message.toString()
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
	gap: 16px;
	position: relative;
	padding: 5px $comment-padding;

	&__side {
		display: flex;
		align-items: flex-start;
		padding-top: 16px;
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
		margin-left: $comment-padding !important;
	}

	&__author {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		color: var(--color-text-maxcontrast);
	}

	&_loading,
	&__timestamp {
		margin-left: auto;
		text-align: right;
		white-space: nowrap;
		color: var(--color-text-maxcontrast);
	}

	&__submit {
		position: absolute !important;
		right: 10px;
		bottom: 5px;
		// Align with input border
		margin: 1px;
	}

	&__message {
		white-space: pre-wrap;
		word-break: break-word;
		max-height: 70px;
		overflow: hidden;
		margin-top: -6px;
		&--expanded {
			max-height: none;
			overflow: visible;
		}
	}

	div.rich-contenteditable__input {
		padding: 8px 35px 8px 8px;
		min-height: 69px;
		margin: 0;
    	position: relative;
	}

	div.rich-contenteditable__input:before {
		width: calc(100% - 40px);
		top: 10px;
		left: 10px;
	}

}

</style>
