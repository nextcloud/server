<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form__item">
		<NcSelect
			v-model="formData.quota"
			class="user-form__select"
			:input-label="t('settings', 'Quota')"
			:placeholder="t('settings', 'Set account quota')"
			:options="quotaOptions"
			:clearable="false"
			:taggable="true"
			:create-option="validateQuota" />
	</div>
</template>

<script>
import { formatFileSize, parseFileSize } from '@nextcloud/files'
import NcSelect from '@nextcloud/vue/components/NcSelect'

export default {
	name: 'UserFormQuota',

	components: {
		NcSelect,
	},

	props: {
		formData: {
			type: Object,
			required: true,
		},
		quotaOptions: {
			type: Array,
			required: true,
		},
	},

	methods: {
		validateQuota(quota) {
			const parsed = parseFileSize(quota, true)
			if (parsed !== null && parsed >= 0) {
				const label = formatFileSize(parsed)
				return { id: label, label }
			}
			return this.quotaOptions[0]
		},
	},
}
</script>
