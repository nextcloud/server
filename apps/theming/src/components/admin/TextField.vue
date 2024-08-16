<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="field">
		<NcTextField :value.sync="localValue"
			:label="displayName"
			:placeholder="placeholder"
			:type="type"
			:maxlength="maxlength"
			:spellcheck="false"
			:success="showSuccess"
			:error="Boolean(errorMessage)"
			:helper-text="errorMessage"
			:show-trailing-button="value !== defaultValue"
			trailing-button-icon="undo"
			@trailing-button-click="undo"
			@keydown.enter="save"
			@blur="save" />
	</div>
</template>

<script>
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import TextValueMixin from '../../mixins/admin/TextValueMixin.js'

export default {
	name: 'TextField',

	components: {
		NcTextField,
	},

	mixins: [
		TextValueMixin,
	],

	props: {
		name: {
			type: String,
			required: true,
		},
		value: {
			type: String,
			required: true,
		},
		defaultValue: {
			type: String,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
		displayName: {
			type: String,
			required: true,
		},
		placeholder: {
			type: String,
			required: true,
		},
		maxlength: {
			type: Number,
			required: true,
		},
	},
}
</script>

<style lang="scss" scoped>
.field {
	max-width: 400px;
}
</style>
