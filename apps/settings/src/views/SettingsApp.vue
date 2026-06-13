<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent :class="$style.settingsApp" appName="settings">
		<SettingsNavigation />
		<SettingsContentWrapper />
	</NcContent>
</template>

<script setup lang="ts">
import NcContent from '@nextcloud/vue/components/NcContent'
import SettingsContentWrapper from './SettingsContentWrapper.vue'
import SettingsNavigation from './SettingsNavigation.vue'
</script>

<style module>
:global(#content):has(.settingsApp) {
	margin: 0;
	width: 100%;
	height: 100%;
}
</style>
