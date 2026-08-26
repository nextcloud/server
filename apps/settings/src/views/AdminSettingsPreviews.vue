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
				{{ t('settings', 'This page controls preview providers, limits, Imaginary, and generation failures.') }}
				{{ t('settings', 'The Preview Generator app (if installed) only controls which sizes are pre-generated and when background generation runs.') }}
			</p>
			<div class="previews-admin__master">
				<NcCheckboxRadioSwitch
					v-model="settings.enablePreviews"
					type="switch"
					data-cy="previews-enable"
					aria-controls="previews-admin-dependent"
					:disabled="settings.configIsReadonly">
					{{ t('settings', 'Enable previews') }}
				</NcCheckboxRadioSwitch>
				<p class="previews-admin__hint">
					{{ t('settings', 'Master switch for preview generation. When off, Nextcloud will not generate or serve file previews, and the settings below are ignored until you turn this back on and save.') }}
				</p>
				<div class="previews-admin__save">
					<NcButton
						type="primary"
						:disabled="saveDisabled"
						data-cy="previews-save"
						@click="save">
						{{ t('settings', 'Save') }}
					</NcButton>
				</div>
			</div>
		</NcSettingsSection>

		<NcNoteCard v-if="settingsLocked" type="info">
			{{ t('settings', 'Preview generation is disabled. The settings below are kept for when you re-enable previews, but they have no effect until then.') }}
		</NcNoteCard>

		<fieldset
			id="previews-admin-dependent"
			class="previews-admin__dependent"
			:disabled="settingsLocked"
			:inert="settingsLocked || undefined">
		<NcSettingsSection
			:name="t('settings', 'Providers')"
			:description="t('settings', 'Enable providers and set their global priority. The order of enabled providers is the fallback order for all MIME types. Preview format is the type stored for that provider.')">
			<div class="previews-admin__toolbar">
				<div class="previews-admin__filters">
					<NcSelect
						v-model="statusFilterOption"
						class="previews-admin__status-filter"
						:options="statusFilterOptions"
						:clearable="false"
						:input-label="t('settings', 'Status')"
						data-cy="previews-status-filter" />
					<NcSelect
						v-model="mimeFilterOption"
						class="previews-admin__mime-filter"
						:options="mimeFilterOptions"
						:clearable="false"
						:input-label="t('settings', 'Source MIME type')"
						data-cy="previews-mime-filter" />
				</div>
				<NcButton
					:disabled="settings.configIsReadonly"
					data-cy="previews-reset-providers"
					@click="resetProviders">
					{{ t('settings', 'Reset to defaults') }}
				</NcButton>
			</div>
			<div class="previews-admin__providers" data-cy="previews-providers">
				<div class="previews-admin__provider previews-admin__provider--header">
					<span>{{ t('settings', 'Provider') }}</span>
					<span>{{ t('settings', 'Class') }}</span>
					<span>{{ t('settings', 'MIME') }}</span>
					<span>{{ t('settings', 'Preview format') }}</span>
					<span>{{ t('settings', 'Availability') }}</span>
					<span>{{ t('settings', 'Order') }}</span>
				</div>
				<VueDraggable
					:key="providersListKey"
					v-model="orderedProviders"
					handle=".previews-admin__drag-handle"
					:disabled="settings.configIsReadonly || settingsLocked || !canReorderProviders">
					<div
						v-for="provider in orderedProviders"
						:key="provider.class"
						class="previews-admin__provider"
						:class="{ 'previews-admin__provider--unavailable': provider.available === false }">
						<NcCheckboxRadioSwitch
							v-model="provider.enabled"
							type="switch">
							{{ provider.name }}<sup
								v-if="providerUnsupportedMark(provider)"
								class="previews-admin__footnote-mark">{{ providerUnsupportedMark(provider) }}</sup>
						</NcCheckboxRadioSwitch>
						<code class="previews-admin__class">{{ provider.class }}</code>
						<span class="previews-admin__mime">{{ provider.mime }}</span>
						<span class="previews-admin__output" data-cy="previews-provider-format">{{ providerOutputInfo(provider).label }}<sup
							v-if="providerFormatMark(provider)"
							class="previews-admin__footnote-mark">{{ providerFormatMark(provider) }}</sup>
						</span>
						<span
							class="previews-admin__availability"
							data-cy="previews-provider-availability">
							<a
								v-if="providerAvailabilityHref(provider)"
								class="previews-admin__availability-link"
								:href="providerAvailabilityHref(provider)"
								:aria-label="providerAvailabilityLinkLabel(provider)">
								<NcChip
									:text="providerAvailabilityLabel(provider)"
									:variant="providerAvailabilityVariant(provider)"
									no-close />
							</a>
							<NcChip
								v-else
								:text="providerAvailabilityLabel(provider)"
								:variant="providerAvailabilityVariant(provider)"
								no-close />
						</span>
						<div class="previews-admin__order">
							<NcButton
								type="tertiary"
								:aria-label="t('settings', 'Move up')"
								:disabled="providerIndex(provider) === 0"
								@click="moveProvider(providerIndex(provider), -1)">
								<template #icon>
									<ArrowUpIcon :size="20" />
								</template>
							</NcButton>
							<NcButton
								type="tertiary"
								:aria-label="t('settings', 'Move down')"
								:disabled="providerIndex(provider) === settings.providers.length - 1"
								@click="moveProvider(providerIndex(provider), 1)">
								<template #icon>
									<ArrowDownIcon :size="20" />
								</template>
							</NcButton>
							<span
								class="previews-admin__drag-handle"
								:aria-label="t('settings', 'Drag to reorder')"
								role="button">
								<DragVerticalIcon :size="20" />
							</span>
						</div>
					</div>
				</VueDraggable>
				<ul v-if="visibleProviderFootnotes.length" class="previews-admin__providers-footnotes">
					<li v-for="note in visibleProviderFootnotes" :key="note.key">
						<sup class="previews-admin__footnote-mark">{{ note.mark }}</sup> {{ note.text }}
					</li>
				</ul>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'General')"
			:description="t('settings', 'These limits apply to all preview generation, both on-demand and from the Preview Generator app.')">
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
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'Preview quality')"
			:description="t('settings', 'JPEG and WebP quality apply when a preview is generated in that format. PNG previews are lossless, so they have no quality setting.')">
			<div class="previews-admin__fields">
				<NcTextField
					v-model="jpegQuality"
					type="number"
					:label="t('settings', 'JPEG quality (1–100)')"
					:helper-text="t('settings', 'Higher values look better and use more disk. Default 80.')" />
				<NcTextField
					v-model="webpQuality"
					type="number"
					:label="t('settings', 'WebP quality (1–100)')"
					:helper-text="t('settings', 'Higher values look better and use more disk. Default 80.')" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'Performance')"
			:description="concurrencyHint">
			<div class="previews-admin__fields">
				<NcTextField
					v-model="concurrencyNew"
					type="number"
					data-cy="previews-concurrency-new"
					:label="t('settings', 'New preview concurrency')"
					:helper-text="t('settings', 'How many previews may be generated at once. Leave empty to use the CPU count. Do not set higher than the number of CPU cores.')" />
				<NcNoteCard v-if="newConcurrencyExceedsCpu" type="warning" data-cy="previews-concurrency-new-warning">
					{{ t('settings', 'New preview concurrency is higher than the {count} CPU cores detected on this server. Preview generation may overload the host.', { count: detectedCpuCount }) }}
				</NcNoteCard>
				<NcTextField
					v-model="concurrencyAll"
					type="number"
					data-cy="previews-concurrency-all"
					:label="t('settings', 'Total preview concurrency')"
					:helper-text="t('settings', 'All preview requests, including cache hits. Leave empty to use twice the “New preview concurrency” limit. Should be greater than or equal to new preview concurrency.')" />
				<NcNoteCard v-if="totalConcurrencyBelowNew" type="warning" data-cy="previews-concurrency-all-warning">
					{{ t('settings', 'Total preview concurrency should be greater than or equal to new preview concurrency ({count}). Lower values are treated as equal to the new-preview limit.', { count: effectiveNewConcurrency }) }}
				</NcNoteCard>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			id="previews-section-imaginary"
			:name="t('settings', 'Imaginary')"
			:description="t('settings', 'Use Imaginary to offload image processing. Put Imaginary above native HEIC in Providers so HEIC/HEIF files try Imaginary first.')">
			<div class="previews-admin__fields">
				<NcNoteCard v-if="!settings.imaginaryUrl" type="warning">
					{{ t('settings', 'Not configured yet. Set a URL so Imaginary providers can generate previews.') }}
				</NcNoteCard>
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
				<NcSelect
					v-model="previewFormatOption"
					:options="formatOptions"
					:clearable="false"
					:input-label="t('settings', 'Preview output format')" />
				<p class="previews-admin__hint">
					{{ t('settings', 'Only Imaginary uses this setting. Other providers usually keep the source image type (JPEG stays JPEG, PNG stays PNG). Nextcloud stores one file per preview size.') }}
				</p>
			</div>
			<div class="previews-admin__test">
				<NcButton :disabled="testingImaginary || !settings.imaginaryUrl" @click="testImaginary">
					{{ t('settings', 'Test connection') }}
				</NcButton>
				<NcNoteCard v-if="imaginaryTestSuccess" type="success">
					<div class="previews-admin__banner">
						<p>{{ imaginaryTestSuccess }}</p>
						<NcButton
							type="tertiary"
							:aria-label="t('settings', 'Dismiss')"
							@click="imaginaryTestSuccess = ''">
							<template #icon>
								<CloseIcon :size="20" />
							</template>
						</NcButton>
					</div>
				</NcNoteCard>
				<NcNoteCard v-if="imaginaryTestError" type="error">
					<div class="previews-admin__banner">
						<p>{{ imaginaryTestError }}</p>
						<NcButton
							type="tertiary"
							:aria-label="t('settings', 'Dismiss')"
							@click="imaginaryTestError = ''">
							<template #icon>
								<CloseIcon :size="20" />
							</template>
						</NcButton>
					</div>
				</NcNoteCard>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			id="previews-section-movie"
			:name="t('settings', 'Movie')"
			:description="t('settings', 'Generate video previews with ffmpeg.')">
			<div class="previews-admin__fields">
				<NcNoteCard v-if="detection.ffmpegFound" type="success">
					{{ t('settings', 'ffmpeg found: {path}', { path: detection.ffmpegDetectedPath }) }}
				</NcNoteCard>
				<NcNoteCard v-else type="warning">
					{{ t('settings', 'Not detected. Set a path to ffmpeg or install it so the Movie provider can generate video previews.') }}
				</NcNoteCard>
				<NcTextField
					v-model="settings.ffmpegPath"
					:label="t('settings', 'ffmpeg path')"
					:helper-text="t('settings', 'Custom path to ffmpeg for video previews. Empty uses the server PATH.')" />
				<NcTextField
					v-model="settings.ffprobePath"
					:label="t('settings', 'ffprobe path')"
					:helper-text="t('settings', 'Used for HDR video metadata. Empty uses the same directory as ffmpeg.')" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			id="previews-section-office"
			:name="t('settings', 'Office')"
			:description="t('settings', 'Generate previews for Microsoft Office, StarOffice, and EMF files with LibreOffice or OpenOffice. Leave the path empty to search PATH.')">
			<div class="previews-admin__fields">
				<NcNoteCard v-if="!detection.officeFound" type="warning">
					{{ t('settings', 'Not detected. Set a path to LibreOffice or OpenOffice, or install it, so office providers can generate document previews.') }}
				</NcNoteCard>
				<NcTextField
					v-model="settings.libreofficePath"
					:label="t('settings', 'LibreOffice path')"
					:helper-text="t('settings', 'Custom path to LibreOffice or OpenOffice. Empty searches for libreoffice, then openoffice.')" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('settings', 'Failed generations')"
			:description="t('settings', 'Provider errors while generating a preview. A later provider may still produce an image. Retry generates the preview again with the current providers.')">
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
		</fieldset>

		<div class="previews-admin__save previews-admin__save--bottom">
			<NcButton
				type="primary"
				:disabled="saveDisabled"
				data-cy="previews-save-bottom"
				@click="save">
				{{ t('settings', 'Save') }}
			</NcButton>
		</div>
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
import NcChip from '@nextcloud/vue/components/NcChip'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import VueDraggable from 'vuedraggable'
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown.vue'
import ArrowUpIcon from 'vue-material-design-icons/ArrowUp.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import DragVerticalIcon from 'vue-material-design-icons/DragVertical.vue'
import logger from '../logger.ts'

