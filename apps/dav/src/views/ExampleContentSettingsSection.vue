<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection id="example-content"
		:name="$t('dav', 'Example content')"
		class="example-content-setting"
		:description="$t('dav', 'Example content serves to showcase the features of Nextcloud. Default content is shipped with Nextcloud, and can be replaced by custom content.')">
		<ExampleContactSettings v-if="hasContactsApp" />
		<ExampleEventSettings v-if="hasCalendarApp" />
	</NcSettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { NcSettingsSection } from '@nextcloud/vue'
import ExampleEventSettings from '../components/ExampleEventSettings.vue'
import ExampleContactSettings from '../components/ExampleContactSettings.vue'

export default {
	name: 'ExampleContentSettingsSection',
	components: {
		NcSettingsSection,
		ExampleContactSettings,
		ExampleEventSettings,
	},
	computed: {
		hasContactsApp() {
			return loadState('dav', 'contactsEnabled')
		},
		hasCalendarApp() {
			return loadState('dav', 'calendarEnabled')
		},
	}
}
</script>
