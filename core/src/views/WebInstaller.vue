<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import type { DbType, SetupConfig, SetupLinks } from '../types/install.d.ts'

import { mdiArrowRight } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import DomPurify from 'dompurify'
import { computed, onMounted, reactive, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'

enum PasswordStrength {
	VeryWeak,
	Weak,
	Moderate,
	Strong,
	VeryStrong,
	ExtremelyStrong,
}

/**
 * Estimate the strength of a password from its entropy.
 *
 * @param password - The password
 */
function checkPasswordEntropy(password: string = ''): PasswordStrength {
	const uniqueCharacters = new Set(password)
	const entropy = parseInt(Math.log2(Math.pow(uniqueCharacters.size, password.length)).toFixed(2))
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

const config = reactive(loadState<SetupConfig>('core', 'config'))
const links = loadState<SetupLinks>('core', 'links')

const form = useTemplateRef('form')
const isValidAutoconfig = ref(false)
const loading = ref(false)

const passwordStrength = computed(() => checkPasswordEntropy(config.adminpass))

const passwordHelperText = computed(() => {
	if (config.adminpass === '') {
		return ''
	}

	switch (passwordStrength.value) {
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
})

const passwordHelperType = computed(() => {
	if (passwordStrength.value < PasswordStrength.Moderate) {
		return 'error'
	}
	if (passwordStrength.value < PasswordStrength.Strong) {
		return 'warning'
	}
	return 'success'
})

/**
 * Only MySQL/MariaDB and PostgreSQL can be configured to use an encrypted
 * connection through the installer, see OC\Setup\AbstractDatabase.
 */
const supportsEncryptedConnection = computed(() => config.dbtype === 'mysql' || config.dbtype === 'pgsql')

/**
 * The form is submitted natively, so the checkbox needs a `name` to be part of
 * the request - which NcCheckboxRadioSwitch only supports for groups of
 * checkboxes, meaning the model has to be the list of the checked values.
 * The value is submitted as a string and reflected back on validation errors.
 */
const dbsslnoverify = computed({
	get: () => config.dbsslnoverify ? ['1'] : [],
	set: (checked: string[]) => {
		config.dbsslnoverify = checked.includes('1')
	},
})

const firstAndOnlyDatabase = computed(() => {
	const dbNames = Object.values(config.databases || {})
	return dbNames.length === 1 ? dbNames[0] : null
})

// More than 3 databases are listed vertically
const DBTypeGroupDirection = computed(() => Object.keys(config.databases || {}).length > 3 ? 'vertical' : 'horizontal')

const htaccessWarning = computed(() => {
	// The message is rendered with v-html
	const message = [
		t('core', 'Your data directory and files are probably accessible from the internet because the <code>.htaccess</code> file does not work.'),
		t('core', 'For information how to properly configure your server, please {linkStart}see the documentation{linkEnd}', {
			linkStart: '<a href="' + links.adminInstall + '" target="_blank" rel="noreferrer noopener">',
			linkEnd: '</a>',
		}, { escape: false }),
	].join('<br>')
	return DomPurify.sanitize(message)
})

const errors = computed(() => (config.errors || []).map((error) => {
	if (typeof error === 'string') {
		return { heading: '', message: error }
	}

	// Without a hint there is nothing to show below a heading
	if (error.hint === '') {
		return { heading: '', message: error.error }
	}

	return { heading: error.error, message: error.hint }
}))

onMounted(() => {
	if (config.dbtype === '') {
		config.dbtype = Object.keys(config.databases).at(0) as DbType
	}

	// An autoconfig is only valid if it fills in everything but the administration account
	if (config.hasAutoconfig && form.value) {
		const adminFields = form.value.querySelectorAll('input[name="adminlogin"], input[name="adminpass"]')
		adminFields.forEach((input) => input.removeAttribute('required'))
		isValidAutoconfig.value = form.value.checkValidity() && config.errors.length === 0
		adminFields.forEach((input) => input.setAttribute('required', 'true'))
	}
})
</script>

<template>
	<form
		ref="form"
		:class="$style.setupForm"
		action=""
		data-cy-setup-form
		method="POST"
		@submit="loading = true">
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
		<fieldset>
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
		<details v-show="!isValidAutoconfig" open data-cy-setup-form-advanced-config>
			<summary>{{ t('core', 'Storage & database') }}</summary>

			<!-- Data folder -->
			<fieldset>
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
			<fieldset>
				<legend>{{ t('core', 'Database configuration') }}</legend>

				<!-- Database type select -->
				<fieldset>
					<legend class="hidden-visually">
						{{ t('core', 'Database type') }}
					</legend>

					<!-- Using v-show instead of v-if ensures that the input dbtype remains set even when only one database engine is available -->
					<p v-show="!firstAndOnlyDatabase" :class="[$style.setupForm__databaseTypeSelect, { [$style.setupForm__databaseTypeSelect_vertical]: DBTypeGroupDirection === 'vertical' }]">
						<NcCheckboxRadioSwitch
							v-for="(name, db) in config.databases"
							:key="db"
							v-model="config.dbtype"
							buttonVariant
							:data-cy-setup-form-field="`dbtype-${db}`"
							:value="db"
							:buttonVariantGrouped="DBTypeGroupDirection"
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
						:helperText="t('core', 'Please specify the port number along with the host name (e.g., localhost:5432).')"
						:label="t('core', 'Database host')"
						:placeholder="t('core', 'localhost')"
						autocapitalize="none"
						autocomplete="off"
						data-cy-setup-form-field="dbhost"
						name="dbhost"
						spellcheck="false" />
				</fieldset>

				<!-- Encrypted database connection -->
				<details v-if="supportsEncryptedConnection" data-cy-setup-form-database-encryption>
					<summary>{{ t('core', 'Encrypted database connection') }}</summary>

					<fieldset>
						<legend class="hidden-visually">
							{{ t('core', 'Encrypted database connection') }}
						</legend>

						<NcTextField
							v-if="config.dbtype === 'pgsql'"
							v-model="config.dbsslmode"
							:helperText="t('core', 'Supported modes: disable, allow, prefer, require, verify-ca, verify-full.')"
							:label="t('core', 'Encryption mode')"
							autocapitalize="none"
							autocomplete="off"
							name="dbsslmode"
							spellcheck="false" />

						<NcTextField
							v-model="config.dbsslca"
							:helperText="t('core', 'Has to be readable by the web server.')"
							:label="t('core', 'CA certificate path')"
							autocapitalize="none"
							autocomplete="off"
							name="dbsslca"
							spellcheck="false" />

						<NcTextField
							v-model="config.dbsslcert"
							:label="t('core', 'Client certificate path')"
							autocapitalize="none"
							autocomplete="off"
							name="dbsslcert"
							spellcheck="false" />

						<NcTextField
							v-model="config.dbsslkey"
							:label="t('core', 'Client certificate key path')"
							autocapitalize="none"
							autocomplete="off"
							name="dbsslkey"
							spellcheck="false" />

						<NcTextField
							v-if="config.dbtype === 'pgsql'"
							v-model="config.dbsslcrl"
							:label="t('core', 'Certificate revocation list path')"
							autocapitalize="none"
							autocomplete="off"
							name="dbsslcrl"
							spellcheck="false" />

						<NcCheckboxRadioSwitch
							v-if="config.dbtype === 'mysql'"
							v-model="dbsslnoverify"
							name="dbsslnoverify"
							type="checkbox"
							value="1">
							{{ t('core', 'Do not verify that the server certificate matches the database host') }}
						</NcCheckboxRadioSwitch>
					</fieldset>
				</details>
			</fieldset>
		</details>

		<!-- Submit -->
		<NcButton
			:class="{ [$style.setupForm__button]: !loading }"
			:disabled="loading"
			wide
			alignment="center-reverse"
			data-cy-setup-form-submit
			type="submit"
			variant="primary">
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<NcIconSvgWrapper v-else :path="mdiArrowRight" />
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

<style module lang="scss">
.setupForm {
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
	> :global(.notecard):first-child {
		margin-top: 0;
	}

	> :global(.notecard):last-child {
		margin-bottom: 0;
	}

	fieldset,
	details {
		margin-block: 1rem;
	}

	code {
		background-color: var(--color-background-dark);
		margin-top: 1rem;
		padding: 0 0.3em;
		border-radius: var(--border-radius);
	}

	:global(.input-field) {
		margin-block-start: 1rem !important;
	}

	:global(.notecard__heading) {
		font-size: inherit !important;
	}
}

.setupForm__button {
	:global(.icon-vue) {
		transition: all linear var(--animation-quick);
	}

	&:hover :global(.icon-vue) {
		transform: translateX(0.2em);
	}
}

.setupForm__databaseTypeSelect {
	display: flex;
}

.setupForm__databaseTypeSelect_vertical {
	flex-direction: column;
}
</style>
