<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form
		ref="form"
		class="setup-form"
		:class="{ 'setup-form--loading': loading }"
		action=""
		data-cy-setup-form
		method="POST"
		@submit="onSubmit">
		<!-- Autoconfig info -->
		<NcNoteCard
			v-if="config.hasAutoconfig"
			:heading="t('core', 'Autoconfig file detected')"
			data-cy-setup-form-note="autoconfig"
			type="success">
			{{ t('core', 'The setup form below is pre-filled with the values from the config file.') }}
		</NcNoteCard>

		<!-- Htaccess warning -->
		<NcNoteCard
			v-if="config.htaccessWorking === false"
			:heading="t('core', 'Security warning')"
			data-cy-setup-form-note="htaccess"
			type="warning">
			<p v-html="htaccessWarning" />
		</NcNoteCard>

		<!-- Various errors -->
		<NcNoteCard
			v-for="(error, index) in errors"
			:key="index"
			:heading="error.heading"
			data-cy-setup-form-note="error"
			type="error">
			{{ error.message }}
		</NcNoteCard>

		<!-- Admin creation -->
		<fieldset class="setup-form__administration">
			<legend>{{ t('core', 'Create administration account') }}</legend>

			<!-- Username -->
			<NcTextField
				v-model="config.adminlogin"
				:label="t('core', 'Administration account name')"
				data-cy-setup-form-field="adminlogin"
				name="adminlogin"
				required />

			<!-- Password -->
			<NcPasswordField
				v-model="config.adminpass"
				:label="t('core', 'Administration account password')"
				data-cy-setup-form-field="adminpass"
				name="adminpass"
				required />

			<!-- Password entropy -->
			<NcNoteCard v-show="config.adminpass !== ''" :type="passwordHelperType">
				{{ passwordHelperText }}
			</NcNoteCard>
		</fieldset>

		<!-- Autoconfig toggle -->
		<details v-show="!isValidAutoconfig" data-cy-setup-form-advanced-config>
			<summary>{{ t('core', 'Storage & database') }}</summary>

			<!-- Data folder -->
			<fieldset class="setup-form__data-folder">
				<NcTextField
					v-model="config.directory"
					:label="t('core', 'Data folder')"
					:placeholder="config.serverRoot + '/data'"
					required
					autocomplete="off"
					autocapitalize="none"
					data-cy-setup-form-field="directory"
					name="directory"
					spellcheck="false" />
			</fieldset>

			<!-- Database -->
			<fieldset class="setup-form__database">
				<legend>{{ t('core', 'Database configuration') }}</legend>

				<!-- Database type select -->
				<fieldset class="setup-form__database-type">
					<legend class="hidden-visually">
						{{ t('core', 'Database type') }}
					</legend>

					<!-- Using v-show instead of v-if ensures that the input dbtype remains set even when only one database engine is available -->
					<p v-show="!firstAndOnlyDatabase" :class="`setup-form__database-type-select--${DBTypeGroupDirection}`" class="setup-form__database-type-select">
						<NcCheckboxRadioSwitch
							v-for="(name, db) in config.databases"
							:key="db"
							v-model="config.dbtype"
							:button-variant="true"
							:data-cy-setup-form-field="`dbtype-${db}`"
							:value="db"
							:button-variant-grouped="DBTypeGroupDirection"
							name="dbtype"
							type="radio">
							{{ name }}
						</NcCheckboxRadioSwitch>
					</p>

					<NcNoteCard v-if="firstAndOnlyDatabase" data-cy-setup-form-db-note="single-db" type="warning">
						{{ t('core', 'Only {firstAndOnlyDatabase} is available.', { firstAndOnlyDatabase }) }}<br>
						{{ t('core', 'Install and activate additional PHP modules to choose other database types.') }}<br>
						<a :href="links.adminSourceInstall" target="_blank" rel="noreferrer noopener">
							{{ t('core', 'For more details check out the documentation.') }} ↗
						</a>
					</NcNoteCard>

					<NcNoteCard
						v-if="config.dbtype === 'sqlite'"
						:heading="t('core', 'Performance warning')"
						data-cy-setup-form-db-note="sqlite"
						type="warning">
						{{ t('core', 'You chose SQLite as database.') }}<br>
						{{ t('core', 'SQLite should only be used for minimal and development instances. For production we recommend a different database backend.') }}<br>
						{{ t('core', 'If you use clients for file syncing, the use of SQLite is highly discouraged.') }}
					</NcNoteCard>
				</fieldset>

				<!-- Database configuration -->
				<fieldset v-if="config.dbtype !== 'sqlite'">
					<legend class="hidden-visually">
						{{ t('core', 'Database connection') }}
					</legend>

					<NcTextField
						v-model="config.dbuser"
						:label="t('core', 'Database user')"
						autocapitalize="none"
						autocomplete="off"
						data-cy-setup-form-field="dbuser"
						name="dbuser"
						spellcheck="false"
						required />

					<NcPasswordField
						v-model="config.dbpass"
						:label="t('core', 'Database password')"
						autocapitalize="none"
						autocomplete="off"
						data-cy-setup-form-field="dbpass"
						name="dbpass"
						spellcheck="false"
						required />

					<NcTextField
						v-model="config.dbname"
						:label="t('core', 'Database name')"
						autocapitalize="none"
						autocomplete="off"
						data-cy-setup-form-field="dbname"
						name="dbname"
						pattern="[0-9a-zA-Z\$_\-]+"
						spellcheck="false"
						required />

					<NcTextField
						v-if="config.dbtype === 'oci'"
						v-model="config.dbtablespace"
						:label="t('core', 'Database tablespace')"
						autocapitalize="none"
						autocomplete="off"
						data-cy-setup-form-field="dbtablespace"
						name="dbtablespace"
						spellcheck="false" />

					<NcTextField
						v-model="config.dbhost"
						:helper-text="t('core', 'Please specify the port number along with the host name (e.g., localhost:5432).')"
						:label="t('core', 'Database host')"
						:placeholder="t('core', 'localhost')"
						autocapitalize="none"
						autocomplete="off"
						data-cy-setup-form-field="dbhost"
						name="dbhost"
						spellcheck="false" />
				</fieldset>
			</fieldset>
		</details>

		<!-- Submit -->
		<NcButton
			class="setup-form__button"
			:class="{ 'setup-form__button--loading': loading }"
			:disabled="loading"
			:loading="loading"
			:wide="true"
			alignment="center-reverse"
			data-cy-setup-form-submit
			type="submit"
			variant="primary">
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<IconArrowRight v-else />
			</template>
			{{ loading ? t('core', 'Installing …') : t('core', 'Install') }}
		</NcButton>

		<!-- Help note -->
		<NcNoteCard data-cy-setup-form-note="help" type="info">
			{{ t('core', 'Need help?') }}
			<a target="_blank" rel="noreferrer noopener" :href="links.adminInstall">{{ t('core', 'See the documentation') }} ↗</a>
		</NcNoteCard>
	</form>
