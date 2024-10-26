<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<div class="public-page-menu__wrapper">
		<NcButton v-if="primaryAction"
			id="public-page-menu--primary"
			class="public-page-menu__primary"
			:href="primaryAction.href"
			type="primary"
			@click="openDialogIfNeeded">
			<template v-if="primaryAction.icon" #icon>
				<div :class="['icon', primaryAction.icon, 'public-page-menu__primary-icon']" />
			</template>
			{{ primaryAction.label }}
		</NcButton>

		<NcHeaderMenu v-if="secondaryActions.length > 0"
			id="public-page-menu"
			:aria-label="t('core', 'More actions')"
			:open.sync="showMenu">
			<template #trigger>
				<IconMore :size="20" />
			</template>
			<ul :aria-label="t('core', 'More actions')"
				class="public-page-menu"
				role="menu">
				<component :is="getComponent(entry)"
					v-for="entry, index in secondaryActions"
					:key="index"
					v-bind="entry"
					@click="showMenu = false" />
			</ul>
		</NcHeaderMenu>
	</div>
</template>

<script setup lang="ts">
import { spawnDialog } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { useIsSmallMobile } from '@nextcloud/vue/dist/Composables/useIsMobile.js'
import { computed, ref, type Ref } from 'vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcHeaderMenu from '@nextcloud/vue/dist/Components/NcHeaderMenu.js'
import IconMore from 'vue-material-design-icons/DotsHorizontal.vue'
import PublicPageMenuEntry from '../components/PublicPageMenu/PublicPageMenuEntry.vue'
import PublicPageMenuCustomEntry from '../components/PublicPageMenu/PublicPageMenuCustomEntry.vue'
import PublicPageMenuExternalEntry from '../components/PublicPageMenu/PublicPageMenuExternalEntry.vue'
import PublicPageMenuExternalDialog from '../components/PublicPageMenu/PublicPageMenuExternalDialog.vue'
import PublicPageMenuLinkEntry from '../components/PublicPageMenu/PublicPageMenuLinkEntry.vue'

interface IPublicPageMenu {
	id: string
	label: string
	href: string
	icon?: string
	html?: string
	details?: string
}

const menuEntries = loadState<Array<IPublicPageMenu>>('core', 'public-page-menu')

/** used to conditionally close the menu when clicking entry */
const showMenu = ref(false)

const isMobile = useIsSmallMobile() as Readonly<Ref<boolean>>
/** The primary menu action - only showed when not on mobile */
const primaryAction = computed(() => isMobile.value ? undefined : menuEntries[0])
/** All other secondary actions (including primary action on mobile) */
const secondaryActions = computed(() => isMobile.value ? menuEntries : menuEntries.slice(1))

/**
 * Get the render component for an entry
 * @param entry The entry to get the component for
 */
function getComponent(entry: IPublicPageMenu) {
	if ('html' in entry) {
		return PublicPageMenuCustomEntry
	}
	switch (entry.id) {
	case 'save':
		return PublicPageMenuExternalEntry
	case 'directLink':
		return PublicPageMenuLinkEntry
	default:
		return PublicPageMenuEntry
	}
}

/**
 * Open the "federated share" dialog if needed
 */
function openDialogIfNeeded() {
	if (primaryAction.value?.id !== 'save') {
		return
	}
	spawnDialog(PublicPageMenuExternalDialog, { label: primaryAction.value.label })
}
</script>

<style scoped lang="scss">
.public-page-menu {
	box-sizing: border-box;

	> :deep(*) {
		box-sizing: border-box;
	}

	&__wrapper {
		display: flex;
		flex-direction: row;
		gap: var(--default-grid-baseline);
	}

	&__primary {
		height: var(--default-clickable-area);
		margin-block: calc((var(--header-height) - var(--default-clickable-area)) / 2);

		// Ensure the correct focus-visible color is used (as this is rendered directly on the background(-image))
		&:focus-visible {
			border-color: var(--color-background-plain-text) !important;
		}
	}

	&__primary-icon {
		filter: var(--primary-invert-if-bright);
	}
}
</style>
