<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

import AvatarSection from '../components/PersonalInfo/AvatarSection.vue'
import BiographySection from '../components/PersonalInfo/BiographySection.vue'
import BirthdaySection from '../components/PersonalInfo/BirthdaySection.vue'
import BlueskySection from '../components/PersonalInfo/BlueskySection.vue'
import DetailsSection from '../components/PersonalInfo/DetailsSection.vue'
import DisplayNameSection from '../components/PersonalInfo/DisplayNameSection.vue'
import EmailSection from '../components/PersonalInfo/EmailSection/EmailSection.vue'
import FediverseSection from '../components/PersonalInfo/FediverseSection.vue'
import FirstDayOfWeekSection from '../components/PersonalInfo/FirstDayOfWeekSection.vue'
import HeadlineSection from '../components/PersonalInfo/HeadlineSection.vue'
import LanguageSection from '../components/PersonalInfo/LanguageSection/LanguageSection.vue'
import LocaleSection from '../components/PersonalInfo/LocaleSection/LocaleSection.vue'
import LocationSection from '../components/PersonalInfo/LocationSection.vue'
import OrganisationSection from '../components/PersonalInfo/OrganisationSection.vue'
import PhoneSection from '../components/PersonalInfo/PhoneSection.vue'
import ProfileSection from '../components/PersonalInfo/ProfileSection/ProfileSection.vue'
import PronounsSection from '../components/PersonalInfo/PronounsSection.vue'
import RoleSection from '../components/PersonalInfo/RoleSection.vue'
import TimezoneSection from '../components/PersonalInfo/TimezoneSection.vue'
import TwitterSection from '../components/PersonalInfo/TwitterSection.vue'
import WebsiteSection from '../components/PersonalInfo/WebsiteSection.vue'

const profileEnabledGlobally = loadState('settings', 'profileEnabledGlobally', true)
</script>

<template>
	<div class="personal-info-settings">
		<NcSettingsSection :name="t('settings', 'Profile & contact')">
			<ProfileSection v-if="profileEnabledGlobally" />
			<AvatarSection />
			<DisplayNameSection />
			<PronounsSection />
			<LocationSection />
			<BirthdaySection />
			<template v-if="profileEnabledGlobally">
				<HeadlineSection />
				<BiographySection />
				<h3 class="personal-info-settings__subheading">{{ t('settings', 'Organization') }}</h3>
				<OrganisationSection />
				<RoleSection />
			</template>

			<h3 class="personal-info-settings__subheading">{{ t('settings', 'Contact') }}</h3>
			<p class="personal-info-settings__hint">{{ t('settings', 'Notifications will be sent to your primary email') }}</p>
			<EmailSection />
			<PhoneSection />
			<WebsiteSection />
			<FediverseSection />
			<BlueskySection />
			<TwitterSection />
			<DetailsSection />
		</NcSettingsSection>

		<NcSettingsSection :name="t('settings', 'Language & locale')">
			<LanguageSection />
			<LocaleSection />
			<FirstDayOfWeekSection />
			<TimezoneSection />
		</NcSettingsSection>
	</div>
</template>

<style lang="scss" scoped>
.personal-info-settings {
	&__subheading {
		margin: 16px 0 0;
		padding: 0 10px;
		font-size: 16px;
		font-weight: bold;
	}

	&__hint {
		margin: 0 0 4px;
		padding: 0 10px;
		color: var(--color-text-maxcontrast);
	}
}
</style>
