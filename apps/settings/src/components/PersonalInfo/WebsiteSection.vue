<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="website"
		:placeholder="t('settings', 'Your website')"
		autocomplete="url"
		type="url"
		:on-validate="onValidate" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'
import { validateUrl } from '../../utils/validate.js'

const { website } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'WebsiteSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			website: { ...website, readable: NAME_READABLE_ENUM[website.name] },
		}
	},

	methods: {
		onValidate(value) {
			return validateUrl(value)
		},
	},
}
</script>
