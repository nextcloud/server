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
						</h3>
						<IconCheck v-if="selectedSuite === suite.id" class="office-suite-card__check" :size="24" />
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
		</template>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import { OFFICE_SUITES } from '../../constants/OfficeSuites.js'

export default {
	name: 'OfficeSuiteSwitcher',

	components: {
		IconCheck,
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
				this.selectedSuite = null
				this.$emit('suite-selected', null)
			} else {
				this.selectedSuite = suiteId
				this.$emit('suite-selected', suiteId)
			}
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

	&:hover {
		border-color: var(--color-primary-element);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	&--selected {
		background: linear-gradient(135deg, var(--color-primary-element) 0%, var(--color-primary-element-light) 200%);
		color: var(--color-primary-element-text);
		border-color: var(--color-primary-element);

		.office-suite-card__title,
		.office-suite-card__features,
		.office-suite-card__link {
			color: var(--color-primary-element-text);
		}
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

	&__check {
		color: var(--color-success);
		flex-shrink: 0;
	}

	.office-suite-card--primary &__check {
		color: var(--color-primary-element-text);
	}

	&__features {
		list-style: none;
		padding: 0;
		margin: 0 0 20px 0;
		flex-grow: 1;

		li {
			padding: 8px 0;
			padding-left: 20px;
			position: relative;
			line-height: 1.5;

			&::before {
				content: '•';
				position: absolute;
				left: 0;
				font-weight: bold;
			}
		}
	}

	&__link {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		color: var(--color-primary-element);
		text-decoration: none;
		font-weight: 500;
		margin-top: auto;

		&:hover {
			text-decoration: underline;
		}
	}

	.office-suite-card--selected &__link {
		color: var(--color-primary-element-text);
	}
}

@media (max-width: 768px) {
	.office-suite-cards {
		flex-direction: column;
	}
}
</style>
