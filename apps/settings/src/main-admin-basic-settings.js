/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { getCSPNonce } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'

import logger from './logger.ts'

import ProfileSettings from './components/BasicSettings/ProfileSettings.vue'
import BackgroundJob from './components/BasicSettings/BackgroundJob.vue'

__webpack_nonce__ = getCSPNonce()

const profileEnabledGlobally = loadState('settings', 'profileEnabledGlobally', true)

Vue.mixin({
	props: {
		logger,
	},
	methods: {
		t,
	},
})

const BackgroundJobView = Vue.extend(BackgroundJob)
new BackgroundJobView().$mount('#vue-admin-background-job')

if (profileEnabledGlobally) {
	const ProfileSettingsView = Vue.extend(ProfileSettings)
	new ProfileSettingsView().$mount('#vue-admin-profile-settings')
}
