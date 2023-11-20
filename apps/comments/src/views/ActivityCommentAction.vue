<!--
  - @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @license AGPL-3.0-or-later
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
	<Comment v-bind="editorData"
		:auto-complete="autoComplete"
		:resource-type="resourceType"
		:editor="true"
		:user-data="userData"
		:resource-id="resourceId"
		class="comments-action"
		@new="onNewComment" />
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Comment from '../components/Comment.vue'
import CommentView from '../mixins/CommentView.js'
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

<style scoped>
.comments-action {
	padding: 0;
}
</style>
