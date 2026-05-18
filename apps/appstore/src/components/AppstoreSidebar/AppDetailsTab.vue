<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../../apps.d.ts'

import { mdiTextBoxOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, useId } from 'vue'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import BadgeAppDaemon from '../BadgeAppDaemon.vue'
import BadgeAppLevel from '../BadgeAppLevel.vue'
import BadgeAppScore from '../BadgeAppScore.vue'
import { useLimitedGroups } from '../../composables/useLimitedGroups.ts'
import { useAppsStore } from '../../store/apps.ts'

const { app } = defineProps<{ app: IAppstoreApp | IAppstoreExApp }>()

const store = useAppsStore()

const idLimitedToGroups = useId()

const lastModified = computed(() => app.releases
	?.map((release) => release.lastModified)
	.map((date) => Date.parse(date))
	.sort()
	.at(-1))

/**
 * App authors as comma separated string
 */
const appAuthors = computed(() => {
	if (!app) {
		return ''
	}

	return [app.author].flat().map(authorName)
		.sort((a, b) => a.split(' ').at(-1)!.localeCompare(b.split(' ').at(-1)!))
		.join(', ')
})

const appstoreUrl = computed(() => `https://apps.nextcloud.com/apps/${app.id}`)
const groupsAppIsLimitedTo = useLimitedGroups(() => app)

/**
 * Further external resources (e.g. website)
 */
const externalResources = computed(() => {
	const resources: { id: string, href: string, label: string }[] = []
	if (!app.internal) {
		resources.push({
			id: 'appstore',
			href: appstoreUrl.value,
			label: t('appstore', 'View in store'),
		})
	}
	if (app.website) {
		resources.push({
			id: 'website',
			href: app.website,
			label: t('appstore', 'Visit website'),
		})
	}
	if (app.documentation) {
		if (app.documentation.user) {
			resources.push({
				id: 'doc-user',
				href: app.documentation.user,
				label: t('appstore', 'Usage documentation'),
			})
		}
		if (app.documentation.admin) {
			resources.push({
				id: 'doc-admin',
				href: app.documentation.admin,
				label: t('appstore', 'Admin documentation'),
			})
		}
		if (app.documentation.developer) {
			resources.push({
				id: 'doc-developer',
				href: app.documentation.developer,
				label: t('appstore', 'Developer documentation'),
			})
		}
	}
	return resources
})

const appCategories = computed(() => {
	return [app.category].flat()
		.map((id) => store.getCategoryById(id)?.displayName ?? id)
		.join(', ')
})

/**
 * Get the author name from the XML node
 *
 * @param xmlNode - The XML node to get the author name from
 */
function authorName(xmlNode): string {
	if (xmlNode['@value']) {
		// Complex node (with email or homepage attribute)
		return xmlNode['@value']
	}
	// Simple text node
	return xmlNode
}
</script>

<template>
	<NcAppSidebarTab
		id="details"
		:name="t('appstore', 'Details')"
		:order="1">
		<template #icon>
			<NcIconSvgWrapper :path="mdiTextBoxOutline" />
		</template>
		<div class="app-details">
			<!-- Featured/Supported badges -->
			<div :class="$style.appstoreDetailsTab__badges">
				<BadgeAppLevel :level="app.level" />
				<BadgeAppDaemon v-if="app.app_api && app.daemon" :daemon="app.daemon" />
				<BadgeAppScore :app />
			</div>

			<NcNoteCard v-if="!app.isCompatible && app.missingDependencies && app.missingDependencies.length" type="error">
				{{ t('appstore', 'This app cannot be installed because the following dependencies are not fulfilled:') }}
				<ul :aria-label="t('appstore', 'Missing dependencies')" :class="$style.appstoreDetailsTab__missingDependencies">
					<li v-for="(dep, index) in app.missingDependencies" :key="index">
						{{ dep }}
					</li>
				</ul>
			</NcNoteCard>

			<div v-if="groupsAppIsLimitedTo.length" :class="$style.appstoreDetailsTab__section">
				<h4 :id="idLimitedToGroups">
					{{ t('appstore', 'Limited to groups') }}
				</h4>
				<ul :aria-labelledby="idLimitedToGroups" :class="$style.appstoreDetailsTab__sectionDetails">
					<li
						v-for="group of groupsAppIsLimitedTo"
						:key="group.id"
						:title="group.id">
						{{ group.displayName }}
					</li>
				</ul>
			</div>

			<div v-if="lastModified && !app.shipped" :class="$style.appstoreDetailsTab__section">
				<h4>
					{{ t('appstore', 'Latest updated') }}
				</h4>
				<NcDateTime :class="$style.appstoreDetailsTab__sectionDetails" :timestamp="lastModified" />
			</div>

			<div :class="$style.appstoreDetailsTab__section">
				<h4>
					{{ t('appstore', 'Author') }}
				</h4>
				<p :class="$style.appstoreDetailsTab__sectionDetails">
					{{ appAuthors }}
				</p>
			</div>

			<div :class="$style.appstoreDetailsTab__section">
				<h4>
					{{ t('appstore', 'Categories') }}
				</h4>
				<p :class="$style.appstoreDetailsTab__sectionDetails">
					{{ appCategories }}
				</p>
			</div>

			<div v-if="externalResources.length > 0" :class="$style.appstoreDetailsTab__section">
				<h4>{{ t('appstore', 'Resources') }}</h4>
				<ul
					:class="$style.appstoreDetailsTab__resources"
					:aria-label="t('appstore', 'Documentation resources')">
					<li
						v-for="resource of externalResources"
						:key="resource.id"
						:class="$style.appstoreDetailsTab__resourcesItem">
						<a
							:class="$style.appstoreDetailsTab__resourcesLink"
							:href="resource.href"
							target="_blank"
							rel="noreferrer noopener">
							{{ resource.label }} ↗
						</a>
					</li>
				</ul>
			</div>
		</div>
	</NcAppSidebarTab>
</template>

<style module>
.appstoreDetailsTab__badges {
	display: flex;
	flex-direction: row;
	gap: 12px;
}

.appstoreDetailsTab__section {
	margin-top: 15px;

	h4 {
		font-size: 16px;
		font-weight: bold;
		margin-block-end: 5px;
	}
}

.appstoreDetailsTab__sectionDetails {
	color: var(--color-text-maxcontrast);
}

.appstoreDetailsTab__missingDependencies {
	list-style: disc;
	padding-block: 0.5lh 0;
	padding-inline: 1em 0;
}

.appstoreDetailsTab__resourcesLink {
	text-decoration: underline;
}

.appstoreDetailsTab__resourcesItem {
	padding-inline-start: 20px;

	&::before {
		width: 5px;
		height: 5px;
		border-radius: 100%;
		background-color: var(--color-main-text);
		content: "";
		float: inline-start;
		margin-inline-start: -13px;
		position: relative;
		top: 10px;
	}
}
</style>
