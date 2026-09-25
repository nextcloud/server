<!--
	SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
	SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<a v-if="link" :class="$style.unknownArgument__link" :href="link">{{ name }}</a>
	<strong v-else>{{ name }}</strong>
</template>

<script setup lang="ts">
defineProps<{
	name: string
	link?: string
}>()
</script>

<style module>
.unknownArgument__link {
	text-decoration: underline;
}
</style>
