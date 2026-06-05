<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form__item">
		<NcSelect
			v-model="formData.quota"
			class="user-form__select"
			:inputLabel="t('settings', 'Quota')"
			:placeholder="t('settings', 'Set account quota')"
			:options="quotaOptions"
			:clearable="false"
			:taggable="true"
			:createOption="validateQuota" />
	</div>
</template>

<script setup lang="ts">
import type { QuotaOption } from './userFormUtils.ts'

import { translate as t } from '@nextcloud/l10n'
import { inject } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { formDataKey } from './injectionKeys.ts'
import { validateQuota as validateQuotaOption } from './userFormUtils.ts'

const props = defineProps<{
	quotaOptions: QuotaOption[]
}>()

const formData = inject(formDataKey)!

/**
 * Validate a typed quota, falling back to the first preset when unparseable.
 *
 * @param quota The raw quota string entered in the select
 */
function validateQuota(quota: string) {
	return validateQuotaOption(quota, props.quotaOptions[0])
}
</script>
