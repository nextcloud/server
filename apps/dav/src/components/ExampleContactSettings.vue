<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="example-contact-settings">
		<NcCheckboxRadioSwitch :checked="enableDefaultContact"
			type="switch"
			@update:model-value="updateEnableDefaultContact">
			{{ $t('dav', "Add example contact to user's address book when they first log in") }}
		</NcCheckboxRadioSwitch>
		<div v-if="enableDefaultContact" class="example-contact-settings__buttons">
			<ExampleContentDownloadButton :href="downloadUrl">
				<template #icon>
					<IconAccount :size="20" />
				</template>
				example_contact.vcf
			</ExampleContentDownloadButton>
			<NcButton type="secondary"
				@click="toggleModal">
				<template #icon>
					<IconUpload :size="20" />
				</template>
				{{ $t('dav', 'Import contact') }}
			</NcButton>
			<NcButton v-if="hasCustomDefaultContact"
				type="tertiary"
				@click="resetContact">
				<template #icon>
					<IconRestore :size="20" />
				</template>
				{{ $t('dav', 'Reset to default') }}
			</NcButton>
		</div>
		<NcDialog :open.sync="isModalOpen"
			:name="$t('dav', 'Import contacts')"
			:buttons="buttons">
			<div>
				<p>{{ $t('dav', 'Importing a new .vcf file will delete the existing default contact and replace it with the new one. Do you want to continue?') }}</p>
			</div>
		</NcDialog>
		<input id="example-contact-import"
			ref="exampleContactImportInput"
			:disabled="loading"
			type="file"
			accept=".vcf"
			class="hidden-visually"
			@change="processFile">
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { NcDialog, NcButton, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import IconUpload from 'vue-material-design-icons/Upload.vue'
import IconRestore from 'vue-material-design-icons/Restore.vue'
import IconAccount from 'vue-material-design-icons/Account.vue'
import IconCancel from '@mdi/svg/svg/cancel.svg?raw'
import IconCheck from '@mdi/svg/svg/check.svg?raw'
import logger from '../service/logger.js'
import ExampleContentDownloadButton from './ExampleContentDownloadButton.vue'

const enableDefaultContact = loadState('dav', 'enableDefaultContact')
const hasCustomDefaultContact = loadState('dav', 'hasCustomDefaultContact')

export default {
	name: 'ExampleContactSettings',
	components: {
		NcDialog,
		NcButton,
		NcCheckboxRadioSwitch,
		IconUpload,
		IconRestore,
		IconAccount,
		ExampleContentDownloadButton,
	},
	data() {
		return {
			enableDefaultContact,
			hasCustomDefaultContact,
			isModalOpen: false,
			loading: false,
			buttons: [
				{
					label: this.$t('dav', 'Cancel'),
					icon: IconCancel,
					callback: () => { this.isModalOpen = false },
				},
				{
					label: this.$t('dav', 'Import'),
					type: 'primary',
					icon: IconCheck,
					callback: () => { this.clickImportInput() },
				},
			],
		}
	},
	computed: {
		downloadUrl() {
			return generateUrl('/apps/dav/api/defaultcontact/contact')
		},
	},
	methods: {
		updateEnableDefaultContact() {
			axios.put(generateUrl('apps/dav/api/defaultcontact/config'), {
				allow: !this.enableDefaultContact,
			}).then(() => {
				this.enableDefaultContact = !this.enableDefaultContact
			}).catch(() => {
				showError(this.$t('dav', 'Error while saving settings'))
			})
		},
		toggleModal() {
			this.isModalOpen = !this.isModalOpen
		},
		clickImportInput() {
			this.$refs.exampleContactImportInput.click()
		},
		resetContact() {
			this.loading = true
			axios.put(generateUrl('/apps/dav/api/defaultcontact/contact'))
				.then(() => {
					this.hasCustomDefaultContact = false
					showSuccess(this.$t('dav', 'Contact reset successfully'))
				})
				.catch((error) => {
					logger.error('Error importing contact:', { error })
					showError(this.$t('dav', 'Error while resetting contact'))
				})
				.finally(() => {
					this.loading = false
				})
		},
		processFile(event) {
			this.loading = true

			const file = event.target.files[0]
			const reader = new FileReader()

			reader.onload = async () => {
				this.isModalOpen = false
				try {
					await axios.put(generateUrl('/apps/dav/api/defaultcontact/contact'), { contactData: reader.result })
					this.hasCustomDefaultContact = true
					showSuccess(this.$t('dav', 'Contact imported successfully'))
				} catch (error) {
					logger.error('Error importing contact:', { error })
					showError(this.$t('dav', 'Error while importing contact'))
				} finally {
					this.loading = false
					event.target.value = ''
				}
			}
			reader.readAsText(file)
		},
	},
}
</script>
<style lang="scss" scoped>
.example-contact-settings {
	margin-block-start: 2rem;

	&__buttons {
		display: flex;
		gap: calc(var(--default-grid-baseline) * 2);
		margin-top: calc(var(--default-grid-baseline) * 2);
	}
}
</style>
