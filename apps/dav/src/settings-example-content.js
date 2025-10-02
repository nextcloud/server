/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import ExampleContentSettingsSection from './views/ExampleContentSettingsSection.vue'

Vue.mixin({
	methods: {
		t,
		$t: t,
	},
})

const View = Vue.extend(ExampleContentSettingsSection);

(new View({})).$mount('#settings-example-content')
