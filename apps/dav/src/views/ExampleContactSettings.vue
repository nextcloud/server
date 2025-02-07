<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection id="exmaple-content"
		:name="$t('dav', 'Example Content')"
		class="example-content-setting"
		:description="$t('dav', 'Set example content to be created on new user first login.')">
		<div class="example-content-setting__contacts">
			<input id="enable-default-contact"
				v-model="enableDefaultContact"
				type="checkbox"
				class="checkbox"
				@change="updateEnableDefaultContact">
			<label for="enable-default-contact"> {{ $t('dav',"Default contact is added to the user's own address book on user's first login.") }} </label>
			<div v-if="enableDefaultContact" class="example-content-setting__contacts__buttons">
				<NcButton type="primary"
					class="example-content-setting__contacts__buttons__button"
					@click="toggleModal">
					<template #icon>
						<IconUpload :size="20" />
					</template>
					{{ $t('dav', 'Import contact') }}
				</NcButton>
				<NcButton type="secondary"
					class="example-content-setting__contacts__buttons__button"
					@click="resetContact">
					<template #icon>
						<IconRestore :size="20" />
					</template>
					{{ $t('dav', 'Reset to default contact') }}
				</NcButton>
			</div>
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
	</NcSettingsSection>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { NcDialog, NcButton, NcSettingsSection } from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import IconUpload from 'vue-material-design-icons/Upload.vue'
import IconRestore from 'vue-material-design-icons/Restore.vue'
import IconCancel from '@mdi/svg/svg/cancel.svg?raw'
import IconCheck from '@mdi/svg/svg/check.svg?raw'

const enableDefaultContact = loadState('dav', 'enableDefaultContact') === 'yes'

export default {
	name: 'ExampleContactSettings',
	components: {
		NcDialog,
		NcButton,
		NcSettingsSection,
		IconUpload,
		IconRestore,
	},
	data() {
		return {
			enableDefaultContact,
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
	methods: {
		updateEnableDefaultContact() {
			axios.put(generateUrl('apps/dav/api/defaultcontact/config'), {
				allow: this.enableDefaultContact ? 'yes' : 'no',
			}).catch(() => {
				this.enableDefaultContact = !this.enableDefaultContact
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
					showSuccess(this.$t('dav', 'Contact reset successfully'))
				})
				.catch((error) => {
					console.error('Error importing contact:', error)
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
					showSuccess(this.$t('dav', 'Contact imported successfully'))
				} catch (error) {
					console.error('Error importing contact:', error)
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
.example-content-setting{
	&__contacts{
		&__buttons{
			margin-top: 1rem;
			display: flex;
			&__button{
				margin-inline-end: 5px;
			}
		}
	}
}
</style>
