<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="previews-admin" data-cy-settings-previews>
		<NcNoteCard v-if="settings.configIsReadonly" type="warning">
			{{ t('settings', 'The config file is read-only. Preview settings cannot be saved from this page.') }}
		</NcNoteCard>

		<NcSettingsSection
			:name="t('settings', 'Previews')"
			:description="t('settings', 'Configure how Nextcloud generates and serves file previews used by Files, Photos, Memories, and other apps.')"
			:doc-url="documentationLink">
			<p>
				{{ t('settings', 'This page controls preview providers, limits, Imaginary, HTTP caching, and generation failures.') }}
				{{ t('settings', 'The Preview Generator app (if installed) only controls which sizes are pre-generated and when background generation runs.') }}
			</p>
			<NcButton
				type="primary"
				:disabled="saving || settings.configIsReadonly"
				data-cy="previews-save"
				@click="save">
				{{ t('settings', 'Save') }}
			</NcButton>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'General')"
			:description="t('settings', 'These limits apply to all preview generation, both on-demand and from the Preview Generator app.')">
			<NcCheckboxRadioSwitch
				v-model="settings.enablePreviews"
				type="switch"
				data-cy="previews-enable">
				{{ t('settings', 'Enable previews') }}
			</NcCheckboxRadioSwitch>
			<p class="previews-admin__hint">
				{{ t('settings', 'When disabled, Nextcloud will not generate or serve file previews.') }}
			</p>
			<div class="previews-admin__fields">
				<NcTextField
					v-model="maxX"
					type="number"
					data-cy="previews-max-x"
					:label="t('settings', 'Maximum preview width (pixels)')"
					:helper-text="t('settings', 'Longest side of the full-size preview. Leave empty to use the default (4096).')" />
				<NcTextField
					v-model="maxY"
					type="number"
					data-cy="previews-max-y"
					:label="t('settings', 'Maximum preview height (pixels)')"
					:helper-text="t('settings', 'Longest side of the full-size preview. Leave empty to use the default (4096).')" />
				<NcTextField
					v-model="maxMemory"
					type="number"
					:label="t('settings', 'Maximum memory (MB)')"
					:helper-text="t('settings', 'Skip imagegd previews that would allocate more than this. -1 means no memory cap. Default 256.')" />
				<NcTextField
					v-model="maxFilesize"
					type="number"
					:label="t('settings', 'Maximum source image filesize (MB)')"
					:helper-text="t('settings', 'Skip local image previews above this size. -1 means unlimited. Default 50.')" />
				<NcSelect
					v-model="previewFormatOption"
					:options="formatOptions"
					:clearable="false"
					:input-label="t('settings', 'Preview output format')" />
				<p class="previews-admin__hint">
					{{ t('settings', 'Used by Imaginary and other providers that can emit JPEG or WebP.') }}
				</p>
				<NcTextField
					v-model="jpegQuality"
					type="number"
					:label="t('settings', 'JPEG quality (1–100)')"
					:helper-text="t('settings', 'Higher values look better and use more disk. Default 80.')" />
				<NcTextField
					v-if="showWebpQuality"
					v-model="webpQuality"
					type="number"
					:label="t('settings', 'WebP quality (1–100)')"
					:helper-text="t('settings', 'Used when the output format is WebP. Default 80.')" />
			</div>

			<h3>{{ t('settings', 'Performance') }}</h3>
			<p class="previews-admin__hint">
				{{ concurrencyHint }}
			</p>
			<div class="previews-admin__fields">
				<NcTextField
					v-model="concurrencyNew"
					type="number"
					:label="t('settings', 'New preview concurrency')"
					:helper-text="t('settings', 'How many previews may be generated at once. Leave empty to use the CPU count (or 4). Do not set higher than the number of CPU cores.')" />
				<NcTextField
					v-model="concurrencyAll"
					type="number"
					:label="t('settings', 'Total preview concurrency')"
					:helper-text="t('settings', 'All preview requests, including cache hits. Leave empty to use twice the new-preview limit (or 8). Should be greater than or equal to new preview concurrency.')" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'Imaginary')"
			:description="t('settings', 'Use Imaginary to offload image processing. HEIC/HEIF often work best with Imaginary first and the native HEIC provider as fallback — set that under MIME priority.')">
			<div class="previews-admin__fields">
				<NcTextField
					v-model="settings.imaginaryUrl"
					:label="t('settings', 'Imaginary URL')"
					placeholder="http://imaginary:9000"
					:helper-text="t('settings', 'HTTP(S) URL of the Imaginary service. Required before the Imaginary providers can generate previews.')" />
				<NcPasswordField
					v-model="settings.imaginaryKey"
					:label="t('settings', 'Imaginary API key (optional)')" />
				<p class="previews-admin__hint">
					{{ t('settings', 'Sent as the key query parameter. Leave empty if Imaginary does not require a key.') }}
				</p>
			</div>
			<div class="previews-admin__row">
				<NcButton :disabled="testingImaginary" @click="testImaginary">
					{{ t('settings', 'Test connection') }}
				</NcButton>
				<span class="previews-admin__status" :data-status="imaginaryStatus">
					{{ imaginaryStatusLabel }}
				</span>
			</div>
			<p>
				{{ t('settings', 'Enabling Imaginary still requires OC\\Preview\\Imaginary in the provider list below.') }}
			</p>
		</NcSettingsSection>

		<NcSettingsSection
			v-if="showExternalTools"
			:name="t('settings', 'External tools')"
			:description="t('settings', 'Paths are shown because the matching binaries were detected or a related provider is enabled. Leave a path empty to search PATH.')">
			<div v-if="showFfmpeg" class="previews-admin__fields">
				<NcNoteCard :type="detection.ffmpegFound ? 'success' : 'warning'">
					{{ detection.ffmpegFound
						? t('settings', 'ffmpeg found: {path}', { path: detection.ffmpegDetectedPath })
						: t('settings', 'ffmpeg was not found. Movie previews will not be generated until it is installed or a path is set.') }}
				</NcNoteCard>
				<NcTextField
					v-model="settings.ffmpegPath"
					:label="t('settings', 'ffmpeg path')"
					:helper-text="t('settings', 'Custom path to ffmpeg for video previews. Empty uses the server PATH.')" />
				<NcTextField
					v-if="showFfmpeg"
					v-model="settings.ffprobePath"
					:label="t('settings', 'ffprobe path')"
					:helper-text="t('settings', 'Used for HDR video metadata. Empty uses the same directory as ffmpeg.')" />
			</div>
			<div v-if="showOffice" class="previews-admin__fields">
				<NcNoteCard :type="detection.officeFound ? 'success' : 'warning'">
					{{ detection.officeFound
						? t('settings', 'LibreOffice/OpenOffice found: {path}', { path: detection.officeDetectedPath })
						: t('settings', 'LibreOffice/OpenOffice was not found. Office document previews will not be generated until it is installed or a path is set.') }}
				</NcNoteCard>
				<NcTextField
					v-model="settings.libreofficePath"
					:label="t('settings', 'LibreOffice path')"
					:helper-text="t('settings', 'Custom path to LibreOffice or OpenOffice. Empty searches for libreoffice, then openoffice.')" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'Providers')"
			:description="t('settings', 'Enable providers and set their global priority. The order of enabled providers is the fallback order for all MIME types.')">
			<NcNoteCard v-if="detection.imagick" type="success">
				{{ t('settings', 'ImageMagick (Imagick) is available. HEIC, TIFF, PDF, and similar providers can run when their format is supported.') }}
			</NcNoteCard>
			<NcNoteCard v-else type="warning">
				{{ t('settings', 'ImageMagick (Imagick) is not available. Providers that require it are listed as unavailable until the PHP extension is installed.') }}
			</NcNoteCard>
			<div class="previews-admin__row">
				<NcButton :disabled="settings.configIsReadonly" @click="resetProviders">
					{{ t('settings', 'Reset to defaults') }}
				</NcButton>
			</div>
			<ul class="previews-admin__providers" data-cy="previews-providers">
				<li
					v-for="(provider, index) in settings.providers"
					:key="provider.class"
					class="previews-admin__provider"
					:class="{ 'previews-admin__provider--unavailable': provider.available === false }">
					<NcCheckboxRadioSwitch
						v-model="provider.enabled"
						type="switch">
						{{ provider.name }}
					</NcCheckboxRadioSwitch>
					<code class="previews-admin__class">{{ provider.class }}</code>
					<span class="previews-admin__mime">{{ provider.mime }}</span>
					<span
						class="previews-admin__availability"
						:data-available="provider.available === false ? 'false' : 'true'">
						{{ providerAvailabilityLabel(provider) }}
					</span>
					<div class="previews-admin__order">
						<NcButton
							type="tertiary"
							:aria-label="t('settings', 'Move up')"
							:disabled="index === 0"
							@click="moveProvider(index, -1)">
							<template #icon>
								<ArrowUpIcon :size="20" />
							</template>
						</NcButton>
						<NcButton
							type="tertiary"
							:aria-label="t('settings', 'Move down')"
							:disabled="index === settings.providers.length - 1"
							@click="moveProvider(index, 1)">
							<template #icon>
								<ArrowDownIcon :size="20" />
							</template>
						</NcButton>
					</div>
				</li>
			</ul>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'MIME priority')"
			:description="t('settings', 'Override provider order for specific MIME types. Providers listed here are tried first; remaining enabled providers still run afterwards unless they are denied.')">
			<table class="previews-admin__table">
				<thead>
					<tr>
						<th>{{ t('settings', 'MIME type') }}</th>
						<th>{{ t('settings', 'Preferred providers') }}</th>
						<th>{{ t('settings', 'Denied providers') }}</th>
						<th />
					</tr>
				</thead>
				<tbody>
					<tr v-for="(row, index) in mimeRows" :key="row.mime + '-' + index">
						<td>
							<NcTextField
								v-model="row.mime"
								:label="t('settings', 'MIME type')"
								:label-visible="false" />
						</td>
						<td>
							<NcSelect
								v-model="row.providers"
								:options="providerClassOptions"
								multiple
								:close-on-select="false" />
						</td>
						<td>
							<NcSelect
								v-model="row.deny"
								:options="providerClassOptions"
								multiple
								:close-on-select="false" />
						</td>
						<td>
							<NcButton type="tertiary" @click="mimeRows.splice(index, 1)">
								{{ t('settings', 'Remove') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
			<NcButton @click="addMimeRow">
				{{ t('settings', 'Add MIME type') }}
			</NcButton>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'HTTP caching')"
			:description="t('settings', 'Control Cache-Control headers for preview responses. Authenticated previews stay private by default.')">
			<h3>{{ t('settings', 'Authenticated previews') }}</h3>
			<NcNoteCard v-if="settings.cacheAuthenticated.visibility === 'public'" type="warning">
				{{ t('settings', 'Setting authenticated previews to public allows shared caches (proxies/CDNs) to store them. Only enable this if you understand the privacy impact.') }}
			</NcNoteCard>
			<div class="previews-admin__fields">
				<NcSelect
					v-model="authVisibility"
					:options="visibilityOptions"
					:clearable="false"
					:input-label="t('settings', 'Visibility')" />
				<NcTextField
					v-model="authMaxAge"
					type="number"
					:label="t('settings', 'max-age (seconds)')"
					:helper-text="t('settings', 'How long browsers may reuse the preview. Default 86400 (1 day).')" />
				<NcTextField
					v-model="authSMaxAge"
					type="number"
					:label="t('settings', 's-maxage (seconds, optional)')"
					:helper-text="t('settings', 'How long shared caches (proxies/CDNs) may store the preview. Leave empty to omit.')" />
				<NcCheckboxRadioSwitch v-model="settings.cacheAuthenticated.immutable" type="switch">
					{{ t('settings', 'immutable') }}
				</NcCheckboxRadioSwitch>
				<NcTextField
					v-model="settings.cacheAuthenticated.cache_control"
					:label="t('settings', 'Raw Cache-Control override (empty = build from fields)')" />
			</div>

			<h3>{{ t('settings', 'Public share previews') }}</h3>
			<div class="previews-admin__fields">
				<NcSelect
					v-model="publicVisibility"
					:options="visibilityOptions"
					:clearable="false"
					:input-label="t('settings', 'Visibility')" />
				<NcTextField
					v-model="publicMaxAge"
					type="number"
					:label="t('settings', 'max-age (seconds)')"
					:helper-text="t('settings', 'How long browsers may reuse public-share previews. Default 86400 (1 day).')" />
				<NcTextField
					v-model="publicSMaxAge"
					type="number"
					:label="t('settings', 's-maxage (seconds, optional)')"
					:helper-text="t('settings', 'How long shared caches may store public-share previews. Leave empty to omit.')" />
				<NcCheckboxRadioSwitch v-model="settings.cachePublic.immutable" type="switch">
					{{ t('settings', 'immutable') }}
				</NcCheckboxRadioSwitch>
				<NcTextField
					v-model="settings.cachePublic.cache_control"
					:label="t('settings', 'Raw Cache-Control override (empty = build from fields)')" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'Failed generations')"
			:description="t('settings', 'Preview generation failures recorded by the server. Retrying generates a preview for that file using the current providers.')">
			<h3>{{ t('settings', 'Retention') }}</h3>
			<div class="previews-admin__fields">
				<NcTextField
					v-model="expirationDays"
					type="number"
					:label="t('settings', 'Expire generated previews after (days)')"
					:helper-text="t('settings', 'Daily job deletes stored preview files older than this. 0 disables file expiry. This does not delete user files.')" />
				<NcTextField
					v-model="failuresRetentionDays"
					type="number"
					:label="t('settings', 'Keep failure records for (days)')"
					:helper-text="t('settings', 'Daily job deletes failure rows older than this. 0 keeps them until the max-rows cap applies.')" />
				<NcTextField
					v-model="failuresMaxRows"
					type="number"
					:label="t('settings', 'Maximum failure rows')"
					:helper-text="t('settings', 'Oldest failure rows are dropped when this cap is exceeded. Default 5000.')" />
			</div>
			<div class="previews-admin__row">
				<NcSelect
					v-model="failureRange"
					:options="rangeOptions"
					:clearable="false"
					:input-label="t('settings', 'Time range')" />
				<NcButton @click="loadFailures">
					{{ t('settings', 'Refresh') }}
				</NcButton>
				<NcButton type="error" :disabled="failures.length === 0" @click="clearFailures">
					{{ t('settings', 'Clear all') }}
				</NcButton>
			</div>
			<p v-if="failures.length === 0" data-cy="previews-failures-empty">
				{{ t('settings', 'No failed preview generations recorded.') }}
			</p>
			<table v-else class="previews-admin__table">
				<thead>
					<tr>
						<th>{{ t('settings', 'File') }}</th>
						<th>{{ t('settings', 'MIME') }}</th>
						<th>{{ t('settings', 'Provider') }}</th>
						<th>{{ t('settings', 'Error') }}</th>
						<th>{{ t('settings', 'Last attempt') }}</th>
						<th>{{ t('settings', 'Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="failure in failures" :key="failure.id">
						<td>
							<a v-if="failure.fileUrl" :href="failure.fileUrl">{{ failure.path || failure.fileId }}</a>
							<span v-else>{{ failure.path || failure.fileId }}</span>
						</td>
						<td>{{ failure.mime }}</td>
						<td>{{ failure.provider }}</td>
						<td :title="failure.error">{{ truncate(failure.error) }}</td>
						<td>{{ formatTime(failure.lastAttempt) }}</td>
						<td class="previews-admin__actions">
							<NcButton type="secondary" @click="retryFailure(failure.id)">
								{{ t('settings', 'Retry') }}
							</NcButton>
							<NcButton type="tertiary" @click="deleteFailure(failure.id)">
								{{ t('settings', 'Clear') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>
		</NcSettingsSection>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown.vue'
import ArrowUpIcon from 'vue-material-design-icons/ArrowUp.vue'
import logger from '../logger.ts'

const documentationLink = loadState('settings', 'previewsDocumentation', '')
const initialSettings = loadState('settings', 'previewsSettings', {})
const initialFailures = loadState('settings', 'previewsFailures', [])

function mimeRowsFromSettings(settings) {
	const priority = settings.mimePriority || {}
	const deny = settings.mimeDeny || {}
	const mimes = [...new Set([...(settings.mimePresets || []), ...Object.keys(priority), ...Object.keys(deny)])]
	return mimes.map((mime) => ({
		mime,
		providers: [...(priority[mime] || [])],
		deny: [...(deny[mime] || [])],
	}))
}

export default {
	name: 'AdminSettingsPreviews',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcNoteCard,
		NcPasswordField,
		NcSelect,
		NcSettingsSection,
		NcTextField,
		ArrowDownIcon,
		ArrowUpIcon,
	},

	data() {
		const settings = JSON.parse(JSON.stringify(initialSettings))
		settings.detection = settings.detection || {}
		settings.ffmpegPath = settings.ffmpegPath || ''
		settings.ffprobePath = settings.ffprobePath || ''
		settings.libreofficePath = settings.libreofficePath || ''
		return {
			documentationLink,
			settings,
			failures: [...initialFailures],
			saving: false,
			testingImaginary: false,
			imaginaryStatus: settings.imaginaryUrl ? 'unknown' : 'unconfigured',
			failureRange: { id: 'all', label: t('settings', 'All') },
			mimeRows: mimeRowsFromSettings(settings),
			formatOptions: [
				{ id: 'jpeg', label: 'JPEG' },
				{ id: 'webp', label: 'WebP' },
			],
			visibilityOptions: [
				{ id: 'private', label: t('settings', 'private') },
				{ id: 'public', label: t('settings', 'public') },
			],
			rangeOptions: [
				{ id: '24h', label: t('settings', 'Last 24 hours') },
				{ id: '7d', label: t('settings', 'Last 7 days') },
				{ id: '30d', label: t('settings', 'Last 30 days') },
				{ id: 'all', label: t('settings', 'All') },
			],
		}
	},

	computed: {
		maxX: {
			get() {
				return this.settings.previewMaxX === null || this.settings.previewMaxX === undefined ? '' : String(this.settings.previewMaxX)
			},
			set(value) {
				this.settings.previewMaxX = value === '' ? null : Number(value)
			},
		},
		maxY: {
			get() {
				return this.settings.previewMaxY === null || this.settings.previewMaxY === undefined ? '' : String(this.settings.previewMaxY)
			},
			set(value) {
				this.settings.previewMaxY = value === '' ? null : Number(value)
			},
		},
		maxMemory: {
			get() {
				return String(this.settings.previewMaxMemory ?? '')
			},
			set(value) {
				this.settings.previewMaxMemory = Number(value)
			},
		},
		maxFilesize: {
			get() {
				return String(this.settings.previewMaxFilesizeImage ?? '')
			},
			set(value) {
				this.settings.previewMaxFilesizeImage = Number(value)
			},
		},
		jpegQuality: {
			get() {
				return String(this.settings.jpegQuality ?? '')
			},
			set(value) {
				this.settings.jpegQuality = Number(value)
			},
		},
		webpQuality: {
			get() {
				return String(this.settings.webpQuality ?? '')
			},
			set(value) {
				this.settings.webpQuality = Number(value)
			},
		},
		concurrencyNew: {
			get() {
				return this.settings.previewConcurrencyNew === null || this.settings.previewConcurrencyNew === undefined
					? ''
					: String(this.settings.previewConcurrencyNew)
			},
			set(value) {
				this.settings.previewConcurrencyNew = value === '' ? null : Number(value)
			},
		},
		concurrencyAll: {
			get() {
				return this.settings.previewConcurrencyAll === null || this.settings.previewConcurrencyAll === undefined
					? ''
					: String(this.settings.previewConcurrencyAll)
			},
			set(value) {
				this.settings.previewConcurrencyAll = value === '' ? null : Number(value)
			},
		},
		expirationDays: {
			get() {
				return String(this.settings.previewExpirationDays ?? 0)
			},
			set(value) {
				this.settings.previewExpirationDays = Number(value)
			},
		},
		failuresRetentionDays: {
			get() {
				return String(this.settings.failuresRetentionDays ?? 30)
			},
			set(value) {
				this.settings.failuresRetentionDays = Number(value)
			},
		},
		failuresMaxRows: {
			get() {
				return String(this.settings.failuresMaxRows ?? 5000)
			},
			set(value) {
				this.settings.failuresMaxRows = Number(value)
			},
		},
		detection() {
			return this.settings.detection || {}
		},
		movieEnabled() {
			return (this.settings.providers || []).some((provider) => provider.class === 'OC\\Preview\\Movie' && provider.enabled)
		},
		officeEnabled() {
			return (this.settings.providers || []).some((provider) => provider.requirement === 'office' && provider.enabled)
		},
		imaginaryEnabled() {
			return (this.settings.providers || []).some((provider) => provider.requirement === 'imaginary' && provider.enabled)
		},
		webpEnabled() {
			return (this.settings.providers || []).some((provider) => provider.class === 'OC\\Preview\\WebP' && provider.enabled)
		},
		showWebpQuality() {
			return this.settings.previewFormat === 'webp' || this.imaginaryEnabled || this.webpEnabled
		},
		showFfmpeg() {
			return Boolean(this.detection.ffmpegFound || this.movieEnabled || this.settings.ffmpegPath)
		},
		showOffice() {
			return Boolean(this.detection.officeFound || this.officeEnabled || this.settings.libreofficePath)
		},
		showExternalTools() {
			return this.showFfmpeg || this.showOffice
		},
		concurrencyHint() {
			const cores = Number(this.detection.cpuCount) || 0
			if (cores > 0) {
				return t('settings', 'This server reports {count} CPU cores. Empty concurrency fields use automatic defaults based on that.', { count: cores })
			}
			return t('settings', 'CPU count could not be detected. Empty concurrency fields use 4 (new) and 8 (all).')
		},
		previewFormatOption: {
			get() {
				return this.formatOptions.find((option) => option.id === this.settings.previewFormat) || this.formatOptions[0]
			},
			set(option) {
				this.settings.previewFormat = option.id
			},
		},
		authVisibility: {
			get() {
				return this.visibilityOptions.find((option) => option.id === this.settings.cacheAuthenticated.visibility) || this.visibilityOptions[0]
			},
			set(option) {
				this.settings.cacheAuthenticated.visibility = option.id
			},
		},
		publicVisibility: {
			get() {
				return this.visibilityOptions.find((option) => option.id === this.settings.cachePublic.visibility) || this.visibilityOptions[0]
			},
			set(option) {
				this.settings.cachePublic.visibility = option.id
			},
		},
		authMaxAge: {
			get() {
				return String(this.settings.cacheAuthenticated.max_age ?? '')
			},
			set(value) {
				this.settings.cacheAuthenticated.max_age = Number(value)
			},
		},
		authSMaxAge: {
			get() {
				const value = this.settings.cacheAuthenticated.s_maxage
				return value === null || value === undefined ? '' : String(value)
			},
			set(value) {
				this.settings.cacheAuthenticated.s_maxage = value === '' ? null : Number(value)
			},
		},
		publicMaxAge: {
			get() {
				return String(this.settings.cachePublic.max_age ?? '')
			},
			set(value) {
				this.settings.cachePublic.max_age = Number(value)
			},
		},
		publicSMaxAge: {
			get() {
				const value = this.settings.cachePublic.s_maxage
				return value === null || value === undefined ? '' : String(value)
			},
			set(value) {
				this.settings.cachePublic.s_maxage = value === '' ? null : Number(value)
			},
		},
		providerClassOptions() {
			return (this.settings.providers || []).map((provider) => provider.class)
		},
		imaginaryStatusLabel() {
			switch (this.imaginaryStatus) {
			case 'reachable':
				return t('settings', 'Reachable')
			case 'unreachable':
				return t('settings', 'Unreachable')
			case 'unconfigured':
				return t('settings', 'Unconfigured')
			default:
				return t('settings', 'Not tested')
			}
		},
	},

	methods: {
		t,
		moveProvider(index, delta) {
			const target = index + delta
			if (target < 0 || target >= this.settings.providers.length) {
				return
			}
			const copy = [...this.settings.providers]
			const [item] = copy.splice(index, 1)
			copy.splice(target, 0, item)
			this.settings.providers = copy
		},
		resetProviders() {
			const defaults = this.settings.defaultEnabledProviders || []
			const enabledSet = new Set(defaults)
			const byClass = Object.fromEntries((this.settings.providers || []).map((provider) => [provider.class, provider]))
			const next = []
			for (const className of defaults) {
				const existing = byClass[className] || { class: className, name: className, mime: '', enabled: true }
				next.push({ ...existing, enabled: true })
			}
			for (const provider of this.settings.providers || []) {
				if (!enabledSet.has(provider.class)) {
					next.push({ ...provider, enabled: false })
				}
			}
			this.settings.providers = next
		},
		addMimeRow() {
			this.mimeRows.push({ mime: '', providers: [], deny: [] })
		},
		payload() {
			const mimePriority = {}
			const mimeDeny = {}
			for (const row of this.mimeRows) {
				const mime = (row.mime || '').trim().toLowerCase()
				if (!mime) {
					continue
				}
				if (row.providers?.length) {
					mimePriority[mime] = row.providers
				}
				if (row.deny?.length) {
					mimeDeny[mime] = row.deny
				}
			}
			const enabledCount = (this.settings.providers || []).filter((provider) => provider.enabled).length
			return {
				...this.settings,
				mimePriority,
				mimeDeny,
				confirmEmptyProviders: enabledCount === 0,
			}
		},
		async save() {
			const enabledCount = (this.settings.providers || []).filter((provider) => provider.enabled).length
			if (enabledCount === 0 && !window.confirm(t('settings', 'Disabling every preview provider will break image previews. Continue?'))) {
				return
			}
			this.saving = true
			try {
				await confirmPassword()
				const { data } = await axios.put(generateUrl('/settings/api/admin/previews'), { settings: this.payload() })
				this.settings = data
				this.mimeRows = mimeRowsFromSettings(data)
				showSuccess(t('settings', 'Preview settings saved'))
			} catch (error) {
				logger.error('Could not save preview settings', { error })
				showError(error.response?.data?.error || t('settings', 'Failed to save preview settings'))
			} finally {
				this.saving = false
			}
		},
		async testImaginary() {
			this.testingImaginary = true
			try {
				const { data } = await axios.post(generateUrl('/settings/api/admin/previews/imaginary/test'), {
					url: this.settings.imaginaryUrl,
					key: this.settings.imaginaryKey,
				})
				this.imaginaryStatus = data.status || 'unreachable'
			} catch (error) {
				this.imaginaryStatus = error.response?.data?.status || 'unreachable'
				showError(error.response?.data?.error || t('settings', 'Could not reach Imaginary'))
			} finally {
				this.testingImaginary = false
			}
		},
		async loadFailures() {
			try {
				const { data } = await axios.get(generateUrl('/settings/api/admin/previews/failures'), {
					params: { range: this.failureRange.id },
				})
				this.failures = data.failures || []
			} catch (error) {
				logger.error('Could not load preview failures', { error })
				showError(t('settings', 'Failed to load preview failures'))
			}
		},
		async retryFailure(id) {
			try {
				await axios.post(generateUrl('/settings/api/admin/previews/failures/{id}/retry'.replace('{id}', id)))
				showSuccess(t('settings', 'Preview regenerated'))
				await this.loadFailures()
			} catch (error) {
				showError(error.response?.data?.error || t('settings', 'Retry failed'))
				await this.loadFailures()
			}
		},
		async deleteFailure(id) {
			try {
				await axios.delete(generateUrl('/settings/api/admin/previews/failures/{id}'.replace('{id}', id)))
				await this.loadFailures()
			} catch (error) {
				showError(t('settings', 'Could not clear failure'))
			}
		},
		async clearFailures() {
			if (!window.confirm(t('settings', 'Clear all recorded preview failures?'))) {
				return
			}
			try {
				await axios.delete(generateUrl('/settings/api/admin/previews/failures'))
				this.failures = []
			} catch (error) {
				showError(t('settings', 'Could not clear failures'))
			}
		},
		truncate(value) {
			if (!value) {
				return ''
			}
			return value.length > 80 ? value.slice(0, 80) + '…' : value
		},
		formatTime(timestamp) {
			if (!timestamp) {
				return ''
			}
			return new Date(timestamp * 1000).toLocaleString()
		},
		providerAvailabilityLabel(provider) {
			if (provider.available !== false) {
				return t('settings', 'Available')
			}
			switch (provider.requirement) {
			case 'imagick':
				if (this.detection.imagick) {
					return t('settings', 'ImageMagick is installed but does not support {format}', { format: provider.imagickFormat || 'this format' })
				}
				return t('settings', 'Requires ImageMagick (Imagick)')
			case 'ffmpeg':
				return t('settings', 'Requires ffmpeg')
			case 'office':
				return t('settings', 'Requires LibreOffice or OpenOffice')
			case 'imaginary':
				return t('settings', 'Requires Imaginary URL')
			default:
				return t('settings', 'Unavailable')
			}
		},
	},
}
</script>

<style scoped>
.previews-admin__fields {
	display: flex;
	flex-direction: column;
	gap: 12px;
	max-width: 480px;
	margin-block: 12px;
}

.previews-admin__row {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 12px;
	margin-block: 12px;
}

.previews-admin__providers {
	list-style: none;
	padding: 0;
	margin: 0;
}

.previews-admin__hint {
	color: var(--color-text-maxcontrast);
	margin-block: 4px 12px;
}

.previews-admin__provider {
	display: grid;
	grid-template-columns: minmax(140px, 1fr) minmax(140px, 1.4fr) minmax(100px, 1fr) minmax(140px, 1fr) auto;
	gap: 8px;
	align-items: center;
	padding-block: 6px;
	border-bottom: 1px solid var(--color-border);
}

.previews-admin__availability {
	font-size: 13px;
}

.previews-admin__availability[data-available='true'] {
	color: var(--color-success);
}

.previews-admin__availability[data-available='false'] {
	color: var(--color-warning);
}

.previews-admin__class,
.previews-admin__mime {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	overflow-wrap: anywhere;
}

.previews-admin__order {
	display: flex;
}

.previews-admin__table {
	width: 100%;
	margin-block: 12px;
	border-collapse: collapse;
}

.previews-admin__table th,
.previews-admin__table td {
	text-align: start;
	padding: 6px 8px;
	vertical-align: top;
	border-bottom: 1px solid var(--color-border);
}

.previews-admin__actions {
	display: flex;
	gap: 8px;
}

.previews-admin__status[data-status='reachable'] {
	color: var(--color-success);
}

.previews-admin__status[data-status='unreachable'] {
	color: var(--color-error);
}
</style>
