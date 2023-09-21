<!--
	- @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @license AGPL-3.0-or-later
	-
	- This code is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License
	- as published by the Free Software Foundation,
	- either version 3 of the License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License, version 3,
	- along with this program.  If not, see <http://www.gnu.org/licenses/>
	-
-->

<template>
	<NcSettingsSection :name="t('settings', 'CORS allowed domains')"
		:description="t('settings', 'Cross-origin resource sharing (CORS) allows restricted resources (API) to be accessed from another external domain. The enabled domains will be allowed to access the DAV resources and CORS enabled API routes.')"
		:doc-url="corsSettingsAdminDoc">
		<NcCheckboxRadioSwitch :checked.sync="userCorsDomainsEnabled"
			type="switch"
			@update:checked="updateUserCorsDomains">
			{{ t('settings', 'Allow users to define a custom list of CORS enabled domains for their resources') }}
		</NcCheckboxRadioSwitch>
		<section>
			<h3>{{ t('settings', 'CORS enabled external domains') }}</h3>
			<ul class="cors-domain__list">
				<li v-for="domain in allowedCorsDomains" :key="domain" class="cors-domain">
					<IconDomain :size="20" />
					<span class="cors-domain__text">{{ domain }}</span>
					<NcButton class="cors-domain__delete" type="tertiary" @click="removeCorsDomain(domain)">
						<template #icon>
							<IconTrashCan :size="20" />
						</template>
						Delete
					</NcButton>
				</li>
			</ul>
		</section>
		<div class="cors-domain-input__wrapper">
			<NcTextField :error="inputError"
				:helper-text="inputErrorText"
				:label="t('settings', 'New CORS enabled domain')"
				:show-trailing-button="inputValue !== ''"
				:trailing-button-label="t('settings', 'Add CORS domain')"
				:value.sync="inputValue"
				class="cors-domain-input"
				placeholder="http://some.example.com"
				@keydown="onKeydownDomain"
				@trailing-button-click="inputValue = ''">
				<IconDomainPlus :size="20" />
			</NcTextField>
			<NcButton class="cors-domain-input__submit" @click="addCorsDomain(inputValue)">
				<template #icon>
					<IconCheck :size="20" />
				</template>
				{{ t('setting', 'Add domain') }}
			</NcButton>
		</div>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconDomain from 'vue-material-design-icons/Domain.vue'
import IconDomainPlus from 'vue-material-design-icons/DomainPlus.vue'
import IconTrashCan from 'vue-material-design-icons/TrashCan.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { showError } from '@nextcloud/dialogs'
import { logger } from '../utils/logger.ts'

export default {
	name: 'CORS',
	components: {
		IconCheck,
		IconDomain,
		IconDomainPlus,
		IconTrashCan,
		NcButton,
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		NcTextField,
	},
	data() {
		return {
			allowedCorsDomains: loadState('settings', 'cors-allowed-domains', []),
			userCorsDomainsEnabled: loadState('settings', 'cors-allow-user-domains', false),
			corsSettingsAdminDoc: loadState('settings', 'cors-settings-admin-docs', ''),
			inputValue: '',
			inputError: false,
			inputErrorText: '',
		}
	},
	watch: {
		/**
		 * Ensure errors are cleared on empty input or if the input is valid again
		 */
		inputValue() {
			if (this.inputValue === '' || (this.inputError && this.validateCorsDomain(this.inputValue))) {
				this.inputError = false
				this.inputErrorText = ''
			}
		},
	},
	methods: {
		/**
		 * Add a new trusted CORS domain
		 * @param {string} newDomain New domain to add as CORS enabled domain
		 */
		addCorsDomain(newDomain) {
			const domain = this.validateCorsDomain(newDomain)
			if (domain !== false) {
				const backup = [...this.allowedCorsDomains]
				this.allowedCorsDomains = [...this.allowedCorsDomains, domain]

				this.update('domains', this.allowedCorsDomains).then(() => {
					this.inputValue = ''
				}).catch(() => {
					this.allowedCorsDomains = backup
				})
			}
		},

		/**
		 * Remove a domain from the list of CORS enabled domains
		 * @param {string} domain Domain to remove from allowed domains
		 */
		removeCorsDomain(domain) {
			const backup = [...this.allowedCorsDomains]
			this.allowedCorsDomains = [...this.allowedCorsDomains.filter((entry) => entry !== domain)]
			this.update('domains', this.allowedCorsDomains).catch(() => {
				this.allowedCorsDomains = backup
			})
		},

		/**
		 * Handle Enter press on the CORS domain input field
		 * @param {KeyboardEvent} event The keyboard event
		 */
		 onKeydownDomain(event) {
			if (event.key === 'Enter') {
				this.addCorsDomain(this.inputValue)
			}
		},

		/**
		 * Save user defined CORS domain
		 * @param {boolean} checked Whether user defined lists are enabled
		 */
		updateUserCorsDomains(checked) {
			const backup = this.userCorsDomainsEnabled
			this.update('allowusers', checked).catch(() => {
				this.userCorsDomainsEnabled = backup
			})
		},

		/**
		 * Validate if a given string is a valid domain for the CORS headers
		 * @param {string} domain A URL to validate
		 * @return {string|false} Either the validated domain or false if invalid
		 */
		 validateCorsDomain(domain) {
			try {
				const url = new URL(domain)
				if (url.hash !== '' || url.search !== '' || (url.pathname !== '' && url.pathname !== '/')) {
					this.inputError = true
					this.inputErrorText = t('settings', 'The domain must not contain any additional path or query parameters.')
				} else if (url.password !== '' || url.username !== '') {
					this.inputError = true
					this.inputErrorText = t('settings', 'The domain must not contain user and / or password.')
				} else if (url.origin === window.location.origin) {
					this.inputError = true
					this.inputErrorText = t('settings', 'The domain must not be the same like the current domain.')
				} else if (this.allowedCorsDomains.includes(url.origin)) {
					this.inputError = true
					this.inputErrorText = t('settings', 'The domain is already included.')
				} else {
					return url.origin
				}
			} catch (e) {
				this.inputError = true
				this.inputErrorText = t('settings', 'Invalid entered domain is not valid, please include protocol and hostname.')
				logger.debug('Invalid URL passed as CORS domain', { error: e })
			}
			return false
		},

		async update(key, value) {
			await confirmPassword()

			const url = generateUrl('/settings/api/admin/cors/{key}', {
				key,
			})

			let reject = false
			try {
				const { status } = await axios.put(url, {
					value,
				})
				if (status !== 200) {
					showError(errorMessage)
					logger.error(errorMessage, { error })
					reject = true
				}
			} catch (error) {
				const errorMessage = t('settings', 'Unable to update CORS config')
				showError(errorMessage)
				logger.error(errorMessage, { error })
			}

			if (reject) {
				throw new Error()
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.cors-domain-input {
	margin-inline-start: 12px;

	&__wrapper {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	&__submit {
		flex-shrink: 0;
	}
}

.cors-domain {
	display: flex;
	align-items: center;
	border-radius: var(--border-radius-large);
	padding: 4px;
	padding-inline-start: 12px;

	&:hover {
		background-color: var(--color-background-hover);
	}

	&__list {
		margin-block: 6px 12px;
		margin-inline-start: 12px;
	}

	&__text {
		font-family: monospace;
		padding-inline: 24px 12px;
	}
	&__delete {
		margin-inline-start: auto;
	}
}
</style>
