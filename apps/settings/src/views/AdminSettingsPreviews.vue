<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
/* eslint-disable jsdoc/require-jsdoc */
import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, ref, watch } from 'vue'
import VueDraggable from 'vuedraggable'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcChip from '@nextcloud/vue/components/NcChip'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown.vue'
import ArrowUpIcon from 'vue-material-design-icons/ArrowUp.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import DragVerticalIcon from 'vue-material-design-icons/DragVertical.vue'
import logger from '../logger.ts'

interface PreviewProviderRow {
	class: string
	name: string
	mime: string
	requirement: string
	imagickFormat?: string | null
	sourceMimes?: string[]
	enabled: boolean
	available?: boolean
	unsupported?: boolean
}

interface PreviewsDetection {
	ffmpegFound?: boolean
	ffmpegDetectedPath?: string
	officeFound?: boolean
	imagick?: boolean
	cpuCount?: number
}

interface PreviewsSettings {
	enablePreviews: boolean
	previewMaxX: number | null
	previewMaxY: number | null
	previewMaxMemory: number | null
	previewMaxFilesizeImage: number | null
	jpegQuality: number | null
	webpQuality: number | null
	previewFormat: string
	previewConcurrencyNew: number | null
	previewConcurrencyAll: number | null
	previewExpirationDays: number
	imaginaryUrl: string
	imaginaryKey: string
	ffmpegPath: string
	ffprobePath: string
	libreofficePath: string
	providers: PreviewProviderRow[]
	defaultEnabledProviders: string[]
	defaultProviderOrder: string[]
	detection: PreviewsDetection
	configIsReadonly: boolean
}

interface SelectOption {
	id: string
	label: string
}

const productName = window.OC.theme.productName as string
const documentationLink = loadState<string>('settings', 'previewsDocumentation', '')
const initialSettings = loadState<Partial<PreviewsSettings>>('settings', 'previewsSettings', {})

const JPEG_QUALITY_PROVIDERS = new Set([
	'OC\\Preview\\JPEG',
	'OC\\Preview\\HEIC',
	'OC\\Preview\\Movie',
])
const WEBP_QUALITY_PROVIDERS = new Set([
	'OC\\Preview\\WebP',
])
const FORMAT_QUALITY_PROVIDERS = new Set([
	'OC\\Preview\\Imaginary',
])
const FORMAT_WEBP_ONLY_PROVIDERS = new Set([
	'OC\\Preview\\ImaginaryPDF',
])
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

function normalizeSettings(raw: Partial<PreviewsSettings>): PreviewsSettings {
	return {
		enablePreviews: raw.enablePreviews !== false,
		previewMaxX: raw.previewMaxX ?? null,
		previewMaxY: raw.previewMaxY ?? null,
		previewMaxMemory: raw.previewMaxMemory ?? null,
		previewMaxFilesizeImage: raw.previewMaxFilesizeImage ?? null,
		jpegQuality: raw.jpegQuality ?? null,
		webpQuality: raw.webpQuality ?? null,
		previewFormat: raw.previewFormat || 'jpeg',
		previewConcurrencyNew: raw.previewConcurrencyNew ?? null,
		previewConcurrencyAll: raw.previewConcurrencyAll ?? null,
		previewExpirationDays: raw.previewExpirationDays ?? 0,
		imaginaryUrl: raw.imaginaryUrl || '',
		imaginaryKey: raw.imaginaryKey || '',
		ffmpegPath: raw.ffmpegPath || '',
		ffprobePath: raw.ffprobePath || '',
		libreofficePath: raw.libreofficePath || '',
		providers: JSON.parse(JSON.stringify(raw.providers || [])),
		defaultEnabledProviders: raw.defaultEnabledProviders || [],
		defaultProviderOrder: raw.defaultProviderOrder || [],
		detection: raw.detection || {},
		configIsReadonly: !!raw.configIsReadonly,
	}
}

function ocsPayload<T>(response: { data?: { ocs?: { data?: T } } }): T | undefined {
	return response.data?.ocs?.data
}

