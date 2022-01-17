/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import { getRequestToken } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import '@nextcloud/dialogs/styles/toast.scss'

import logger from './logger'

import DisplayNameSection from './components/PersonalInfo/DisplayNameSection/DisplayNameSection'
import EmailSection from './components/PersonalInfo/EmailSection/EmailSection'
import LanguageSection from './components/PersonalInfo/LanguageSection/LanguageSection'
import ProfileSection from './components/PersonalInfo/ProfileSection/ProfileSection'
import OrganisationSection from './components/PersonalInfo/OrganisationSection/OrganisationSection'
import RoleSection from './components/PersonalInfo/RoleSection/RoleSection'
import HeadlineSection from './components/PersonalInfo/HeadlineSection/HeadlineSection'
import BiographySection from './components/PersonalInfo/BiographySection/BiographySection'
import ProfileVisibilitySection from './components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection'

__webpack_nonce__ = btoa(getRequestToken())

Vue.mixin({
	props: {
		logger,
	},
	methods: {
		t,
	},
})

const DisplayNameView = Vue.extend(DisplayNameSection)
const EmailView = Vue.extend(EmailSection)
const LanguageView = Vue.extend(LanguageSection)
const ProfileView = Vue.extend(ProfileSection)
const OrganisationView = Vue.extend(OrganisationSection)
const RoleView = Vue.extend(RoleSection)
const HeadlineView = Vue.extend(HeadlineSection)
const BiographyView = Vue.extend(BiographySection)
const ProfileVisibilityView = Vue.extend(ProfileVisibilitySection)

new DisplayNameView().$mount('#vue-displayname-section')
new EmailView().$mount('#vue-email-section')
new LanguageView().$mount('#vue-language-section')
new ProfileView().$mount('#vue-profile-section')
new OrganisationView().$mount('#vue-organisation-section')
new RoleView().$mount('#vue-role-section')
new HeadlineView().$mount('#vue-headline-section')
new BiographyView().$mount('#vue-biography-section')
new ProfileVisibilityView().$mount('#vue-profile-visibility-section')