const documentationLink = loadState('settings', 'previewsDocumentation', '')
const initialSettings = loadState('settings', 'previewsSettings', {})
const initialFailures = loadState('settings', 'previewsFailures', [])

/** Providers that always store JPEG previews and therefore use JPEG quality. */
const JPEG_QUALITY_PROVIDERS = new Set([
	'OC\\Preview\\JPEG',
	'OC\\Preview\\HEIC',
	'OC\\Preview\\Movie',
])

/** Providers that always store WebP previews and therefore use WebP quality. */
const WEBP_QUALITY_PROVIDERS = new Set([
	'OC\\Preview\\WebP',
])

/** Imaginary follows preview_format for JPEG vs WebP output. */
const FORMAT_QUALITY_PROVIDERS = new Set([
	'OC\\Preview\\Imaginary',
])

/** Imaginary PDF writes PNG unless preview_format is WebP. */
const FORMAT_WEBP_ONLY_PROVIDERS = new Set([
	'OC\\Preview\\ImaginaryPDF',
])

/** Providers that always store PNG previews. PNG is lossless (no quality setting). */
const PNG_PROVIDERS = new Set([
	'OC\\Preview\\PNG',
	'OC\\Preview\\BMP',
	'OC\\Preview\\XBitmap',
	'OC\\Preview\\Krita',
	'OC\\Preview\\TIFF',
	'OC\\Preview\\SVG',
	'OC\\Preview\\TGA',
	'OC\\Preview\\SGI',
	'OC\\Preview\\PDF',
	'OC\\Preview\\Postscript',
	'OC\\Preview\\Illustrator',
	'OC\\Preview\\Photoshop',
	'OC\\Preview\\Font',
	'OC\\Preview\\MarkDown',
	'OC\\Preview\\TXT',
	'OC\\Preview\\OpenDocument',
	'OC\\Preview\\MSOfficeDoc',
	'OC\\Preview\\MSOffice2003',
	'OC\\Preview\\MSOffice2007',
	'OC\\Preview\\StarOffice',
	'OC\\Preview\\EMF',
])

