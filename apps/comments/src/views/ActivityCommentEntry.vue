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
	<Comment ref="comment"
		tag="li"
		v-bind="comment.props"
		:auto-complete="autoComplete"
		:resource-type="resourceType"
		:message="commentMessage"
		:resource-id="resourceId"
		:user-data="genMentionsData(comment.props.mentions)"
		class="comments-activity"
		@delete="reloadCallback()" />
</template>

<script lang="ts">
import type { PropType } from 'vue'
import { translate as t } from '@nextcloud/l10n'

import Comment from '../components/Comment.vue'
import CommentView from '../mixins/CommentView'

export default {
	name: 'ActivityCommentEntry',

	components: {
		Comment,
	},

	mixins: [CommentView],
	props: {
		comment: {
			type: Object,
			required: true,
		},
		reloadCallback: {
			type: Function as PropType<() => void>,
			required: true,
		},
	},

	data() {
		return {
			commentMessage: '',
		}
	},

	watch: {
		comment() {
			this.commentMessage = this.comment.props.message
		},
	},

	mounted() {
		this.commentMessage = this.comment.props.message
	},

	methods: {
		t,
	},
}
</script>

<style scoped>
.comments-activity {
	padding: 0;
}
</style>
