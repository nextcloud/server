/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { createApp } from 'vue'
import BackgroundJob from './BackgroundJob.vue'
import ProfileSettings from './ProfileSettings.vue'

createApp(BackgroundJob).mount('#vue-admin-background-job')

if (loadState('settings', 'profileEnabledGlobally', true)) {
	createApp(ProfileSettings).mount('#vue-admin-profile-settings')
}
