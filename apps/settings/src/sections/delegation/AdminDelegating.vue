<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import type { AuthorizedGroup, DelegatedSetting, Group } from './types.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import GroupSelect from './components/GroupSelect.vue'

const availableSettings = loadState<DelegatedSetting[]>('settings', 'available-settings')
const availableGroups = loadState<Group[]>('settings', 'available-groups')
const authorizedGroups = loadState<AuthorizedGroup[]>('settings', 'authorized-groups')
const authorizedSettingsDocLink = loadState<string>('settings', 'authorized-settings-doc-link')
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Administration privileges')"
		:description="t('settings', 'Here you can decide which group can access certain sections of the administration settings.')"
		:docUrl="authorizedSettingsDocLink">
		<div class="setting-list">
			<div v-for="setting in availableSettings" :key="setting.class">
				<label :class="$style.label" :for="setting.id">{{ setting.sectionName }}</label>
				<GroupSelect :availableGroups="availableGroups" :authorizedGroups="authorizedGroups" :setting="setting" />
			</div>
		</div>
	</NcSettingsSection>
</template>

<style module>
.label {
	color: var(--color-text-maxcontrast);
	display: block;
	font-size: 16px;
	margin: 12px 0 6px 0;
}
</style>
