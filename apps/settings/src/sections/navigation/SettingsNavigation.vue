<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ISettingsSection } from './SettingsNavigationItem.vue'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import SettingsNavigationItem from './SettingsNavigationItem.vue'

const {
	personal: personalSections,
	admin: adminSections,
} = loadState<{ admin: ISettingsSection[], personal: ISettingsSection[] }>('settings', 'sections')
const hasAdminSections = adminSections.length > 0
</script>

<template>
	<NcAppNavigation :ariaLabel="t('settings', 'Settings')">
		<NcAppNavigationCaption
			headingId="settings-personal_section_heading"
			isHeading
			:name="t('settings', 'Personal')" />
		<NcAppNavigationList aria-labelledby="settings-personal_section_heading">
			<SettingsNavigationItem
				v-for="section in personalSections"
				:key="'personal-section--' + section.id"
				:section="section"
				type="personal" />
		</NcAppNavigationList>

		<template v-if="hasAdminSections">
			<NcAppNavigationCaption
				headingId="settings-admin_section_heading"
				isHeading
				:name="t('settings', 'Administration')" />
			<NcAppNavigationList aria-labelledby="settings-admin_section_heading">
				<SettingsNavigationItem
					v-for="section in adminSections"
					:key="'admin-section--' + section.id"
					:section="section"
					type="admin" />
			</NcAppNavigationList>
		</template>
	</NcAppNavigation>
</template>
