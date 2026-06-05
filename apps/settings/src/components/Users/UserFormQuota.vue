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
import type { FormData, QuotaOption } from './userFormUtils.ts'

import { translate as t } from '@nextcloud/l10n'
import { inject } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { validateQuota as validateQuotaOption } from './userFormUtils.ts'

const props = defineProps<{
	/** Quota preset options; the first entry is the fallback for invalid input */
	quotaOptions: QuotaOption[]
}>()

/** Shared, reactive form state provided by the parent dialog */
const formData = inject<FormData>('formData')!

/**
 * Wraps the pure validator so NcSelect's create-option callback receives the
 * preset fallback (first option) for unparseable quota strings.
 *
 * @param quota Raw quota string entered by the user
 */
function validateQuota(quota: string) {
	return validateQuotaOption(quota, props.quotaOptions[0])
}
</script>
