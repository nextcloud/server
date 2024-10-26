<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSettingsSection id="availability"
			:name="$t('dav', 'Availability')"
			:description="$t('dav', 'If you configure your working hours, other people will see when you are out of office when they book a meeting.')">
			<AvailabilityForm />
		</NcSettingsSection>
		<NcSettingsSection v-if="!hideAbsenceSettings"
			id="absence"
			:name="$t('dav', 'Absence')"
			:description="$t('dav', 'Configure your next absence period.')">
			<AbsenceForm />
		</NcSettingsSection>
	</div>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import AbsenceForm from '../components/AbsenceForm.vue'
import AvailabilityForm from '../components/AvailabilityForm.vue'
import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'Availability',
	components: {
		NcSettingsSection,
		AbsenceForm,
		AvailabilityForm,
	},
	data() {
		return {
			hideAbsenceSettings: loadState('dav', 'hide_absence_settings', true),
		}
	},
}
</script>