function mergeProviderRows(current: PreviewProviderRow[], incoming?: PreviewProviderRow[]): PreviewProviderRow[] {
	if (!incoming?.length) {
		return current
	}
	const byClass = Object.fromEntries(incoming.map((row) => [row.class, row]))
	const seen = new Set<string>()
	const merged = current.map((row) => {
		seen.add(row.class)
		return byClass[row.class] ? { ...row, ...byClass[row.class] } : row
	})
	for (const row of incoming) {
		if (!seen.has(row.class)) {
			merged.push(row)
		}
	}
	return merged
}

function ocsErrorMessage(error: unknown, fallback: string): string {
	if (!isAxiosError(error)) {
		return fallback
	}
	const data = error.response?.data as { ocs?: { data?: { error?: string }, meta?: { message?: string } }, error?: string } | undefined
	return data?.ocs?.data?.error || data?.ocs?.meta?.message || data?.error || fallback
}

const settings = ref<PreviewsSettings>(normalizeSettings(initialSettings))
const saving = ref(false)
const testingImaginary = ref(false)
const imaginaryTestSuccess = ref('')
const imaginaryTestError = ref('')
const providerFilter = ref('all')
const mimeFilter = ref('')
const providersListKey = ref(0)

const formatOptions: SelectOption[] = [
	{ id: 'jpeg', label: 'JPEG' },
	{ id: 'webp', label: 'WebP' },
]
const statusFilterOptions: SelectOption[] = [
	{ id: 'all', label: t('settings', 'All') },
	{ id: 'available', label: t('settings', 'Available') },
	{ id: 'unavailable', label: t('settings', 'Unavailable') },
	{ id: 'supported', label: t('settings', 'Supported') },
	{ id: 'unsupported', label: t('settings', 'Unsupported') },
]

const settingsLocked = computed(() => settings.value.enablePreviews === false)
const controlsDisabled = computed(() => settings.value.configIsReadonly || saving.value)
const detection = computed(() => settings.value.detection || {})

function numericModel(key: 'previewMaxX' | 'previewMaxY' | 'previewMaxMemory' | 'previewMaxFilesizeImage' | 'jpegQuality' | 'webpQuality' | 'previewConcurrencyNew' | 'previewConcurrencyAll' | 'previewExpirationDays', allowEmpty: boolean) {
	return computed({
		get(): string {
			const value = settings.value[key]
			return value === null || value === undefined ? '' : String(value)
		},
		set(raw: string) {
			if (allowEmpty && raw === '') {
				(settings.value[key] as number | null) = null
				return
			}
			(settings.value[key] as number) = Number(raw)
		},
	})
}

const maxX = numericModel('previewMaxX', true)
const maxY = numericModel('previewMaxY', true)
const maxMemory = numericModel('previewMaxMemory', false)
const maxFilesize = numericModel('previewMaxFilesizeImage', false)
const jpegQuality = numericModel('jpegQuality', false)
const webpQuality = numericModel('webpQuality', false)
const concurrencyNew = numericModel('previewConcurrencyNew', true)
const concurrencyAll = numericModel('previewConcurrencyAll', true)
const expirationDays = numericModel('previewExpirationDays', false)

