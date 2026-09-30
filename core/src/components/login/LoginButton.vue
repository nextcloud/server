<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiArrowRight } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

withDefaults(defineProps<{
	loading: boolean
	value?: string
	valueLoading?: string
}>(), {
	value: () => t('core', 'Log in'),
	valueLoading: () => t('core', 'Logging in …'),
})
</script>

<template>
	<NcButton
		:class="$style.loginButton"
		variant="primary"
		type="submit"
		wide
		:disabled="loading">
		{{ !loading ? value : valueLoading }}
		<template #icon>
			<NcLoadingIcon v-if="loading" />
			<NcIconSvgWrapper v-else :path="mdiArrowRight" />
		</template>
	</NcButton>
</template>

<style module>
.loginButton {
	margin-top: .5rem;
}
</style>
