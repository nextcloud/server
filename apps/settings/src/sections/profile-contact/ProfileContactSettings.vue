<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AvatarSection from './components/AvatarSection.vue'
import BiographySection from './components/BiographySection.vue'
import BirthdaySection from './components/BirthdaySection.vue'
import BlueskySection from './components/BlueskySection.vue'
import DetailsSection from './components/DetailsSection.vue'
import DisplayNameSection from './components/DisplayNameSection.vue'
import EmailSection from './components/EmailSection/EmailSection.vue'
import FediverseSection from './components/FediverseSection.vue'
import HeadlineSection from './components/HeadlineSection.vue'
import LocationSection from './components/LocationSection.vue'
import OrganisationSection from './components/OrganisationSection.vue'
import PhoneSection from './components/PhoneSection.vue'
import ProfileSection from './components/ProfileSection/ProfileSection.vue'
import PronounsSection from './components/PronounsSection.vue'
import RoleSection from './components/RoleSection.vue'
import TwitterSection from './components/TwitterSection.vue'
import WebsiteSection from './components/WebsiteSection.vue'
import SettingsColumn from '../../components/SettingsColumn.vue'

const profileEnabledGlobally = loadState('settings', 'profileEnabledGlobally', true)
</script>

<template>
	<SettingsColumn class="profile-contact-settings">
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
				<h3 class="profile-contact-settings__subheading">
					{{ t('settings', 'Organization') }}
				</h3>
				<OrganisationSection />
				<RoleSection />
			</template>

			<h3 class="profile-contact-settings__subheading">
				{{ t('settings', 'Contact') }}
			</h3>
			<p class="profile-contact-settings__hint">
				{{ t('settings', 'Notifications and password reset requests will be sent to your primary email') }}
			</p>
			<EmailSection />
			<PhoneSection />
			<WebsiteSection />
			<FediverseSection />
			<BlueskySection />
			<TwitterSection />
			<DetailsSection />
		</NcSettingsSection>
	</SettingsColumn>
</template>

<style lang="scss" scoped>
.profile-contact-settings {
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