const filteredProviders = computed(() => (settings.value.providers || []).filter((provider) => providerMatchesStatus(provider) && providerMatchesMime(provider)))
const canReorderProviders = computed(() => providerFilter.value === 'all' && mimeFilter.value === '')
const mimeFilterOptions = computed<SelectOption[]>(() => {
	const mimes = new Set<string>()
	for (const provider of settings.value.providers || []) {
		for (const mime of provider.sourceMimes || []) {
			if (mime) {
				mimes.add(mime)
			}
		}
	}
	return [
		{ id: '', label: t('settings', 'All source MIME types') },
		...[...mimes].sort().map((mime) => ({ id: mime, label: mime })),
	]
})
const mimeFilterOption = computed({
	get(): SelectOption {
		return mimeFilterOptions.value.find((option) => option.id === mimeFilter.value) || mimeFilterOptions.value[0]
	},
	set(option: SelectOption | null) {
		mimeFilter.value = option?.id || ''
	},
})
const statusFilterOption = computed({
	get(): SelectOption {
		return statusFilterOptions.find((option) => option.id === providerFilter.value) || statusFilterOptions[0]
	},
	set(option: SelectOption | null) {
		providerFilter.value = option?.id || 'all'
	},
})
const orderedProviders = computed({
	get(): PreviewProviderRow[] {
		return filteredProviders.value
	},
	set(value: PreviewProviderRow[]) {
		if (!canReorderProviders.value) {
			return
		}
		settings.value.providers = value
	},
})
const previewFormatOption = computed({
	get(): SelectOption {
		return formatOptions.find((option) => option.id === settings.value.previewFormat) || formatOptions[0]
	},
	set(option: SelectOption | null) {
		if (!option) {
			return
		}
		settings.value.previewFormat = option.id
		savePartial({ previewFormat: option.id })
	},
})
const detectedCpuCount = computed(() => Number(detection.value.cpuCount) || 0)
const concurrencyHint = computed(() => {
	if (detectedCpuCount.value > 0) {
		return t('settings', 'This server reports {count} CPU cores.', { count: detectedCpuCount.value })
	}
	return t('settings', 'This server cannot detect the number of CPU cores and will assume there are 4.')
})
const effectiveNewConcurrency = computed(() => {
	if (settings.value.previewConcurrencyNew !== null && settings.value.previewConcurrencyNew !== undefined) {
		return Number(settings.value.previewConcurrencyNew)
	}
	return detectedCpuCount.value > 0 ? detectedCpuCount.value : 4
})
const newConcurrencyExceedsCpu = computed(() => {
	const value = settings.value.previewConcurrencyNew
	return detectedCpuCount.value > 0
		&& value !== null
		&& value !== undefined
		&& Number(value) > detectedCpuCount.value
})
const totalConcurrencyBelowNew = computed(() => {
	const value = settings.value.previewConcurrencyAll
	return detectedCpuCount.value > 0
		&& value !== null
		&& value !== undefined
		&& Number(value) < effectiveNewConcurrency.value
})
const visibleProviderFootnotes = computed(() => {
	const present = new Set<string>()
	for (const provider of filteredProviders.value) {
		if (provider.unsupported) {
			present.add('unsupported')
		}
		const footnote = providerOutputInfo(provider).footnote
		if (footnote) {
			present.add(footnote)
		}
	}
	const order = ['unsupported', 'imaginary-jpeg', 'mp3', 'legacy-image']
	const notes: { key: string, mark: string, text: string }[] = []
	for (const key of order) {
		if (!present.has(key)) {
			continue
		}
		notes.push({
			key,
			mark: String(notes.length + 1),
			text: providerFootnoteText(key),
		})
	}
	return notes
})

watch(() => settings.value.ffmpegPath, uncheckUnavailableProviders)
watch(() => settings.value.libreofficePath, uncheckUnavailableProviders)
watch(() => settings.value.imaginaryUrl, uncheckUnavailableProviders)

async function savePartial(partial: Record<string, unknown>): Promise<void> {
	if (settings.value.configIsReadonly) {
		return
	}
	saving.value = true
	try {
		await confirmPassword()
		const { data } = await axios.put(generateOcsUrl('/apps/settings/api/admin/previews'), { settings: partial })
		const next = ocsPayload<Partial<PreviewsSettings>>({ data })
		if (next) {
			settings.value = normalizeSettings({
				...settings.value,
				...next,
				providers: mergeProviderRows(settings.value.providers, next.providers),
			})
		}
	} catch (error) {
		logger.error('Could not save preview settings', { error })
		showError(ocsErrorMessage(error, t('settings', 'Failed to save preview settings')))
		throw error
	} finally {
		saving.value = false
	}
}

async function onEnablePreviews(value: boolean): Promise<void> {
	const previous = settings.value.enablePreviews
	settings.value.enablePreviews = value
	try {
		await savePartial({ enablePreviews: value })
	} catch {
		settings.value.enablePreviews = previous
	}
}

async function saveProviders(): Promise<void> {
	const enabledCount = (settings.value.providers || []).filter((provider) => provider.enabled).length
	if (enabledCount === 0 && !window.confirm(t('settings', 'Disabling every preview provider will break image previews. Continue?'))) {
		return
	}
	await savePartial({
		providers: settings.value.providers,
		confirmEmptyProviders: enabledCount === 0,
	})
}

async function saveTooling(partial: Record<string, unknown>): Promise<void> {
	uncheckUnavailableProviders()
	await savePartial({
		...partial,
		providers: settings.value.providers,
	})
}