function formSnapshot(settings) {
	return JSON.stringify({
		enablePreviews: !!settings.enablePreviews,
		previewMaxX: settings.previewMaxX ?? null,
		previewMaxY: settings.previewMaxY ?? null,
		previewMaxMemory: settings.previewMaxMemory ?? null,
		previewMaxFilesizeImage: settings.previewMaxFilesizeImage ?? null,
		jpegQuality: settings.jpegQuality ?? null,
		webpQuality: settings.webpQuality ?? null,
		previewFormat: settings.previewFormat || 'jpeg',
		previewConcurrencyNew: settings.previewConcurrencyNew ?? null,
		previewConcurrencyAll: settings.previewConcurrencyAll ?? null,
		previewExpirationDays: settings.previewExpirationDays ?? 0,
		imaginaryUrl: settings.imaginaryUrl || '',
		imaginaryKey: settings.imaginaryKey || '',
		ffmpegPath: settings.ffmpegPath || '',
		ffprobePath: settings.ffprobePath || '',
		libreofficePath: settings.libreofficePath || '',
		providers: (settings.providers || []).map((provider) => `${provider.class}:${provider.enabled ? 1 : 0}`),
		failuresRetentionDays: settings.failuresRetentionDays ?? 30,
		failuresMaxRows: settings.failuresMaxRows ?? 5000,
	})
}

