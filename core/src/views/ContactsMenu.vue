<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiAccountGroupOutline, mdiContacts, mdiMagnify } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getBuilder } from '@nextcloud/browser-storage'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import debounce from 'debounce'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import ContactMenuEntry from '../components/ContactsMenu/ContactMenuEntry.vue'
import logger from '../logger.js'

interface IPreviewUser {
	uid: string
	fullName: string
	isUser: boolean
}

const storage = getBuilder('core:contacts')
	.persist(true)
	.clearOnLogout(true)
	.build()

const user = getCurrentUser()!
const contactsAppURL = generateUrl('/apps/contacts')
const contactsAppMgmtURL = generateUrl('/settings/apps/social/contacts')

const contactsMenuInput = ref<HTMLInputElement>()

const actions = ref(window.OC?.ContactsMenu?.actions || [])
const contactsAppEnabled = ref(false)
const contacts = ref([])
const loadingText = ref<string>()
const hasError = ref(false)
const searchTerm = ref('')

const teams = ref<ITeam[]>([])
const storedTeam = storage.getItem('core:contacts:team')
const selectedTeam = ref<string>(storedTeam ? JSON.parse(storedTeam) : '$_all_$')
const selectedTeamName = computed(() => teams.value.find((t) => t.teamId === selectedTeam.value)?.displayName)
const previewUsers = ref<IPreviewUser[]>([])
const showAvatarStack = computed(() => previewUsers.value.length >= 2)

onMounted(async () => {
	if (userTeams.length === 0) {
		try {
			const { data } = await axios.get<ITeam[]>(generateUrl('/contactsmenu/teams'))
			userTeams.push(...data)
		} catch (error) {
			logger.error('could not load user teams', { error })
		}
	}
	teams.value = [...userTeams]
})

watch(selectedTeam, () => {
	storage.setItem('core:contacts:team', JSON.stringify(selectedTeam.value))
	getContacts(searchTerm.value)
	loadPreviewAvatars()
})

/**
 * Load avatars for the People menu header trigger
 */
async function loadPreviewAvatars() {
	try {
		const { data } = await axios.get<IPreviewUser[]>(generateUrl('/contactsmenu/preview-avatars'), {
			params: {
				teamId: selectedTeam.value !== '$_all_$' ? selectedTeam.value : undefined,
			},
		})
		previewUsers.value = data
	} catch (error) {
		logger.error('could not load preview avatars', { error })
		previewUsers.value = []
	}
}

// Seeded selectedTeam above so this runs once on mount with the correct team
loadPreviewAvatars()

/**
 * Load contacts when opening the menu
 */
async function onOpened() {
	await getContacts('')
}

/**
 * Load contacts from the server
 *
 * @param searchTerm - The search term to filter contacts by
 */
async function getContacts(searchTerm: string) {
	if (searchTerm === '') {
		loadingText.value = t('core', 'Loading your contacts …')
	} else {
		loadingText.value = t('core', 'Looking for {term} …', {
			term: searchTerm,
		})
	}

	// Let the user try a different query if the previous one failed
	hasError.value = false
	try {
		const { data } = await axios.post(generateUrl('/contactsmenu/contacts'), {
			filter: searchTerm,
			teamId: selectedTeam.value !== '$_all_$' ? selectedTeam.value : undefined,
		})
		contacts.value = data.contacts
		contactsAppEnabled.value = data.contactsAppEnabled
		loadingText.value = undefined
	} catch (error) {
		logger.error('could not load contacts', {
			error,
			searchTerm,
		})
		hasError.value = true
	}
}

const onInputDebounced = debounce(function() {
	getContacts(searchTerm.value)
}, 500)

/**
 * Reset the search state
 */
function onReset() {
	searchTerm.value = ''
	contacts.value = []
	focusInput()
}

/**
 * Focus the search input on next tick
 */
function focusInput() {
	nextTick(() => {
		contactsMenuInput.value?.focus()
		contactsMenuInput.value?.select()
	})
}
</script>

<script lang="ts">
interface ITeam {
	teamId: string
	displayName: string
	link: string
}

const userTeams: ITeam[] = []
</script>

