<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="office-suite-switcher">
		<div v-if="isAllInOne" class="office-suite-switcher__aio-message">
			<p>{{ t('settings', 'Office suite switching is managed through the Nextcloud All-in-One interface.') }}</p>
			<p>{{ t('settings', 'Please use the AIO interface to switch between office suites.') }}</p>
		</div>
		<template v-else>
			<p>{{ t('settings', 'Select your preferred office suite. Please note that installing requires manual server setup.') }}</p>
			<div class="office-suite-cards">
				<div
					v-for="suite in officeSuites"
					:key="suite.id"
					class="office-suite-card"
					:class="{
						'office-suite-card--primary': suite.isPrimary,
						'office-suite-card--selected': selectedSuite === suite.id,
					}"
					@click="selectSuite(suite.id)">
					<div class="office-suite-card__header">
						<h3 class="office-suite-card__title">
							{{ suite.name }}
							<span v-if="selectedSuite === suite.id">({{ t('settings', 'installed') }})</span>
						</h3>
						<IconCheckCircle v-if="selectedSuite === suite.id" class="office-suite-card__check" :size="24" />
					</div>
					<ul class="office-suite-card__features">
						<li v-for="(feature, index) in suite.features" :key="index">
							{{ t('settings', feature) }}
						</li>
					</ul>
					<a
						:href="suite.learnMoreUrl"
						target="_blank"
						rel="noopener noreferrer"
						class="office-suite-card__link"
						@click.stop>
						{{ t('settings', 'Learn more') }}
						<IconArrowRight :size="20" />
					</a>
				</div>
			</div>
			<div class="office-suite-actions">
				<button
					class="office-suite-disable-button"
					:disabled="!selectedSuite"
					@click="disableSuites">
					{{ t('settings', 'Disable office suites') }}
				</button>
			</div>
		</template>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import IconCheckCircle from 'vue-material-design-icons/CheckCircle.vue'
import { OFFICE_SUITES } from '../../constants/OfficeSuites.js'

export default {
	name: 'OfficeSuiteSwitcher',

	components: {
		IconCheckCircle,
		IconArrowRight,
	},

	props: {
		installedApps: {
			type: Array,
			default: () => [],
		},
	},

	emits: ['suite-selected'],

	data() {
		return {
			isAllInOne: loadState('settings', 'isAllInOne', false),
			selectedSuite: this.getInitialSuite(),
			officeSuites: OFFICE_SUITES,
		}
	},

	methods: {
		t,
		getInitialSuite() {
			for (const suite of OFFICE_SUITES) {
				const app = this.installedApps.find((a) => a.id === suite.appId)
				if (app && app.active) {
					return suite.id
				}
			}

			return null
		},

		selectSuite(suiteId) {
			if (this.selectedSuite === suiteId) {
				// already selected — keep selection; use the disable button to clear
				return
			}
			this.selectedSuite = suiteId
			this.$emit('suite-selected', suiteId)
		},

		disableSuites() {
			if (this.selectedSuite === null) {
				return
			}
			this.selectedSuite = null
			this.$emit('suite-selected', null)
		},
	},
}
</script>

<style lang="scss" scoped>
.office-suite-switcher {
	padding: 20px;
	margin-bottom: 30px;

	&__aio-message {
		background-color: var(--color-background-dark);
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius-large);
		padding: 20px;
		text-align: center;
	}

    p {
        margin: 8px 0;

        &:first-child {
            font-weight: 600;
        }
    }
}

.office-suite-cards {
	display: flex;
	gap: 20px;
	max-width: 1200px;
}

.office-suite-card {
	flex: 1;
	background-color: var(--color-main-background);
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 24px;
	cursor: pointer;
	transition: all 0.2s ease;
	display: flex;
	flex-direction: column;

	& * {
		cursor: pointer;
	}

	&:hover {
		border-color: var(--color-primary-element);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	&--selected {
		background: linear-gradient(135deg, var(--color-primary-element-light) 0%, var(--color-main-background) 100%);
		color: var(--color-main-text);
		border-color: var(--color-primary-element);
	}

	&__header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 16px;
	}

	&__title {
		font-size: 24px;
		font-weight: 600;
		margin: 0;
	}

	.office-suite-card--primary &__check {
		color: var(--color-primary-element);
	}

	&__features {
		list-style: none;
		padding: 0;
		margin: 0 0 20px 0;
		flex-grow: 1;

		li {
			padding: 4px 0;
			padding-inline-start: 20px;
			position: relative;
			line-height: 1.5;

			&::before {
				content: '•';
				position: absolute;
				inset-inline-start: 0;
				font-weight: bold;
			}
		}
	}

	&__link {
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

	.office-suite-card--selected &__link {
		color: var(--color-main-text);
	}
}

.office-suite-actions {
	margin-top: 16px;
}

.office-suite-disable-button {
	background: transparent;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-small);
	padding: 8px 12px;
	font-weight: 600;
	color: var(--color-main-text);
	cursor: pointer;
	transition: background 0.15s ease, border-color 0.15s ease;
}

.office-suite-disable-button:disabled {
	opacity: 0.5;
	cursor: default;
}

.office-suite-disable-button:hover:not(:disabled) {
	border-color: var(--color-primary-element);
	background: var(--color-background-dark);
}

@media (max-width: 768px) {
	.office-suite-cards {
		flex-direction: column;
	}
}
</style>
