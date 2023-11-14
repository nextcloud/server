<template>
	<Comment v-bind="editorData"
		:auto-complete="autoComplete"
		:user-data="userData"
		:editor="true"
		:ressource-id="ressourceId"
		class="comments__writer"
		@new="onNewComment" />
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Comment from '../components/Comment.vue'
import CommentView from '../mixins/CommentView'
import logger from '../logger'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

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
			} catch (e) {
				showError(t('comments', 'Could not reload comments'))
				logger.debug(e)
			}
		},
	},
})
</script>
