/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import ExampleContentSettingsSection from './views/ExampleContentSettingsSection.vue'

Vue.mixin({
	methods: {
		t: translate,
		$t: translate,
	}
})

const View = Vue.extend(ExampleContentSettingsSection);

(new View({})).$mount('#settings-example-content')
