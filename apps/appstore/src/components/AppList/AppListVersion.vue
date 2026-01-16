<script setup lang="ts">
import type { IAppstoreApp } from '../../apps.d.ts'

defineProps<{
	app: IAppstoreApp
}>()
</script>

<template>
	<div :class="$style.appListVersion">
		<span v-if="app.version">{{ app.version }}</span>
		<span v-else-if="app.appstoreData?.releases[0]?.version">
			{{ app.appstoreData.releases[0].version }}
		</span>
	</div>
</template>

<style module>
.appListVersion {
	color: var(--color-text-maxcontrast);
}
</style>
