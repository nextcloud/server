<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcContent app-name="files">
		<Navigation v-if="!isPublic" />
		<FilesListView :is-public="isPublic" />
	</NcContent>
</template>

<script lang="ts">
import { isPublicShare } from '@nextcloud/sharing/public'
import { defineComponent } from 'vue'

import NcContent from '@nextcloud/vue/components/NcContent'

import Navigation from './views/Navigation.vue'
import FilesListView from './views/FilesListView.vue'

export default defineComponent({
	name: 'FilesApp',

	components: {
		NcContent,
		FilesListView,
		Navigation,
	},

	setup() {
		const isPublic = isPublicShare()

		return {
			isPublic,
		}
	},
})
</script>