function providerIndex(provider: PreviewProviderRow): number {
	return (settings.value.providers || []).findIndex((row) => row.class === provider.class)
}

function providerIsAvailable(provider: PreviewProviderRow): boolean {
	switch (provider.requirement) {
		case 'ffmpeg':
			return !!detection.value.ffmpegFound || !!(settings.value.ffmpegPath || '').trim()
		case 'office':
			return !!detection.value.officeFound || !!(settings.value.libreofficePath || '').trim()
		case 'imaginary':
			return !!(settings.value.imaginaryUrl || '').trim()
		default:
			return provider.available !== false
	}
}

function providerEnableLocked(provider: PreviewProviderRow): boolean {
	return controlsDisabled.value || (!providerIsAvailable(provider) && !provider.enabled)
}

function providerMatchesStatus(provider: PreviewProviderRow): boolean {
	switch (providerFilter.value) {
		case 'available':
			return providerIsAvailable(provider)
		case 'unavailable':
			return !providerIsAvailable(provider)
		case 'supported':
			return !provider.unsupported
		case 'unsupported':
			return !!provider.unsupported
		default:
			return true
	}
}

function providerMatchesMime(provider: PreviewProviderRow): boolean {
	if (!mimeFilter.value) {
		return true
	}
	return (provider.sourceMimes || []).some((token) => mimeTokenMatches(token, mimeFilter.value))
}

function mimeTokenMatches(token: string, selected: string): boolean {
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
}

async function moveProvider(index: number, delta: number): Promise<void> {
	const target = index + delta
	if (target < 0 || target >= settings.value.providers.length) {
		return
	}
	const copy = [...settings.value.providers]
	const [item] = copy.splice(index, 1)
	copy.splice(target, 0, item)
	settings.value.providers = copy
	await saveProviders()
}

function uncheckUnavailableProviders(): void {
	for (const provider of settings.value.providers || []) {
		if (provider.enabled && !providerIsAvailable(provider)) {
			provider.enabled = false
		}
	}
}

async function resetProviders(): Promise<void> {
	const enabledDefaults = settings.value.defaultEnabledProviders || []
	const enabledSet = new Set(enabledDefaults)
	const byClass = Object.fromEntries((settings.value.providers || []).map((provider) => [provider.class, provider]))
	const order = (settings.value.defaultProviderOrder && settings.value.defaultProviderOrder.length)
		? settings.value.defaultProviderOrder
		: [...enabledDefaults, ...(settings.value.providers || []).map((provider) => provider.class)]
	const next: PreviewProviderRow[] = []
	const seen = new Set<string>()
	for (const className of order) {
		if (seen.has(className)) {
			continue
		}
		const existing = byClass[className]
		if (!existing) {
			continue
		}
		next.push({
			...existing,
			enabled: enabledSet.has(className) && providerIsAvailable(existing),
		})
		seen.add(className)
	}
	for (const provider of settings.value.providers || []) {
		if (seen.has(provider.class)) {
			continue
		}
		next.push({ ...provider, enabled: false })
	}
	settings.value.providers = next
	providersListKey.value += 1
	await saveProviders()
}

async function testImaginary(): Promise<void> {
	testingImaginary.value = true
	imaginaryTestSuccess.value = ''
	imaginaryTestError.value = ''
	try {
		const { data } = await axios.post(generateOcsUrl('/apps/settings/api/admin/previews/imaginary/test'), {
			url: settings.value.imaginaryUrl,
			key: settings.value.imaginaryKey,
		})
		const result = ocsPayload<{ status?: string, error?: string }>({ data })
		if (result?.status === 'reachable') {
			imaginaryTestSuccess.value = t('settings', 'Imaginary is reachable.')
		} else {
			imaginaryTestError.value = result?.error || t('settings', 'Could not reach Imaginary')
		}
	} catch (error) {
		imaginaryTestError.value = ocsErrorMessage(error, t('settings', 'Could not reach Imaginary'))
	} finally {
		testingImaginary.value = false
	}
}

function providerOutputInfo(provider: PreviewProviderRow): { label: string, footnote: string | null } {
	const className = provider.class
	const format = settings.value.previewFormat || 'jpeg'
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
}