</template>

<script lang="ts">
import type { DbType, SetupConfig, SetupLinks } from '../install.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import DomPurify from 'dompurify'
import { defineComponent } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'

enum PasswordStrength {
	VeryWeak,
	Weak,
	Moderate,
	Strong,
	VeryStrong,
	ExtremelyStrong,
}

/**
 *
 * @param password
 */
function checkPasswordEntropy(password: string = ''): PasswordStrength {
	const uniqueCharacters = new Set(password)
	const entropy = parseInt(Math.log2(Math.pow(parseInt(uniqueCharacters.size.toString()), password.length)).toFixed(2))
	if (entropy < 16) {
		return PasswordStrength.VeryWeak
	} else if (entropy < 31) {
		return PasswordStrength.Weak
	} else if (entropy < 46) {
		return PasswordStrength.Moderate
	} else if (entropy < 61) {
		return PasswordStrength.Strong
	} else if (entropy < 76) {
		return PasswordStrength.VeryStrong
	}

	return PasswordStrength.ExtremelyStrong
}

export default defineComponent({
	name: 'Setup',

	components: {
		IconArrowRight,
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcNoteCard,
		NcPasswordField,
		NcTextField,
	},

	setup() {
		return {
			t,
		}
	},

	data() {
		return {
			config: {} as SetupConfig,
			links: {} as SetupLinks,
			isValidAutoconfig: false,
			loading: false,
		}
	},

	computed: {
		passwordHelperText(): string {
			if (this.config?.adminpass === '') {
				return ''
			}

			const passwordStrength = checkPasswordEntropy(this.config?.adminpass)
			switch (passwordStrength) {
				case PasswordStrength.VeryWeak:
					return t('core', 'Password is too weak')
				case PasswordStrength.Weak:
					return t('core', 'Password is weak')
				case PasswordStrength.Moderate:
					return t('core', 'Password is average')
				case PasswordStrength.Strong:
					return t('core', 'Password is strong')
				case PasswordStrength.VeryStrong:
					return t('core', 'Password is very strong')
				case PasswordStrength.ExtremelyStrong:
					return t('core', 'Password is extremely strong')
			}

			return t('core', 'Unknown password strength')
		},

		passwordHelperType() {
			if (checkPasswordEntropy(this.config?.adminpass) < PasswordStrength.Moderate) {
				return 'error'
			}
			if (checkPasswordEntropy(this.config?.adminpass) < PasswordStrength.Strong) {
				return 'warning'
			}
			return 'success'
		},

		firstAndOnlyDatabase(): string | null {
			const dbNames = Object.values(this.config?.databases || {})
			if (dbNames.length === 1) {
				return dbNames[0]
			}

			return null
		},

		DBTypeGroupDirection() {
			const databases = Object.keys(this.config?.databases || {})
			// If we have more than 3 databases, we want to display them vertically
			if (databases.length > 3) {
				return 'vertical'
			}
			return 'horizontal'
		},

		htaccessWarning(): string {
			// We use v-html, let's make sure we're safe
			const message = [
				t('core', 'Your data directory and files are probably accessible from the internet because the <code>.htaccess</code> file does not work.'),
				t('core', 'For information how to properly configure your server, please {linkStart}see the documentation{linkEnd}', {
					linkStart: '<a href="' + this.links.adminInstall + '" target="_blank" rel="noreferrer noopener">',
					linkEnd: '</a>',
				}, { escape: false }),
			].join('<br>')
			return DomPurify.sanitize(message)
		},

		errors() {
			return (this.config?.errors || []).map((error) => {
				if (typeof error === 'string') {
					return {
						heading: '',
						message: error,
					}
				}

				// f no hint is set, we don't want to show a heading
				if (error.hint === '') {
					return {
						heading: '',
						message: error.error,
					}
				}

				return {
					heading: error.error,
					message: error.hint,
				}
			})
		},
	},

	beforeMount() {
		// Needs to only read the state once we're mounted
		// for Cypress to be properly initialized.
		this.config = loadState<SetupConfig>('core', 'config')
		this.links = loadState<SetupLinks>('core', 'links')
	},

	mounted() {
		// Set the first database type as default if none is set
		if (this.config.dbtype === '') {
			this.config.dbtype = Object.keys(this.config.databases).at(0) as DbType
		}

		// Validate the legitimacy of the autoconfig
		if (this.config.hasAutoconfig) {
			const form = this.$refs.form as HTMLFormElement

			// Check the form without the administration account fields
			form.querySelectorAll('input[name="adminlogin"], input[name="adminpass"]').forEach((input) => {
				input.removeAttribute('required')
			})

			if (form.checkValidity() && this.config.errors.length === 0) {
				this.isValidAutoconfig = true
			} else {
				this.isValidAutoconfig = false
			}

			// Restore the required attribute
			// Check the form without the administration account fields
			form.querySelectorAll('input[name="adminlogin"], input[name="adminpass"]').forEach((input) => {
				input.setAttribute('required', 'true')
			})
		}
	},

	methods: {
		async onSubmit() {
			this.loading = true
		},
	},
})
</script>

