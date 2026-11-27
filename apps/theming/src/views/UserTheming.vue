<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<UserThemeQuickSettings />

	<NcNoteCard v-if="isUserThemingDisabled" type="info">
		{{ t('theming', 'Customization has been disabled by your administrator') }}
	</NcNoteCard>

	<template v-else>
		<UserSectionPrimaryColor ref="primaryColor" @refreshStyles="refreshGlobalStyles" />
		<UserSectionBackground @refreshStyles="refreshGlobalStyles" />
	</template>

	<UserSectionCompatibility />
	<UserSectionAppMenu />
</template>

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { nextTick, useTemplateRef } from 'vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import UserSectionAppMenu from '../components/UserSectionAppMenu.vue'
import UserSectionBackground from '../components/UserSectionBackground.vue'
import UserSectionCompatibility from '../components/UserSectionCompatibility.vue'
import UserSectionPrimaryColor from '../components/UserSectionPrimaryColor.vue'
import UserThemeQuickSettings from '../components/UserThemeQuickSettings.vue'
import { refreshStyles } from '../utils/refreshStyles.js'

const isUserThemingDisabled = loadState('theming', 'isUserThemingDisabled')

const primaryColorSection = useTemplateRef('primaryColor')

/**
 * Refresh server-side generated theming CSS
 */
async function refreshGlobalStyles() {
	await refreshStyles()
	nextTick(() => primaryColorSection.value?.reload())
}
</script>

<style lang="scss" scoped>

.background {
	&__grid {
		margin-top: 30px;
	}
}
</style>
