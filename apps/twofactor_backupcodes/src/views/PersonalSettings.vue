<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="backupcodes-settings">
		<NcButton v-if="!enabled"
			id="generate-backup-codes"
			:disabled="generatingCodes"
			@click="generateBackupCodes">
			<template #icon>
				<NcLoadingIcon v-if="generatingCodes" />
			</template>
			{{ t('twofactor_backupcodes', 'Generate backup codes') }}
		</NcButton>
		<template v-else>
			<p class="backupcodes-settings__codes">
				<template v-if="!haveCodes">
					{{ t('twofactor_backupcodes', 'Backup codes have been generated. {used} of {total} codes have been used.', {used, total}) }}
				</template>
				<template v-else>
					{{ t('twofactor_backupcodes', 'These are your backup codes. Please save and/or print them as you will not be able to read the codes again later.') }}
					<ul>
						<li v-for="code in codes"
							:key="code"
							class="backupcodes-settings__codes__code">
							{{ code }}
						</li>
					</ul>
				</template>
			</p>
			<p class="backupcodes-settings__actions">
				<template v-if="haveCodes">
					<NcButton :href="downloadUrl"
						:download="downloadFilename"
						type="primary">
						{{ t('twofactor_backupcodes', 'Save backup codes') }}
					</NcButton>
					<NcButton @click="printCodes">
						{{ t('twofactor_backupcodes', 'Print backup codes') }}
					</NcButton>
				</template>
				<NcButton id="generate-backup-codes"
					@click="generateBackupCodes">
					{{ t('twofactor_backupcodes', 'Regenerate backup codes') }}
				</NcButton>
			</p>
			<p>
				<em>
					{{ t('twofactor_backupcodes', 'If you regenerate backup codes, you automatically invalidate old codes.') }}
				</em>
			</p>
		</template>
	</div>
</template>

<script>
import { confirmPassword } from '@nextcloud/password-confirmation'
import { print } from '../service/PrintService.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import '@nextcloud/password-confirmation/dist/style.css'

export default {
	name: 'PersonalSettings',
	components: {
		NcButton,
		NcLoadingIcon,
	},
	data() {
		return {
			generatingCodes: false,
		}
	},
	computed: {
		downloadUrl() {
			if (!this.codes) {
				return ''
			}
			return 'data:text/plain,' + encodeURIComponent(this.codes.reduce((prev, code) => {
				return prev + code + '\r\n'
			}, ''))
		},
		downloadFilename() {
			const name = OC.theme.name || 'Nextcloud'
			return name + '-backup-codes.txt'
		},
		enabled() {
			return this.$store.state.enabled
		},
		total() {
			return this.$store.state.total
		},
		used() {
			return this.$store.state.used
		},
		codes() {
			return this.$store.state.codes
		},
		name() {
			return OC.theme.name || 'Nextcloud'
		},
		haveCodes() {
			return this.codes && this.codes.length > 0
		},
	},
	methods: {
		generateBackupCodes() {
			confirmPassword().then(() => {
				// Hide old codes
				this.generatingCodes = true

				this.$store.dispatch('generate').then(() => {
					this.generatingCodes = false
				}).catch(err => {
					OC.Notification.showTemporary(t('twofactor_backupcodes', 'An error occurred while generating your backup codes'))
					this.generatingCodes = false
					throw err
				})
			}).catch(console.error.bind(this))
		},

		getPrintData(codes) {
			if (!codes) {
				return ''
			}
			return codes.reduce((prev, code) => {
				return prev + code + '<br>'
			}, '')
		},

		printCodes() {
			print(this.getPrintData(this.codes))
		},
	},
}
</script>

<style lang="scss" scoped>
.backupcodes-settings {
	&__codes {
		&__code {
			font-family: monospace;
			letter-spacing: 0.02em;
			font-size: 1.2em;
		}
	}

	&__actions {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: var(--default-grid-baseline);
	}
}
</style>
