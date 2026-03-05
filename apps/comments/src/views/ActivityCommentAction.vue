<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Comment
		v-bind="editorData"
		:auto-complete="autoComplete"
		:resource-type="resourceType"
		:editor="true"
		:user-data="userData"
		:resource-id="resourceId"
		class="comments-action"
		@new="onNewComment" />
</template>

<script lang="ts">
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import Comment from '../components/Comment.vue'
import logger from '../logger.js'
import CommentView from '../mixins/CommentView.js'

export default defineComponent({
	components: {
		Comment,
	},

	mixins: [CommentView],
	props: {
		reloadCallback: {
			type: Function,
			required: true,
		},
	},

	methods: {
		onNewComment() {
			try {
				// just force reload
				this.reloadCallback()
			} catch (error) {
				showError(t('comments', 'Could not reload comments'))
				logger.error('Could not reload comments', { error })
			}
		},
	},
})
</script>

<style scoped>
.comments-action {
	padding: 0;
}
</style>
