<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CollectionList v-if="fileId"
		:id="fileId"
		type="file"
		:name="filename" />
</template>

<script>
import { CollectionList } from 'nextcloud-vue-collections'

export default {
	name: 'CollaborationView',
	components: {
		CollectionList,
	},
	computed: {
		fileId() {
			if (this.$root.model && this.$root.model.id) {
				return '' + this.$root.model.id
			}
			return null
		},
		filename() {
			if (this.$root.model && this.$root.model.name) {
				return '' + this.$root.model.name
			}
			return ''
		},
	},
}
</script>
