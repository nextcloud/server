<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog name="Fill Template">
		{{ fields }}
	</NcDialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { NcDialog } from '@nextcloud/vue'

export default defineComponent({
	name: 'TemplateFiller',

	components: {
		NcDialog,
	},

	props: {
		fields: {
			type: Array,
			default: [],
		},
	},

	methods: {},
})
</script>

<style scoped>
	
</style>