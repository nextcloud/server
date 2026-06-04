<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AbsenceForm from '../components/AbsenceForm.vue'
import AvailabilityForm from '../components/AvailabilityForm.vue'

const hideAbsenceSettings = loadState('dav', 'hide_absence_settings', true)
</script>

<template>
	<div>
		<NcSettingsSection
			id="availability"
			:name="t('dav', 'Availability')"
			:description="t('dav', 'If you configure your working hours, other people will see when you are out of office when they book a meeting.')">
			<AvailabilityForm />
		</NcSettingsSection>
		<NcSettingsSection
			v-if="!hideAbsenceSettings"
			id="absence"
			:name="t('dav', 'Absence')"
			:description="t('dav', 'Configure your next absence period.')">
			<AbsenceForm />
		</NcSettingsSection>
	</div>
</template>
