<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import FirstDayOfWeekSection from '../components/PersonalInfo/FirstDayOfWeekSection.vue'
import LanguageSection from '../components/PersonalInfo/LanguageSection/LanguageSection.vue'
import LocaleSection from '../components/PersonalInfo/LocaleSection/LocaleSection.vue'
import TimezoneSection from '../components/PersonalInfo/TimezoneSection.vue'
import SettingsColumn from '../components/SettingsColumn.vue'
</script>

<template>
	<SettingsColumn class="language-locale-settings">
		<NcSettingsSection :name="t('settings', 'Language & locale')">
			<LanguageSection />
			<LocaleSection />
			<FirstDayOfWeekSection />
			<TimezoneSection />
		</NcSettingsSection>
	</SettingsColumn>
</template>