function providerFootnoteMark(key: string): string {
	const note = visibleProviderFootnotes.value.find((row) => row.key === key)
	return note ? note.mark : ''
}

function providerFootnoteText(key: string): string {
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
}

function providerUnsupportedMark(provider: PreviewProviderRow): string {
	return provider.unsupported ? providerFootnoteMark('unsupported') : ''
}

function providerFormatMark(provider: PreviewProviderRow): string {
	const footnote = providerOutputInfo(provider).footnote
	return footnote ? providerFootnoteMark(footnote) : ''
}

function providerAvailabilityVariant(provider: PreviewProviderRow): 'warning' | 'success' {
	if (!providerIsAvailable(provider) || provider.unsupported) {
		return 'warning'
	}
	return 'success'
}

function providerAvailabilityHref(provider: PreviewProviderRow): string {
	if (providerIsAvailable(provider)) {
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
}

function providerAvailabilityLinkLabel(provider: PreviewProviderRow): string {
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
}

function providerAvailabilityLabel(provider: PreviewProviderRow): string {
	if (!providerIsAvailable(provider)) {
		switch (provider.requirement) {
			case 'imagick':
				if (detection.value.imagick) {
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
}
</script>

<template>
	<div class="previews-admin">
		<NcNoteCard v-if="settings.configIsReadonly" type="warning">
			{{ t('settings', 'The config file is read-only. Preview settings cannot be saved from this page.') }}
		</NcNoteCard>

		<NcSettingsSection
			:name="t('settings', 'Previews')"
			:description="t('settings', 'Configure how {productName} generates and serves file previews.', { productName })"
			:doc-url="documentationLink">
			<NcFormBox>
				<NcCheckboxRadioSwitch
					:model-value="settings.enablePreviews"
					type="switch"
					aria-describedby="previews-enable-hint"
					aria-controls="previews-admin-dependent"
					:disabled="settings.configIsReadonly"
					@update:modelValue="onEnablePreviews">
					{{ t('settings', 'Enable previews') }}
				</NcCheckboxRadioSwitch>
				<p id="previews-enable-hint" class="previews-admin__hint">
					{{ t('settings', 'When disabled no file previews will be generated or served.') }}
				</p>
			</NcFormBox>
		</NcSettingsSection>

		<NcNoteCard v-if="settingsLocked" type="info">
			{{ t('settings', 'Preview generation is disabled.') }}
		</NcNoteCard>

		<div v-if="!settingsLocked" id="previews-admin-dependent">
			<NcSettingsSection
				:name="t('settings', 'Providers')"
				:description="t('settings', 'Enable providers and set their global priority. The order of enabled providers is the fallback order for all MIME types. Preview format is the type stored for that provider.')">
				<div class="previews-admin__toolbar">
					<div class="previews-admin__filters">
						<NcSelect
							v-model="statusFilterOption"
							class="previews-admin__status-filter"
							:options="statusFilterOptions"
							:input-label="t('settings', 'Status')" />
						<NcSelect
							v-model="mimeFilterOption"
							class="previews-admin__mime-filter"
							:options="mimeFilterOptions"
							:input-label="t('settings', 'Source MIME type')" />
					</div>
					<NcButton
						:disabled="controlsDisabled"
						@click="resetProviders">
						{{ t('settings', 'Reset to defaults') }}
					</NcButton>
				</div>
				<table class="previews-admin__table" :aria-label="t('settings', 'Preview providers')">
					<thead>
						<tr>
							<th scope="col">
								{{ t('settings', 'Provider') }}
							</th>
							<th scope="col">
								{{ t('settings', 'Class') }}
							</th>
							<th scope="col" class="previews-admin__mime">
								{{ t('settings', 'MIME') }}
							</th>
							<th scope="col">
								{{ t('settings', 'Preview format') }}
							</th>
							<th scope="col">
								{{ t('settings', 'Availability') }}
							</th>
							<th scope="col">
								{{ t('settings', 'Order') }}
							</th>
						</tr>
					</thead>
					<VueDraggable
						:key="providersListKey"
						v-model="orderedProviders"
						tag="tbody"
						handle=".previews-admin__drag-handle"
						:disabled="controlsDisabled || !canReorderProviders"
						@end="saveProviders">
						<tr
							v-for="provider in orderedProviders"
							:key="provider.class"
							:class="{ 'previews-admin__provider--unavailable': !providerIsAvailable(provider) }">
							<td>
								<NcCheckboxRadioSwitch
									v-model="provider.enabled"
									type="switch"
									:disabled="providerEnableLocked(provider)"
									:title="providerEnableLocked(provider) && !settings.configIsReadonly ? providerAvailabilityLabel(provider) : ''"
									@update:modelValue="saveProviders">
									{{ provider.name }}<sup
										v-if="providerUnsupportedMark(provider)"
										class="previews-admin__footnote-mark">{{ providerUnsupportedMark(provider) }}</sup>
								</NcCheckboxRadioSwitch>
							</td>
							<td>
								<code class="previews-admin__class">{{ provider.class }}</code>
							</td>
							<td class="previews-admin__mime">
								{{ provider.mime }}
							</td>
							<td class="previews-admin__output">
								{{ providerOutputInfo(provider).label }}<sup
									v-if="providerFormatMark(provider)"
									class="previews-admin__footnote-mark">{{ providerFormatMark(provider) }}</sup>
							</td>
							<td class="previews-admin__availability">
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
							</td>
							<td>
								<div class="previews-admin__order">
									<NcButton
										variant="tertiary"
										:aria-label="t('settings', 'Move up')"
										:disabled="controlsDisabled || providerIndex(provider) === 0"
										@click="moveProvider(providerIndex(provider), -1)">
										<template #icon>
											<ArrowUpIcon :size="20" />
										</template>
									</NcButton>
									<NcButton
										variant="tertiary"
										:aria-label="t('settings', 'Move down')"
										:disabled="controlsDisabled || providerIndex(provider) === settings.providers.length - 1"
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
							</td>
						</tr>
					</VueDraggable>
					<tfoot v-if="visibleProviderFootnotes.length">
						<tr>
							<td colspan="6">
								<ol class="previews-admin__footnotes">
									<li v-for="note in visibleProviderFootnotes" :key="note.key">
										{{ note.text }}
									</li>
								</ol>
							</td>
						</tr>
					</tfoot>
				</table>
			</NcSettingsSection>

			<NcSettingsSection
				:name="t('settings', 'General')"
				:description="t('settings', 'These limits apply to all preview generation.')">
				<NcFormBox>
					<NcTextField
						v-model="maxX"
						type="number"
						min="1"
						:label="t('settings', 'Maximum preview width (pixels)')"
						:helper-text="t('settings', 'Longest side of the full-size preview. Leave empty to use the default (4096).')"
						:disabled="controlsDisabled"
						@change="savePartial({ previewMaxX: settings.previewMaxX })" />
					<NcTextField
						v-model="maxY"
						type="number"
						min="1"
						:label="t('settings', 'Maximum preview height (pixels)')"
						:helper-text="t('settings', 'Longest side of the full-size preview. Leave empty to use the default (4096).')"
						:disabled="controlsDisabled"
						@change="savePartial({ previewMaxY: settings.previewMaxY })" />
					<NcTextField
						v-model="maxMemory"
						type="number"
						min="-1"
						:label="t('settings', 'Maximum memory (MB)')"
						:helper-text="t('settings', 'Skip imagegd previews that would allocate more than this. -1 means no memory cap. Default 256.')"
						:disabled="controlsDisabled"
						@change="savePartial({ previewMaxMemory: settings.previewMaxMemory })" />
					<NcTextField
						v-model="maxFilesize"
						type="number"
						min="-1"
						:label="t('settings', 'Maximum source image filesize (MB)')"
						:helper-text="t('settings', 'Skip local image previews above this size. -1 means unlimited. Default 50.')"
						:disabled="controlsDisabled"
						@change="savePartial({ previewMaxFilesizeImage: settings.previewMaxFilesizeImage })" />
					<NcTextField
						v-model="expirationDays"
						type="number"
						min="0"
						:label="t('settings', 'Expire generated previews after (days)')"
						:helper-text="t('settings', 'Daily job deletes stored preview files older than this. 0 disables file expiry. This does not delete user files.')"
						:disabled="controlsDisabled"
						@change="savePartial({ previewExpirationDays: settings.previewExpirationDays })" />
				</NcFormBox>
			</NcSettingsSection>

			<NcSettingsSection
				:name="t('settings', 'Preview quality')"
				:description="t('settings', 'JPEG and WebP quality apply when a preview is generated in that format. PNG previews are lossless, so they have no quality setting.')">
				<NcFormBox>
					<NcTextField
						v-model="jpegQuality"
						type="number"
						min="1"
						max="100"
						:label="t('settings', 'JPEG quality (1–100)')"
						:helper-text="t('settings', 'Higher values look better and use more disk. Default 80.')"
						:disabled="controlsDisabled"
						@change="savePartial({ jpegQuality: settings.jpegQuality })" />
					<NcTextField
						v-model="webpQuality"
						type="number"
						min="1"
						max="100"
						:label="t('settings', 'WebP quality (1–100)')"
						:helper-text="t('settings', 'Higher values look better and use more disk. Default 80.')"
						:disabled="controlsDisabled"
						@change="savePartial({ webpQuality: settings.webpQuality })" />
				</NcFormBox>
			</NcSettingsSection>

			<NcSettingsSection
				:name="t('settings', 'Performance')"
				:description="concurrencyHint">
				<NcFormBox>
					<NcTextField
						v-model="concurrencyNew"
						type="number"
						min="1"
						:label="t('settings', 'New preview concurrency')"
						:helper-text="t('settings', 'How many previews may be generated at once. Leave empty to use the CPU count. Do not set higher than the number of CPU cores.')"
						:disabled="controlsDisabled"
						@change="savePartial({ previewConcurrencyNew: settings.previewConcurrencyNew })" />
					<NcNoteCard v-if="newConcurrencyExceedsCpu" type="warning">
						{{ t('settings', 'New preview concurrency is higher than the {count} CPU cores detected on this server. Preview generation may overload the host.', { count: detectedCpuCount }) }}
					</NcNoteCard>
					<NcTextField
						v-model="concurrencyAll"
						type="number"
						min="1"
						:label="t('settings', 'Total preview concurrency')"
						:helper-text="t('settings', 'All preview requests, including cache hits. Leave empty to use twice the “New preview concurrency” limit. Should be greater than or equal to new preview concurrency.')"
						:disabled="controlsDisabled"
						@change="savePartial({ previewConcurrencyAll: settings.previewConcurrencyAll })" />
					<NcNoteCard v-if="totalConcurrencyBelowNew" type="warning">
						{{ t('settings', 'Total preview concurrency should be greater than or equal to new preview concurrency ({count}). Lower values are treated as equal to the new-preview limit.', { count: effectiveNewConcurrency }) }}
					</NcNoteCard>
				</NcFormBox>
			</NcSettingsSection>

			<div id="previews-section-imaginary" class="previews-admin__anchor">
				<NcSettingsSection
					:name="t('settings', 'Imaginary')"
					:description="t('settings', 'Use Imaginary to offload image processing. Put Imaginary above native HEIC in Providers so HEIC/HEIF files try Imaginary first.')">
					<NcNoteCard v-if="!settings.imaginaryUrl" type="warning">
						{{ t('settings', 'Not configured yet. Set a URL so Imaginary providers can generate previews.') }}
					</NcNoteCard>
					<NcFormBox>
						<NcTextField
							v-model="settings.imaginaryUrl"
							:label="t('settings', 'Imaginary URL')"
							placeholder="http://imaginary:9000"
							:helper-text="t('settings', 'HTTP(S) URL of the Imaginary service. Required before the Imaginary providers can generate previews.')"
							:disabled="controlsDisabled"
							@change="saveTooling({ imaginaryUrl: settings.imaginaryUrl })" />
						<NcPasswordField
							v-model="settings.imaginaryKey"
							:label="t('settings', 'Imaginary API key (optional)')"
							:disabled="controlsDisabled"
							@change="savePartial({ imaginaryKey: settings.imaginaryKey })" />
						<p class="previews-admin__hint">
							{{ t('settings', 'Sent as the key query parameter. Leave empty if Imaginary does not require a key.') }}
						</p>
						<NcSelect
							v-model="previewFormatOption"
							:options="formatOptions"
							:input-label="t('settings', 'Preview output format')"
							:disabled="controlsDisabled" />
						<p class="previews-admin__hint">
							{{ t('settings', 'Only Imaginary uses this setting. Other providers usually keep the source image type (JPEG stays JPEG, PNG stays PNG). One file is stored per preview size.') }}
						</p>
					</NcFormBox>
					<div class="previews-admin__test">
						<NcButton :disabled="testingImaginary || !settings.imaginaryUrl || controlsDisabled" @click="testImaginary">
							{{ t('settings', 'Test connection') }}
						</NcButton>
						<NcNoteCard v-if="imaginaryTestSuccess" type="success">
							<div class="previews-admin__banner">
								<p>{{ imaginaryTestSuccess }}</p>
								<NcButton
									variant="tertiary"
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
									variant="tertiary"
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
			</div>

			<div id="previews-section-movie" class="previews-admin__anchor">
				<NcSettingsSection
					:name="t('settings', 'Movie')"
					:description="t('settings', 'Generate video previews with ffmpeg.')">
					<NcNoteCard v-if="detection.ffmpegFound" type="success">
						{{ t('settings', 'ffmpeg found: {path}', { path: detection.ffmpegDetectedPath }) }}
					</NcNoteCard>
					<NcNoteCard v-else type="warning">
						{{ t('settings', 'Not detected. Set a path to ffmpeg or install it so the Movie provider can generate video previews.') }}
					</NcNoteCard>
					<NcFormBox>
						<NcTextField
							v-model="settings.ffmpegPath"
							:label="t('settings', 'ffmpeg path')"
							:helper-text="t('settings', 'Custom path to ffmpeg for video previews. Empty uses the server PATH.')"
							:disabled="controlsDisabled"
							@change="saveTooling({ ffmpegPath: settings.ffmpegPath })" />
						<NcTextField
							v-model="settings.ffprobePath"
							:label="t('settings', 'ffprobe path')"
							:helper-text="t('settings', 'Used for HDR video metadata. Empty uses the same directory as ffmpeg.')"
							:disabled="controlsDisabled"
							@change="savePartial({ ffprobePath: settings.ffprobePath })" />
					</NcFormBox>
				</NcSettingsSection>
			</div>

			<div id="previews-section-office" class="previews-admin__anchor">
				<NcSettingsSection
					:name="t('settings', 'Office')"
					:description="t('settings', 'Generate previews for office documents (Microsoft Office, Open Document, StarOffice, and EMF) with LibreOffice or OpenOffice. Leave the path empty to search PATH.')">
					<NcNoteCard v-if="!detection.officeFound" type="warning">
						{{ t('settings', 'Not detected. Set a path to LibreOffice or OpenOffice, or install it, so office providers can generate document previews.') }}
					</NcNoteCard>
					<NcFormBox>
						<NcTextField
							v-model="settings.libreofficePath"
							:label="t('settings', 'LibreOffice path')"
							:helper-text="t('settings', 'Custom path to LibreOffice or OpenOffice. Empty searches for libreoffice, then openoffice.')"
							:disabled="controlsDisabled"
							@change="saveTooling({ libreofficePath: settings.libreofficePath })" />
					</NcFormBox>
				</NcSettingsSection>
			</div>
		</div>
	</div>
</template>

<style scoped>
.previews-admin {
	padding-block-end: calc(var(--default-grid-baseline) * 10);
}

.previews-admin__anchor {
	scroll-margin-block-start: calc(var(--header-height, 50px) + var(--body-container-margin, 0px) + 12px);
}

.previews-admin__hint {
	color: var(--color-text-maxcontrast);
	margin-block: 4px 12px;
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

.previews-admin__table {
	width: 100%;
	margin-block: 12px;
	border-collapse: collapse;
}

.previews-admin__table th,
.previews-admin__table td {
	text-align: start;
	padding: 6px 8px;
	vertical-align: middle;
	border-bottom: 1px solid var(--color-border);
	white-space: nowrap;
}

.previews-admin__table th {
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
}

.previews-admin__table td.previews-admin__mime,
.previews-admin__table td.previews-admin__output {
	overflow-wrap: anywhere;
	word-break: break-word;
	white-space: normal;
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
	justify-content: flex-end;
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

.previews-admin__footnotes {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
	margin: 8px 0 0;
	padding-inline-start: 1.25em;
}

.previews-admin__footnotes li {
	margin-block: 4px;
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
