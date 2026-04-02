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
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { validateQuota } from './userFormUtils.ts'

export default {
	name: 'UserFormQuota',

	components: {
		NcSelect,
	},

	inject: ['formData'],

	props: {
		quotaOptions: {
			type: Array,
			required: true,
		},
	},

	methods: {
		validateQuota(quota) {
			return validateQuota(quota, this.quotaOptions[0])
		},
	},
}
</script>
