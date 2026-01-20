<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcNoteCard
		v-if="!isThemeable"
		:text="notThemeableErrorMessage"
		show-alert
		type="error" />
	<template v-else>
		<AdminSectionTheming />
		<AdminSectionThemingAdvanced />
	</template>
	<AdminSectionAppMenu />
</template>

<script setup lang="ts">
import type { AdminThemingInfo } from '../types.d.ts'

import { loadState } from '@nextcloud/initial-state'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import AdminSectionAppMenu from '../components/AdminSectionAppMenu.vue'
import AdminSectionTheming from '../components/AdminSectionTheming.vue'
import AdminSectionThemingAdvanced from '../components/AdminSectionThemingAdvanced.vue'

const { isThemeable, notThemeableErrorMessage } = loadState<AdminThemingInfo>('theming', 'adminThemingInfo')
</script>