<style lang="scss">
form {
	padding: calc(3 * var(--default-grid-baseline));
	color: var(--color-main-text);
	border-radius: var(--border-radius-container);
	background-color: var(--color-main-background-blur);
	box-shadow: 0 0 10px var(--color-box-shadow);
	-webkit-backdrop-filter: var(--filter-background-blur);
	backdrop-filter: var(--filter-background-blur);

	max-width: 300px;
	margin-bottom: 30px;

	> fieldset:first-child,
	> .notecard:first-child {
		margin-top: 0;
	}

	> .notecard:last-child {
		margin-bottom: 0;
	}

	fieldset,
	details {
		margin-block: 1rem;
	}

	.setup-form__button:not(.setup-form__button--loading) {
		.material-design-icon {
			transition: all linear var(--animation-quick);
		}

		&:hover .material-design-icon {
			transform: translateX(0.2em);
		}
	}

	// Db select required styling
	.setup-form__database-type-select {
		display: flex;
		&--vertical {
			flex-direction: column;
		}
	}

}

code {
	background-color: var(--color-background-dark);
	margin-top: 1rem;
	padding: 0 0.3em;
	border-radius: var(--border-radius);
}

// Various overrides
.input-field {
	margin-block-start: 1rem !important;
}

.notecard__heading {
	font-size: inherit !important;
}
</style>
