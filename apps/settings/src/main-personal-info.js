/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'

import AvatarSection from './components/PersonalInfo/AvatarSection.vue'
import DetailsSection from './components/PersonalInfo/DetailsSection.vue'
import DisplayNameSection from './components/PersonalInfo/DisplayNameSection.vue'
import EmailSection from './components/PersonalInfo/EmailSection/EmailSection.vue'
import PhoneSection from './components/PersonalInfo/PhoneSection.vue'
import LocationSection from './components/PersonalInfo/LocationSection.vue'
import WebsiteSection from './components/PersonalInfo/WebsiteSection.vue'
import TwitterSection from './components/PersonalInfo/TwitterSection.vue'
import FediverseSection from './components/PersonalInfo/FediverseSection.vue'
import LanguageSection from './components/PersonalInfo/LanguageSection/LanguageSection.vue'
import LocaleSection from './components/PersonalInfo/LocaleSection/LocaleSection.vue'
import ProfileSection from './components/PersonalInfo/ProfileSection/ProfileSection.vue'
import OrganisationSection from './components/PersonalInfo/OrganisationSection.vue'
import RoleSection from './components/PersonalInfo/RoleSection.vue'
import HeadlineSection from './components/PersonalInfo/HeadlineSection.vue'
import BiographySection from './components/PersonalInfo/BiographySection.vue'
import ProfileVisibilitySection from './components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue'
import BirthdaySection from './components/PersonalInfo/BirthdaySection.vue'

const profileEnabledGlobally = loadState('settings', 'profileEnabledGlobally', true)

Vue.mixin({
	methods: {
		t,
	},
})

const AvatarView = Vue.extend(AvatarSection)
const DetailsView = Vue.extend(DetailsSection)
const DisplayNameView = Vue.extend(DisplayNameSection)
const EmailView = Vue.extend(EmailSection)
const PhoneView = Vue.extend(PhoneSection)
const LocationView = Vue.extend(LocationSection)
const WebsiteView = Vue.extend(WebsiteSection)
const TwitterView = Vue.extend(TwitterSection)
const FediverseView = Vue.extend(FediverseSection)
const LanguageView = Vue.extend(LanguageSection)
const LocaleView = Vue.extend(LocaleSection)
const BirthdayView = Vue.extend(BirthdaySection)

new AvatarView().$mount('#vue-avatar-section')
new DetailsView().$mount('#vue-details-section')
new DisplayNameView().$mount('#vue-displayname-section')
new EmailView().$mount('#vue-email-section')
new PhoneView().$mount('#vue-phone-section')
new LocationView().$mount('#vue-location-section')
new WebsiteView().$mount('#vue-website-section')
new TwitterView().$mount('#vue-twitter-section')
new FediverseView().$mount('#vue-fediverse-section')
new LanguageView().$mount('#vue-language-section')
new LocaleView().$mount('#vue-locale-section')
new BirthdayView().$mount('#vue-birthday-section')

if (profileEnabledGlobally) {
	const ProfileView = Vue.extend(ProfileSection)
	const OrganisationView = Vue.extend(OrganisationSection)
	const RoleView = Vue.extend(RoleSection)
	const HeadlineView = Vue.extend(HeadlineSection)
	const BiographyView = Vue.extend(BiographySection)
	const ProfileVisibilityView = Vue.extend(ProfileVisibilitySection)

	new ProfileView().$mount('#vue-profile-section')
	new OrganisationView().$mount('#vue-organisation-section')
	new RoleView().$mount('#vue-role-section')
	new HeadlineView().$mount('#vue-headline-section')
	new BiographyView().$mount('#vue-biography-section')
	new ProfileVisibilityView().$mount('#vue-profile-visibility-section')
}