<template>
	<NcHeaderMenu
		id="contactsmenu"
		class="contactsmenu"
		:class="{ 'contactsmenu--avatar-stack': showAvatarStack }"
		:aria-label="t('core', 'Search contacts')"
		exclude-click-outside-selectors=".v-popper__popper"
		@open="onOpened">
		<template #trigger>
			<span
				v-if="showAvatarStack"
				class="contactsmenu__trigger-avatars"
				aria-hidden="true">
				<NcAvatar
					v-for="(previewUser, index) in previewUsers"
					:key="previewUser.isUser ? previewUser.uid : `${previewUser.fullName}-${index}`"
					class="contactsmenu__trigger-avatars__avatar"
					:style="{ zIndex: previewUsers.length - index }"
					:user="previewUser.isUser ? previewUser.uid : undefined"
					:is-no-user="!previewUser.isUser"
					:display-name="previewUser.fullName"
					:size="32"
					disable-menu
					disable-tooltip
					hide-status />
			</span>
			<NcIconSvgWrapper
				v-else
				class="contactsmenu__trigger-icon"
				:path="mdiContacts" />
		</template>
		<div class="contactsmenu__menu">
			<div class="contactsmenu__menu__search-container">
				<div class="contactsmenu__menu__input-wrapper">
					<NcActions force-menu :aria-label="t('core', 'Filter by team')" variant="tertiary">
						<template #icon>
							<NcIconSvgWrapper :path="mdiAccountGroupOutline" />
						</template>
						<template #default>
							<NcActionButton
								:modelValue.sync="selectedTeam"
								value="$_all_$"
								type="radio">
								{{ t('core', 'All teams') }}
							</NcActionButton>
							<NcActionButton
								v-for="team of teams"
								:key="team.teamId"
								:modelValue.sync="selectedTeam"
								:value="team.teamId"
								type="radio">
								{{ team.displayName }}
							</NcActionButton>
						</template>
					</NcActions>
					<NcTextField
						id="contactsmenu__menu__search"
						ref="contactsMenuInput"
						v-model="searchTerm"
						class="contactsmenu__menu__search"
						trailing-button-icon="close"
						:label="selectedTeamName
							? t('core', 'Search contacts in team {team}', { team: selectedTeamName })
							: t('core', 'Search contacts …')
						"
						:trailing-button-label="t('core', 'Reset search')"
						:show-trailing-button="searchTerm !== ''"
						type="search"
						@input="onInputDebounced"
						@trailing-button-click="onReset" />
				</div>
				<NcButton
					v-for="action in actions"
					:key="action.id"
					:aria-label="action.label"
					:title="action.label"
					class="contactsmenu__menu__action"
					variant="tertiary-no-background"
					@click="action.onClick">
					<template #icon>
						<NcIconSvgWrapper :svg="action.icon" />
					</template>
				</NcButton>
			</div>
			<NcEmptyContent v-if="hasError" :name="t('core', 'Could not load your contacts')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadingText" :name="loadingText">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="contacts.length === 0" :name="t('core', 'No contacts found')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiMagnify" />
				</template>
			</NcEmptyContent>
			<div v-else class="contactsmenu__menu__content">
				<div id="contactsmenu-contacts">
					<ul :aria-label="t('core', 'Contacts list')">
						<ContactMenuEntry v-for="contact in contacts" :key="contact.id" :contact="contact" />
					</ul>
				</div>
				<div v-if="contactsAppEnabled" class="contactsmenu__menu__content__footer">
					<NcButton variant="tertiary" :href="contactsAppURL">
						{{ t('core', 'Show all contacts') }}
					</NcButton>
				</div>
				<div v-else-if="user.isAdmin" class="contactsmenu__menu__content__footer">
					<NcButton variant="tertiary" :href="contactsAppMgmtURL">
						{{ t('core', 'Install the Contacts app') }}
					</NcButton>
				</div>
			</div>
		</div>
	</NcHeaderMenu>
</template>

<style lang="scss" scoped>
.contactsmenu {
	margin-inline-end: calc(2 * var(--default-grid-baseline));

	:deep(.header-menu__trigger) {
		.button-vue__icon:has(.contactsmenu__trigger-avatars) {
			mask: none !important;
		}
	}

	&--avatar-stack {
		width: fit-content !important;
		min-width: var(--header-height);
		overflow: visible;
		flex-shrink: 0;

		:deep(.header-menu__trigger) {
			width: fit-content !important;
			min-width: var(--header-height);
			max-width: none;
			overflow: visible !important;
			padding-inline: var(--default-grid-baseline);

			.button-vue__wrapper {
				width: auto;
				justify-content: center;
			}

			.button-vue__icon {
				width: auto !important;
				min-width: 0;
				max-width: none;
				height: auto;
				min-height: 0;
				overflow: visible;
			}
		}
	}

	&__trigger-icon {
		color: var(--color-background-plain-text) !important;
	}

	&__trigger-avatars {
		display: flex;
		align-items: center;
		pointer-events: none;

		&__avatar {
			box-sizing: content-box;
			flex-shrink: 0;
			border: 2px solid var(--color-background-plain);
			margin-inline-start: -12px;

			&:first-child {
				margin-inline-start: 0;
			}
		}
	}

	&__menu {
		display: flex;
		flex-direction: column;
		overflow: hidden;
		height: calc(50px * 6 + 2px + 26px);
		max-height: inherit;

		&__search-container {
			padding: 10px;
			display: flex;
			flex: row nowrap;
			column-gap: 10px;
		}

		&__input-wrapper {
			z-index: 2;
			top: 0;
			flex-grow: 1;
			display: flex;
			gap: var(--default-grid-baseline);
		}

		&__search {
			width: 100%;
			height: 34px;
			margin-top: 0!important;
		}

		&__content {
			overflow-y: auto;
			margin-top: 10px;
			flex: 1 1 auto;

			&__footer {
				display: flex;
				flex-direction: column;
				align-items: center;
			}
		}

		a {
			&:focus-visible {
				box-shadow: inset 0 0 0 2px var(--color-main-text) !important; // override rule in core/css/headers.scss #header a:focus-visible
			}
		}
	}

	:deep(.empty-content) {
		margin: 0 !important;
	}
}
</style>
