/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import predefinedStatuses from './predefinedStatuses.js'
import userStatus from './userStatus.js'
import userBackupStatus from './userBackupStatus.js'

Vue.use(Vuex)

export default new Store({
	modules: {
		predefinedStatuses,
		userStatus,
		userBackupStatus,
	},
	strict: true,
})