export default {
	name: 'AdminSettingsPreviews',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcChip,
		NcNoteCard,
		NcPasswordField,
		NcSelect,
		NcSettingsSection,
		NcTextField,
		VueDraggable,
		ArrowDownIcon,
		ArrowUpIcon,
		CloseIcon,
		DragVerticalIcon,
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
			imaginaryTestSuccess: '',
			imaginaryTestError: '',
			failureRange: { id: 'all', label: t('settings', 'All') },
			providerFilter: 'all',
			mimeFilter: '',
			providersListKey: 0,
			savedSnapshot: formSnapshot(settings),
			formatOptions: [
				{ id: 'jpeg', label: 'JPEG' },
				{ id: 'webp', label: 'WebP' },
			],
			statusFilterOptions: [
				{ id: 'all', label: t('settings', 'All') },
				{ id: 'available', label: t('settings', 'Available') },
				{ id: 'unavailable', label: t('settings', 'Unavailable') },
				{ id: 'supported', label: t('settings', 'Supported') },
				{ id: 'unsupported', label: t('settings', 'Unsupported') },
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
		settingsLocked() {
			return this.settings.enablePreviews === false
		},
		dirty() {
			return formSnapshot(this.settings) !== this.savedSnapshot
		},
		saveDisabled() {
			return this.saving || this.settings.configIsReadonly || !this.dirty
		},
		detection() {
			return this.settings.detection || {}
		},
		visibleProviderFootnotes() {
			const present = new Set()
			for (const provider of this.filteredProviders) {
				if (provider.unsupported) {
					present.add('unsupported')
				}
				const footnote = this.providerOutputInfo(provider).footnote
				if (footnote) {
					present.add(footnote)
				}
			}
			const order = ['unsupported', 'imaginary-jpeg', 'mp3', 'legacy-image']
			const marks = ['1', '2', '3', '4']
			const notes = []
			for (const key of order) {
				if (!present.has(key)) {
					continue
				}
				notes.push({
					key,
					mark: marks[notes.length],
					text: this.providerFootnoteText(key),
				})
			}
			return notes
		},
		filteredProviders() {
			const list = this.settings.providers || []
			return list.filter((provider) => this.providerMatchesStatus(provider) && this.providerMatchesMime(provider))
		},
		canReorderProviders() {
			return this.providerFilter === 'all' && this.mimeFilter === ''
		},
		mimeFilterOptions() {
			const mimes = new Set()
			for (const provider of this.settings.providers || []) {
				for (const mime of provider.sourceMimes || []) {
					if (mime) {
						mimes.add(mime)
					}
				}
			}
			const options = [...mimes].sort().map((mime) => ({ id: mime, label: mime }))
			return [
				{ id: '', label: t('settings', 'All source MIME types') },
				...options,
			]
		},
		mimeFilterOption: {
			get() {
				return this.mimeFilterOptions.find((option) => option.id === this.mimeFilter) || this.mimeFilterOptions[0]
			},
			set(option) {
				this.mimeFilter = option?.id || ''
			},
		},
		statusFilterOption: {
			get() {
				return this.statusFilterOptions.find((option) => option.id === this.providerFilter) || this.statusFilterOptions[0]
			},
			set(option) {
				this.providerFilter = option?.id || 'all'
			},
		},
		orderedProviders: {
			get() {
				return this.filteredProviders
			},
			set(value) {
				if (!this.canReorderProviders) {
					return
				}
				this.settings.providers = value
			},
		},
		detectedCpuCount() {
			return Number(this.detection.cpuCount) || 0
		},
		concurrencyHint() {
			if (this.detectedCpuCount > 0) {
				return t('settings', 'This server reports {count} CPU cores.', { count: this.detectedCpuCount })
			}
			return t('settings', 'This server cannot detect the number of CPU cores and will assume there are 4.')
		},
		effectiveNewConcurrency() {
			if (this.settings.previewConcurrencyNew !== null && this.settings.previewConcurrencyNew !== undefined) {
				return Number(this.settings.previewConcurrencyNew)
			}
			return this.detectedCpuCount > 0 ? this.detectedCpuCount : 4
		},
		newConcurrencyExceedsCpu() {
			const value = this.settings.previewConcurrencyNew
			return this.detectedCpuCount > 0
				&& value !== null
				&& value !== undefined
				&& Number(value) > this.detectedCpuCount
		},
		totalConcurrencyBelowNew() {
			const value = this.settings.previewConcurrencyAll
			return this.detectedCpuCount > 0
				&& value !== null
				&& value !== undefined
				&& Number(value) < this.effectiveNewConcurrency
		},
		previewFormatOption: {
			get() {
				return this.formatOptions.find((option) => option.id === this.settings.previewFormat) || this.formatOptions[0]
			},
			set(option) {
				this.settings.previewFormat = option.id
			},
		},
	},

	mounted() {
		window.addEventListener('beforeunload', this.onBeforeUnload)
	},

	beforeDestroy() {
		window.removeEventListener('beforeunload', this.onBeforeUnload)
	},

	methods: {
		t,
		onBeforeUnload(event) {
			if (!this.dirty) {
				return
			}
			event.preventDefault()
			event.returnValue = true
		},
		providerIndex(provider) {
			return (this.settings.providers || []).findIndex((row) => row.class === provider.class)
		},
		providerMatchesStatus(provider) {
			switch (this.providerFilter) {
			case 'available':
				return provider.available !== false
			case 'unavailable':
				return provider.available === false
			case 'supported':
				return !provider.unsupported
			case 'unsupported':
				return !!provider.unsupported
			default:
				return true
			}
		},
		providerMatchesMime(provider) {
			if (!this.mimeFilter) {
				return true
			}
			return (provider.sourceMimes || []).some((token) => this.mimeTokenMatches(token, this.mimeFilter))
		},
		mimeTokenMatches(token, selected) {
			if (token === selected) {
				return true
			}
			if (token.endsWith('/*')) {
				return selected.startsWith(token.slice(0, -1))
			}
			if (token.endsWith('.*') || token.endsWith('-*')) {
				return selected.startsWith(token.slice(0, -1))
			}
			return false
		},
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
			const enabledDefaults = this.settings.defaultEnabledProviders || []
			const enabledSet = new Set(enabledDefaults)
			const byClass = Object.fromEntries((this.settings.providers || []).map((provider) => [provider.class, provider]))
			const order = (this.settings.defaultProviderOrder && this.settings.defaultProviderOrder.length)
				? this.settings.defaultProviderOrder
				: [...enabledDefaults, ...(this.settings.providers || []).map((provider) => provider.class)]
			const next = []
			const seen = new Set()
			for (const className of order) {
				if (seen.has(className)) {
					continue
				}
				const existing = byClass[className]
				if (!existing) {
					continue
				}
				next.push({ ...existing, enabled: enabledSet.has(className) })
				seen.add(className)
			}
			for (const provider of this.settings.providers || []) {
				if (seen.has(provider.class)) {
					continue
				}
				next.push({ ...provider, enabled: false })
			}
			this.settings.providers = next
			this.providersListKey += 1
		},
		payload() {
			const enabledCount = (this.settings.providers || []).filter((provider) => provider.enabled).length
			return {
				...this.settings,
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
				this.settings.detection = data.detection || this.settings.detection || {}
				this.settings.ffmpegPath = this.settings.ffmpegPath || ''
				this.settings.ffprobePath = this.settings.ffprobePath || ''
				this.settings.libreofficePath = this.settings.libreofficePath || ''
				this.savedSnapshot = formSnapshot(this.settings)
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
			this.imaginaryTestSuccess = ''
			this.imaginaryTestError = ''
			try {
				const { data } = await axios.post(generateUrl('/settings/api/admin/previews/imaginary/test'), {
					url: this.settings.imaginaryUrl,
					key: this.settings.imaginaryKey,
				})
				if (data.status === 'reachable') {
					this.imaginaryTestSuccess = t('settings', 'Imaginary is reachable.')
				} else {
					this.imaginaryTestError = data.error || t('settings', 'Could not reach Imaginary')
				}
			} catch (error) {
				this.imaginaryTestError = error.response?.data?.error || t('settings', 'Could not reach Imaginary')
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
		providerOutputInfo(provider) {
			const className = provider.class
			const format = this.settings.previewFormat || 'jpeg'
			if (FORMAT_QUALITY_PROVIDERS.has(className)) {
				if (format === 'webp') {
					return { label: t('settings', 'WebP'), footnote: null }
				}
				return { label: t('settings', 'JPEG'), footnote: 'imaginary-jpeg' }
			}
			if (FORMAT_WEBP_ONLY_PROVIDERS.has(className)) {
				return {
					label: format === 'webp' ? t('settings', 'WebP') : t('settings', 'PNG'),
					footnote: null,
				}
			}
			if (JPEG_QUALITY_PROVIDERS.has(className)) {
				return { label: t('settings', 'JPEG'), footnote: null }
			}
			if (WEBP_QUALITY_PROVIDERS.has(className)) {
				return { label: t('settings', 'WebP'), footnote: null }
			}
			if (PNG_PROVIDERS.has(className)) {
				return { label: t('settings', 'PNG'), footnote: null }
			}
			if (className === 'OC\\Preview\\GIF') {
				return { label: t('settings', 'GIF'), footnote: null }
			}
			if (className === 'OC\\Preview\\MP3') {
				return { label: t('settings', 'JPEG or PNG'), footnote: 'mp3' }
			}
			if (className === 'OC\\Preview\\Image') {
				return { label: t('settings', 'JPEG, PNG, GIF, or WebP'), footnote: 'legacy-image' }
			}
			return { label: t('settings', 'Unknown'), footnote: null }
		},
		providerFootnoteMark(key) {
			const note = this.visibleProviderFootnotes.find((row) => row.key === key)
			return note ? note.mark : ''
		},
		providerFootnoteText(key) {
			switch (key) {
			case 'unsupported':
				return t('settings', 'Disabled by default due to security and performance concerns. These providers are still available, but enabling them is discouraged and they are considered unsupported.')
			case 'imaginary-jpeg':
				return t('settings', 'When the preview output format is JPEG, Imaginary still writes PNG for GIF, PDF, PNG, SVG, and Illustrator files.')
			case 'mp3':
				return t('settings', 'MP3 previews use the artwork embedded in the file’s ID3 tag. The generated preview format matches that artwork (typically JPEG or PNG).')
			case 'legacy-image':
				return t('settings', 'The legacy Image provider keeps the source image type.')
			default:
				return ''
			}
		},
		providerUnsupportedMark(provider) {
			return provider.unsupported ? this.providerFootnoteMark('unsupported') : ''
		},
		providerFormatMark(provider) {
			const footnote = this.providerOutputInfo(provider).footnote
			return footnote ? this.providerFootnoteMark(footnote) : ''
		},
		providerAvailabilityVariant(provider) {
			if (provider.available === false || provider.unsupported) {
				return 'warning'
			}
			return 'success'
		},
		providerAvailabilityHref(provider) {
			if (provider.available !== false) {
				return ''
			}
			switch (provider.requirement) {
			case 'ffmpeg':
				return '#previews-section-movie'
			case 'office':
				return '#previews-section-office'
			case 'imaginary':
				return '#previews-section-imaginary'
			default:
				return ''
			}
		},
		providerAvailabilityLinkLabel(provider) {
			switch (provider.requirement) {
			case 'ffmpeg':
				return t('settings', 'Go to the Movie section to configure ffmpeg')
			case 'office':
				return t('settings', 'Go to the Office section to configure LibreOffice or OpenOffice')
			case 'imaginary':
				return t('settings', 'Go to the Imaginary section to set the URL')
			default:
				return ''
			}
		},
		providerAvailabilityLabel(provider) {
			if (provider.available === false) {
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
			}
			if (provider.unsupported) {
				return t('settings', 'Unsupported')
			}
			return t('settings', 'Available')
		},
	},
}
</script>

<style scoped>
.previews-admin {
	padding-block-end: calc(var(--default-grid-baseline) * 10);
}

.previews-admin__master {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 8px;
	margin-block-start: 12px;
}

.previews-admin__save {
	display: flex;
	align-items: flex-start;
}

.previews-admin__save--bottom {
	/* Match NcSettingsSection spacing so the last control is not flush with the content edge. */
	margin-block: calc(var(--default-grid-baseline) * 7);
	margin-inline: calc(var(--default-grid-baseline) * 7);
}

.previews-admin__dependent {
	margin: 0;
	padding: 0;
	border: 0;
	min-width: 0;
}

/* NcSettingsSection only draws a divider on :not(:last-child). Keep one on every section. */
.previews-admin :deep(.settings-section) {
	border-bottom: 1px solid var(--color-border);
	scroll-margin-block-start: calc(var(--header-height, 50px) + var(--body-container-margin, 0px) + 12px);
}

.previews-admin__dependent:disabled,
.previews-admin__dependent[inert] {
	opacity: 0.5;
	filter: grayscale(0.2);
}

.previews-admin__fields {
	display: flex;
	flex-direction: column;
	gap: 12px;
	max-width: 480px;
	margin-block: 12px;
}

.previews-admin__fields :deep(.notecard) {
	margin-block: 0;
}

.previews-admin__row {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 12px;
	margin-block: 12px;
}

.previews-admin__toolbar {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-end;
	justify-content: space-between;
	gap: 12px;
	margin-block: 12px;
}

.previews-admin__filters {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-end;
	gap: 12px;
}

.previews-admin__status-filter,
.previews-admin__mime-filter {
	min-width: 220px;
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
	grid-template-columns: minmax(140px, 1.1fr) minmax(140px, 1.3fr) minmax(90px, 1fr) minmax(90px, 0.8fr) minmax(160px, 1.2fr) auto;
	gap: 8px;
	align-items: center;
	padding-block: 6px;
	border-bottom: 1px solid var(--color-border);
}

.previews-admin__provider--header {
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	border-bottom: 2px solid var(--color-border);
}

.previews-admin__availability {
	min-width: 0;
}

.previews-admin__availability-link {
	display: inline-flex;
	max-width: 100%;
	color: inherit;
	text-decoration: none;
}

.previews-admin__availability-link :deep(.nc-chip) {
	cursor: pointer;
}

.previews-admin__availability-link :deep(.nc-chip__text) {
	text-decoration: underline;
}

.previews-admin__availability :deep(.nc-chip) {
	height: auto;
	min-height: 24px;
	max-width: 100%;
}

.previews-admin__availability :deep(.nc-chip__text) {
	white-space: normal;
	text-wrap: wrap;
	overflow: visible;
	line-height: 1.3;
	padding-block: 4px;
	font-weight: 600;
}

.previews-admin__class,
.previews-admin__mime,
.previews-admin__output {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	overflow-wrap: anywhere;
}

.previews-admin__footnote-mark {
	font-size: 0.7em;
	font-weight: 600;
	line-height: 0;
	position: relative;
	top: -0.35em;
	vertical-align: baseline;
	margin-inline-start: 0.1em;
}

.previews-admin__order {
	display: flex;
	align-items: center;
	justify-self: end;
}

.previews-admin__drag-handle {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: var(--default-clickable-area);
	min-height: var(--default-clickable-area);
	color: var(--color-text-maxcontrast);
	cursor: grab;
}

.previews-admin__drag-handle:active {
	cursor: grabbing;
}

.previews-admin__providers-footnotes {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
	margin: 8px 0 0;
	padding-inline-start: 1.25em;
}

.previews-admin__providers-footnotes li {
	margin-block: 4px;
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

.previews-admin__test {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 12px;
	max-width: 480px;
	margin-block-start: 12px;
}

.previews-admin__test :deep(.notecard) {
	margin: 0;
	width: 100%;
}

.previews-admin__test :deep(.notecard > div) {
	flex: 1;
	min-width: 0;
}

.previews-admin__banner {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: 8px;
}

.previews-admin__banner p {
	margin: 0;
	flex: 1;
}
</style>
