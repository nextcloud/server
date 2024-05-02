<template>
	<NcContent app-name="files">
		<Navigation />
		<FilesList />
	</NcContent>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'

import Navigation from './views/Navigation.vue'
import FilesList from './views/FilesList.vue'

export default defineComponent({
	name: 'FilesApp',

	components: {
		NcContent,
		FilesList,
		Navigation,
	},
})
</script>
