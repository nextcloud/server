<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal>
		<div class="template-field-modal__content">
			<form @submit.prevent.stop="onSubmit">
				<h3>{{ t('files', 'Fill template fields') }}</h3>

				<!-- break these out into template field components -->
			</form>
		</div>

		<div class="template-field-modal__buttons">
			<NcButton aria-label="Submit button"
				type="primary">
				{{ t('files', 'Submit') }}
			</NcButton>
		</div>
	</NcModal>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { NcModal, NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default defineComponent({
	name: 'TemplateFiller',

	components: {
		NcModal,
		NcButton,
	},

	props: {
		fields: {
			type: Array,
			default: () => [],
		},
	},

	data() {
		return {
			someText: '',
		}
	},

	methods: {
		t,
		onSubmit() {},
	},
})
</script>

<style lang="scss" scoped>
$modal-margin: calc(var(--default-grid-baseline) * 6);

.template-field-modal__content {
	padding: $modal-margin;

	& h3 {
		text-align: center;
	}
}

.template-field-modal__field {
	margin: calc(var(--default-grid-baseline) * 4) 0;
}

.template-field-modal__buttons {
	display: flex;
	justify-content: flex-end;
	margin: $modal-margin;
	margin-top: 0;
}
</style>
