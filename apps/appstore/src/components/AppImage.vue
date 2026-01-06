<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { mdiCogOutline } from '@mdi/js'
import { NcLoadingIcon } from '@nextcloud/vue'
import { ref, watchEffect } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	app: IAppstoreApp | IAppstoreExApp
}>()

const isError = ref(false)
const isLoading = ref(true)
watchEffect(() => {
	if (props.app.screenshot) {
		isError.value = false
		isLoading.value = true
		const image = new Image()
		image.onload = () => {
			isLoading.value = false
		}
		image.onerror = () => {
			isError.value = true
			isLoading.value = false
		}
		image.src = props.app.screenshot
	} else {
		isLoading.value = false
		isError.value = false
	}
})
</script>

<template>
	<div :class="$style.appImage">
		<NcIconSvgWrapper
			v-if="isError || !props.app.screenshot"
			:size="80"
			:path="mdiCogOutline" />

		<NcLoadingIcon v-else-if="isLoading" :size="80" />

		<img :class="$style.appImage__image" :src="props.app.screenshot" alt="">
	</div>
</template>

<style module>
.appImage {
	display: flex;
	justify-content: center;
	width: 100%;
	height: 100%;
}

.appImage__image {
	object-fit: cover;
	height: 100%;
	width: 100%;
}
</style>
