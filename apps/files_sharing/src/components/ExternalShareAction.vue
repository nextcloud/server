<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Component :is="data.is"
		v-bind="data"
		v-on="action.handlers">
		{{ data.text }}
	</Component>
</template>

<script>
import Share from '../models/Share.ts'

export default {
	name: 'ExternalShareAction',

	props: {
		id: {
			type: String,
			required: true,
		},
		action: {
			type: Object,
			default: () => ({}),
		},
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
		share: {
			type: Share,
			default: null,
		},
	},

	computed: {
		data() {
			return this.action.data(this)
		},
	},
}
</script>
