<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import SettingsColumn from '../../components/SettingsColumn.vue'
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

const profileEnabledGlobally = loadState('settings', 'profileEnabledGlobally', true)
</script>

<template>
	<SettingsColumn>
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
				<h3 :class="$style.subheading">
					{{ t('settings', 'Organization') }}
				</h3>
				<OrganisationSection />
				<RoleSection />
			</template>

			<h3 :class="$style.subheading">
				{{ t('settings', 'Contact') }}
			</h3>
			<p :class="$style.hint">
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

<style module>
.subheading {
	margin: calc(var(--default-grid-baseline) * 4) 0 0;
	padding: 0 10px;
	font-size: 16px;
	font-weight: bold;
}

.hint {
	margin: 0 0 var(--default-grid-baseline);
	padding: 0 10px;
	color: var(--color-text-maxcontrast);
}
</style>
