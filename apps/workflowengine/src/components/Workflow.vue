<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="$style.workflowengine">
		<NcSettingsSection
			:docUrl="workflowDocUrl"
			:name="t('workflowengine', 'Available flows')">
			<p v-if="isAdminScope" class="settings-hint">
				<a href="https://nextcloud.com/developer/">{{ t('workflowengine', 'For details on how to write your own flow, check out the development documentation.') }}</a>
			</p>

			<NcEmptyContent
				v-if="!isUserAdmin && mainOperations.length === 0"
				:description="t('workflowengine', 'Ask your administrator to install new flows.')"
				:name="t('workflowengine', 'No flows installed')">
				<template #icon>
					<NcIconSvgWrapper :svg="WorkflowOffSvg" :size="20" />
				</template>
			</NcEmptyContent>
			<TransitionGroup
				v-else
				v-bind="slideTransition"
				:class="$style.actions"
				tag="div">
				<Operation
					v-for="operation in mainOperations"
					:key="operation.id"
					:class="$style.card"
					:operation="operation"
					colored
					@click="createNewRule(operation)" />
				<a
					v-if="showAppStoreHint"
					key="add"
					:class="[$style.card, $style.more]"
					:href="appstoreUrl">
					<NcIconSvgWrapper :class="$style.moreIcon" :path="mdiPlus" :size="50" />
					<div>
						<h3>{{ t('workflowengine', 'More flows') }}</h3>
						<small>{{ t('workflowengine', 'Browse the App Store') }}</small>
					</div>
				</a>
			</TransitionGroup>

			<div v-if="hasMoreOperations" :class="$style.showMore">
				<NcButton @click="showMoreOperations = !showMoreOperations">
					<template #icon>
						<NcIconSvgWrapper :path="showMoreOperations ? mdiMenuUp : mdiMenuDown" :size="20" />
					</template>
					{{ showMoreOperations ? t('workflowengine', 'Show less') : t('workflowengine', 'Show more') }}
				</NcButton>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			v-if="mainOperations.length > 0"
			:name="isAdminScope ? t('workflowengine', 'Configured flows') : t('workflowengine', 'Your flows')">
			<TransitionGroup v-if="rules.length > 0" v-bind="slideTransition">
				<Rule v-for="rule in rules" :key="rule.id" :rule="rule" />
			</TransitionGroup>
			<NcEmptyContent v-else :name="t('workflowengine', 'No flows configured')">
				<template #icon>
					<NcIconSvgWrapper :svg="WorkflowOffSvg" :size="20" />
				</template>
			</NcEmptyContent>
		</NcSettingsSection>
	</div>
</template>

<script setup lang="ts">
/* eslint vue/multi-word-component-names: "warn" */

import type { OperatorPlugin } from '../types.ts'

import { mdiMenuDown, mdiMenuUp, mdiPlus } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, onMounted, ref, useCssModule } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import Operation from './Operation.vue'
import Rule from './Rule.vue'
import WorkflowOffSvg from '../../img/workflow-off.svg?raw'
import { useWorkflowStore } from '../store.ts'
import { Scope } from '../types.ts'

const ACTION_LIMIT = 3

const store = useWorkflowStore()
const style = useCssModule()

const showMoreOperations = ref(false)
const appstoreUrl = generateUrl('settings/apps/workflow')
const workflowDocUrl = loadState<string>('workflowengine', 'doc-url')

const isUserAdmin = Boolean(getCurrentUser()?.isAdmin)

const rules = computed(() => store.configuredRules)
const operations = computed(() => store.operations)

const hasMoreOperations = computed(() => Object.keys(operations.value).length > ACTION_LIMIT)

const mainOperations = computed(() => {
	const all = Object.values(operations.value)
	return showMoreOperations.value ? all : all.slice(0, ACTION_LIMIT)
})

const isAdminScope = computed(() => store.scope === Scope.ADMIN)
const showAppStoreHint = computed(() => store.appstoreEnabled && isUserAdmin)

// transition classes have to be passed explicitly, css modules rename them
const slideTransition = {
	enterActiveClass: style.slideEnterActive,
	leaveActiveClass: style.slideLeaveActive,
	enterFromClass: style.slideEnterFrom,
	enterToClass: style.slideEnterTo,
	leaveFromClass: style.slideLeaveFrom,
	leaveToClass: style.slideLeaveTo,
}

/**
 * @param operation - The operation to configure a new flow for
 */
function createNewRule(operation: OperatorPlugin): void {
	store.createNewRule(operation)
}

onMounted(() => {
	store.fetchRules()
})
</script>

<style module lang="scss">
@use "./../styles/operation.scss" as *;

.workflowengine {
	border-bottom: 1px solid var(--color-border);
}

.actions {
	display: flex;
	flex-wrap: wrap;
	max-width: 1200px;
}

.card {
	max-width: 280px;
	flex-basis: 250px;
}

.more {
	background-color: var(--color-background-dark);
	text-align: center;
}

.moreIcon {
	margin-block: 10px;
}

.showMore {
	margin-bottom: 10px;
}

.slideEnterActive {
	transition-duration: 0.3s;
	transition-timing-function: ease-in;
}

.slideLeaveActive {
	transition-duration: 0.3s;
	transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
}

.slideEnterTo,
.slideLeaveFrom {
	max-height: 500px;
	overflow: hidden;
}

.slideEnterFrom,
.slideLeaveTo {
	overflow: hidden;
	max-height: 0;
	padding-top: 0;
	padding-bottom: 0;
}
</style>
