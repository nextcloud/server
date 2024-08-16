<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form class="version-label-modal"
		@submit.prevent="setVersionLabel(innerVersionLabel)">
		<label>
			<div class="version-label-modal__title">{{ t('files_versions', 'Version name') }}</div>
			<NcTextField ref="labelInput"
				:value.sync="innerVersionLabel"
				:placeholder="t('files_versions', 'Version name')"
				:label-outside="true" />
		</label>

		<div class="version-label-modal__info">
			{{ t('files_versions', 'Named versions are persisted, and excluded from automatic cleanups when your storage quota is full.') }}
		</div>

		<div class="version-label-modal__actions">
			<NcButton :disabled="innerVersionLabel.trim().length === 0" @click="setVersionLabel('')">
				{{ t('files_versions', 'Remove version name') }}
			</NcButton>
			<NcButton type="primary" native-type="submit">
				<template #icon>
					<Check />
				</template>
				{{ t('files_versions', 'Save version name') }}
			</NcButton>
		</div>
	</form>
</template>

<script lang="ts">
import Check from 'vue-material-design-icons/Check.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import { translate } from '@nextcloud/l10n'

import { defineComponent } from 'vue'

export default defineComponent({
	name: 'VersionLabelForm',
	components: {
		NcButton,
		NcTextField,
		Check,
	},
	props: {
		versionLabel: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			innerVersionLabel: this.versionLabel,
		}
	},
	mounted() {
		this.$nextTick(() => {
			(this.$refs.labelInput as Vue).$el.getElementsByTagName('input')[0].focus()
		})
	},
	methods: {
		setVersionLabel(label: string) {
			this.$emit('label-update', label)
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
.version-label-modal {
	display: flex;
	justify-content: space-between;
	flex-direction: column;
	height: 250px;
	padding: 16px;

	&__title {
		margin-bottom: 12px;
		font-weight: 600;
	}

	&__info {
		margin-top: 12px;
		color: var(--color-text-maxcontrast);
	}

	&__actions {
		display: flex;
		justify-content: space-between;
		margin-top: 64px;
	}
}
</style>
