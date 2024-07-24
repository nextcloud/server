<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal>
		<div class="template-field-modal__content">
			<form>
				<h3>{{ t('files', 'Fill template fields') }}</h3>

				<!-- We will support more than just text fields in the future -->
				<div v-for="field in fields" :key="field.index">
					<TemplateTextField v-if="field.type == 'rich-text'"
						:field="field"
						@input="trackInput" />
				</div>
			</form>
		</div>

		<div class="template-field-modal__buttons">
			<NcLoadingIcon v-if="loading" :name="t('files', 'Submitting fields…')" />
			<NcButton aria-label="Submit button"
				type="primary"
				@click="submit">
				{{ t('files', 'Submit') }}
			</NcButton>
		</div>
	</NcModal>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { NcModal, NcButton, NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import TemplateTextField from './TemplateFiller/TemplateTextField.vue'

export default defineComponent({
	name: 'TemplateFiller',

	components: {
		NcModal,
		NcButton,
		NcLoadingIcon,
		TemplateTextField,
	},

	props: {
		fields: {
			type: Array,
			default: () => [],
		},
		onSubmit: {
			type: Function,
			default: async () => {},
		},
	},

	data() {
		return {
			localFields: {},
			loading: false,
		}
	},

	methods: {
		t,
		trackInput([value, index]) {
			this.localFields[index] = {
				content: value,
			}
		},
		async submit() {
			this.loading = true

			await this.onSubmit(this.localFields)

			this.$emit('close')
		},
	},
})
</script>

<style lang="scss" scoped>
$modal-margin: calc(var(--default-grid-baseline) * 4);

.template-field-modal__content {
	padding: $modal-margin;

	h3 {
		text-align: center;
	}
}

.template-field-modal__buttons {
	display: flex;
	justify-content: flex-end;
	gap: var(--default-grid-baseline);
	margin: $modal-margin;
	margin-top: 0;
}
</style>
