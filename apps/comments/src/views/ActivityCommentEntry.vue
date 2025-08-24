<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
