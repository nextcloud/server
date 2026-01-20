<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IProfileSection } from '../services/ProfileSections.ts'

defineProps<{
	section: IProfileSection
	userId: string | undefined
}>()
</script>

<template>
	<div :class="$style.profileSection">
		<component
			:is="section.tagName"
			v-bind.prop="section.params"
			:user.prop="userId" />
	</div>
</template>

<style module>
.profileSection {
	margin-top: 2rem;
}
</style>
