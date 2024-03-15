<template>
	<div id="workflowengine">
		<NcSettingsSection :name="t('workflowengine', 'Available flows')"
			:doc-url="workflowDocUrl">
			<p v-if="isAdminScope" class="settings-hint">
				<a href="https://nextcloud.com/developer/">{{ t('workflowengine', 'For details on how to write your own flow, check out the development documentation.') }}</a>
			</p>

			<NcEmptyContent v-if="!isUserAdmin && mainOperations.length === 0"
				:name="t('workflowengine', 'No flows installed')"
				:description="!isUserAdmin ? t('workflowengine', 'Ask your administrator to install new flows.') : undefined">
				<template #icon>
					<NcIconSvgWrapper :svg="WorkflowOffSvg" :size="20" />
				</template>
			</NcEmptyContent>
			<transition-group v-else
				name="slide"
				tag="div"
				class="actions">
				<Operation v-for="operation in mainOperations"
					:key="operation.id"
					:operation="operation"
					@click.native="createNewRule(operation)" />
				<a v-if="showAppStoreHint"
					key="add"
					:href="appstoreUrl"
					class="actions__item colored more">
					<div class="icon icon-add" />
					<div class="actions__item__description">
						<h3>{{ t('workflowengine', 'More flows') }}</h3>
						<small>{{ t('workflowengine', 'Browse the App Store') }}</small>
					</div>
				</a>
			</transition-group>

			<div v-if="hasMoreOperations" class="actions__more">
				<NcButton @click="showMoreOperations = !showMoreOperations">
					<template #icon>
						<MenuUp v-if="showMoreOperations" :size="20" />
						<MenuDown v-else :size="20" />
					</template>
					{{ showMoreOperations ? t('workflowengine', 'Show less') : t('workflowengine', 'Show more') }}
				</NcButton>
			</div>
		</NcSettingsSection>

		<NcSettingsSection v-if="mainOperations.length > 0"
			:name="isAdminScope ? t('workflowengine', 'Configured flows') : t('workflowengine', 'Your flows')">
			<transition-group v-if="rules.length > 0" name="slide">
				<Rule v-for="rule in rules" :key="rule.id" :rule="rule" />
			</transition-group>
			<NcEmptyContent v-else :name="t('workflowengine', 'No flows configured')">
				<template #icon>
					<NcIconSvgWrapper :svg="WorkflowOffSvg" :size="20" />
				</template>
			</NcEmptyContent>
		</NcSettingsSection>
	</div>
</template>

<script>
import Rule from './Rule.vue'
import Operation from './Operation.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import { mapGetters, mapState } from 'vuex'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import MenuUp from 'vue-material-design-icons/MenuUp.vue'
import MenuDown from 'vue-material-design-icons/MenuDown.vue'
import WorkflowOffSvg from '../../img/workflow-off.svg?raw'

const ACTION_LIMIT = 3
const ADMIN_SCOPE = 0
// const PERSONAL_SCOPE = 1

export default {
	name: 'Workflow',
	components: {
		MenuDown,
		MenuUp,
		NcButton,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcSettingsSection,
		Operation,
		Rule,
	},
	data() {
		return {
			showMoreOperations: false,
			appstoreUrl: generateUrl('settings/apps/workflow'),
			workflowDocUrl: loadState('workflowengine', 'doc-url'),
			WorkflowOffSvg,
		}
	},
	computed: {
		...mapGetters({
			rules: 'getRules',
		}),
		...mapState({
			appstoreEnabled: 'appstoreEnabled',
			scope: 'scope',
			operations: 'operations',
		}),
		hasMoreOperations() {
			return Object.keys(this.operations).length > ACTION_LIMIT
		},
		mainOperations() {
			if (this.showMoreOperations) {
				return Object.values(this.operations)
			}
			return Object.values(this.operations).slice(0, ACTION_LIMIT)
		},
		showAppStoreHint() {
			return this.appstoreEnabled && OC.isUserAdmin()
		},
		isUserAdmin() {
			return OC.isUserAdmin()
		},
		isAdminScope() {
			return this.scope === ADMIN_SCOPE
		},
	},
	mounted() {
		this.$store.dispatch('fetchRules')
	},
	methods: {
		createNewRule(operation) {
			this.$store.dispatch('createNewRule', operation)
		},
	},
}
</script>

<style scoped lang="scss">
	#workflowengine {
		border-bottom: 1px solid var(--color-border);
	}
	.section {
		max-width: 100vw;

		h2.configured-flows {
			margin-top: 50px;
			margin-bottom: 0;
		}
	}
	.actions {
		display: flex;
		flex-wrap: wrap;
		max-width: 1200px;
		.actions__item {
			max-width: 280px;
			flex-basis: 250px;
		}
	}
	.actions__more {
		margin-bottom: 10px;
	}

	.slide-enter-active {
		-moz-transition-duration: 0.3s;
		-webkit-transition-duration: 0.3s;
		-o-transition-duration: 0.3s;
		transition-duration: 0.3s;
		-moz-transition-timing-function: ease-in;
		-webkit-transition-timing-function: ease-in;
		-o-transition-timing-function: ease-in;
		transition-timing-function: ease-in;
	}

	.slide-leave-active {
		-moz-transition-duration: 0.3s;
		-webkit-transition-duration: 0.3s;
		-o-transition-duration: 0.3s;
		transition-duration: 0.3s;
		-moz-transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
		-webkit-transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
		-o-transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
		transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
	}

	.slide-enter-to, .slide-leave {
		max-height: 500px;
		overflow: hidden;
	}

	.slide-enter, .slide-leave-to {
		overflow: hidden;
		max-height: 0;
		padding-top: 0;
		padding-bottom: 0;
	}

	@import "./../styles/operation";

	.actions__item.more {
		background-color: var(--color-background-dark);
	}
</style>
