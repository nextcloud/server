<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { NcSettingsSection } from '@nextcloud/vue'
import ExampleContactSettings from '../components/ExampleContactSettings.vue'
import ExampleEventSettings from '../components/ExampleEventSettings.vue'

const hasContactsApp = loadState('dav', 'contactsEnabled')
const hasCalendarApp = loadState('dav', 'calendarEnabled')
</script>

<template>
	<NcSettingsSection
		id="example-content"
		:name="t('dav', 'Example content')"
		:description="t('dav', 'Example content serves to showcase the features of Nextcloud. Default content is shipped with Nextcloud, and can be replaced by custom content.')">
		<ExampleContactSettings v-if="hasContactsApp" />
		<ExampleEventSettings v-if="hasCalendarApp" />
	</NcSettingsSection>
</template>
