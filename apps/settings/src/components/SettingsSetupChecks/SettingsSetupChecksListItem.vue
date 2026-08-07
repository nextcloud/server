<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ISetupCheck } from '../../settings-types.ts'

import { mdiAlert, mdiClose, mdiInformation } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcRichText from '@nextcloud/vue/components/NcRichText'
import { useRichArguments } from '../../composables/useRichArguments.ts'

const props = defineProps<{
	setupCheck: ISetupCheck
}>()

const showDetails = ref(false)
const hasDetails = computed(() => props.setupCheck.description.includes('\n\n'))

const leadingIcon = computed(() => {
	if (props.setupCheck.severity === 'error') {
		return mdiClose
	} else if (props.setupCheck.severity === 'warning') {
		return mdiAlert
	}
	return mdiInformation
})

const richObjects = computed(() => props.setupCheck.descriptionParameters ?? {})
const richArguments = useRichArguments(richObjects)

const richText = computed(() => {
	if (showDetails.value) {
		return props.setupCheck.description
	}
	const firstParagraph = props.setupCheck.description.split('\n\n')[0]
	return firstParagraph
})
</script>

<template>
	<li
		class="settings-setup-checks-item"
		:class="{
			[`settings-setup-checks-item--${setupCheck.severity}`]: true,
		}">
		<NcIconSvgWrapper class="settings-setup-checks-item__icon" :path="leadingIcon" />
		<div class="settings-setup-checks-item__wrapper">
			<div class="settings-setup-checks-item__header">
				<div class="settings-setup-checks-item__name">
					{{ setupCheck.name }}
				</div>
				<NcButton v-if="hasDetails" @click="showDetails = !showDetails">
					{{ showDetails ? t('settings', 'Hide details') : t('settings', 'Show details') }}
				</NcButton>
			</div>
			<NcRichText
				class="settings-setup-checks-item__description"
				:arguments="richArguments"
				:text="richText"
				use-markdown />
		</div>
	</li>
</template>

<style scope lang="scss">
.settings-setup-checks-item {
	border-radius: var(--border-radius-element);
	display: flex;
	align-items: start;
	flex-direction: row;

	&:hover {
		background-color: var(--color-background-hover);
	}

	&__wrapper {
		display: flex;
		flex-direction: column;
		// align with icon
		padding-top: calc((var(--default-clickable-area) - 1lh) / 2);
		width: 100%;
	}

	&__header {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		gap: var(--default-grid-baseline);
	}

	&__description {
		color: var(--color-text-maxcontrast);
	}

	&__icon {
		border-radius: calc(var(--default-clickable-area) / 2);
	}

	&--error &__icon {
		color: var(--color-element-error);
	}
	&--warning &__icon {
		color: var(--color-element-warning);
	}
	&--info &__icon {
		color: var(--color-element-info);
	}
}
</style>
