/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import BackgroundJob from './BackgroundJob.vue'
import ProfileSettings from './ProfileSettings.vue'

createApp(BackgroundJob).mount('#vue-admin-background-job')

// Only rendered when profiles are enabled globally
const profileSettings = document.getElementById('vue-admin-profile-settings')
if (profileSettings) {
	createApp(ProfileSettings).mount(profileSettings)
}
