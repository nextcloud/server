<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcButton type="primary"
		native-type="submit"
		:wide="true"
		:disabled="loading"
		@click="$emit('click')">
		{{ !loading ? value : valueLoading }}
		<template #icon>
			<div v-if="loading" class="submit-wrapper__icon icon-loading-small-dark" />
			<ArrowRight v-else class="submit-wrapper__icon" />
		</template>
	</NcButton>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'

export default {
	name: 'LoginButton',
	components: {
		ArrowRight,
		NcButton,
	},
	props: {
		value: {
			type: String,
			default: t('core', 'Log in'),
		},
		valueLoading: {
			type: String,
			default: t('core', 'Logging in …'),
		},
		loading: {
			type: Boolean,
			required: true,
		},
		invertedColors: {
			type: Boolean,
			default: false,
		},
	},
}
</script>

<style lang="scss" scoped>
.button-vue {
	margin-top: .5rem;
}
</style>
