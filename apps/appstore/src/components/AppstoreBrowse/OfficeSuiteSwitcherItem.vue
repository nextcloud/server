<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { OFFICE_SUITES } from '../../service/OfficeSuites.ts'

import { t } from '@nextcloud/l10n'
import { computed, useId } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { useAppsStore } from '../../store/apps.ts'
import { canInstall } from '../../utils/appStatus.ts'

const selectedSuiteId = defineModel<string | null>('selected')

const { suite } = defineProps<{
	suite: typeof OFFICE_SUITES[number]
	loading?: boolean
}>()

const headerId = useId()
const store = useAppsStore()

const app = computed(() => store.getAppById(suite.appId))
const isInstalled = computed(() => !!app.value?.installed)
const cannotInstall = computed(() => !app.value || (!isInstalled.value && !canInstall(app.value!)))
</script>

<template>
	<div
		:class="[$style.officeSuiteSwitcherItem, {
			[$style.officeSuiteSwitcherItem_selected]: selectedSuiteId === suite.id,
		}]"
		@click="selectedSuiteId = suite.id">
		<div :class="$style.officeSuiteSwitcherItem__header">
			<h3 :id="headerId" :class="$style.officeSuiteSwitcherItem__title">
				{{ suite.name }}
				<span v-if="isInstalled">({{ t('appstore', 'installed') }})</span>
			</h3>
			<NcCheckboxRadioSwitch
				v-model="selectedSuiteId"
				:aria-labelledby="headerId"
				:disabled="cannotInstall"
				:loading="loading"
				type="radio"
				name="office-suite"
				:value="suite.id"
				@click.stop />
		</div>
		<ul :aria-label="t('appstore', 'Features')" :class="$style.officeSuiteSwitcherItem__features">
			<li v-for="(feature, index) in suite.features" :key="index">
				{{ feature }}
			</li>
		</ul>
		<NcButton :href="suite.learnMoreUrl" @click.stop>
			{{ t('appstore', 'Learn more') }}↗
		</NcButton>
	</div>
</template>

<style module>
.officeSuiteSwitcherItem {
	flex: 1;
	background-color: var(--color-main-background);
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 24px;
	cursor: pointer;
	transition: all 0.2s ease;
	display: flex;
	flex-direction: column;

	* {
		cursor: pointer;
	}

	&:hover {
		border-color: var(--color-primary-element);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}
}

.officeSuiteSwitcherItem_selected {
	background: linear-gradient(135deg, var(--color-primary-element-light) 0%, var(--color-main-background) 100%);
	color: var(--color-main-text);
	border-color: var(--color-primary-element);
}

.officeSuiteSwitcherItem__header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16px;
}

.officeSuiteSwitcherItem__title {
	font-size: 24px;
	font-weight: 600;
	margin: 0;
}

.officeSuiteSwitcherItem__features {
	list-style: disc;
	padding: 0;
	margin: 0 0 1em 0;
	flex-grow: 1;

	li {
		padding-block: var(--default-grid-baseline) 0;
		padding-inline-start: 1em;
		line-height: 1.5;
	}
}

.officeSuiteSwitcherItem__link {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	color: var(--color-main-text);
	text-decoration: none;
	font-weight: 500;
	margin-top: auto;

	&:hover {
		text-decoration: underline;
	}
}

.officeSuiteSwitcherItem_selected .officeSuiteSwitcherItem__link {
	color: var(--color-main-text);
}
</style>
