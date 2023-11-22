<!--
  - @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<li class="contact">
		<a v-if="contact.profileUrl && contact.avatar"
			:href="contact.profileUrl"
			class="contact__avatar-wrapper">
			<NcAvatar class="contact__avatar"
				:size="44"
				:user="contact.isUser ? contact.uid : undefined"
				:is-no-user="!contact.isUser"
				:display-name="contact.avatarLabel"
				:url="contact.avatar"
				:preloaded-user-status="preloadedUserStatus" />
		</a>
		<a v-else-if="contact.profileUrl"
			:href="contact.profileUrl">
			<NcAvatar class="contact__avatar"
				:size="44"
				:user="contact.isUser ? contact.uid : undefined"
				:is-no-user="!contact.isUser"
				:display-name="contact.avatarLabel"
				:preloaded-user-status="preloadedUserStatus" />
		</a>
		<NcAvatar v-else
			:size="44"
			class="contact__avatar"
			:user="contact.isUser ? contact.uid : undefined"
			:is-no-user="!contact.isUser"
			:display-name="contact.avatarLabel"
			:url="contact.avatar"
			:preloaded-user-status="preloadedUserStatus" />

		<a class="contact__body"
			:href="contact.profileUrl || contact.topAction?.hyperlink">
			<div class="contact__body__full-name">{{ contact.fullName }}</div>
			<div v-if="contact.lastMessage" class="contact__body__last-message">{{ contact.lastMessage }}</div>
			<div v-if="contact.statusMessage" class="contact__body__status-message">{{ contact.statusMessage }}</div>
			<div v-else class="contact__body__email-address">{{ contact.emailAddresses[0] }}</div>
		</a>
		<NcActions v-if="actions.length"
			:inline="contact.topAction ? 1 : 0">
			<template v-for="(action, idx) in actions">
				<NcActionLink v-if="action.hyperlink !== '#'"
					:key="idx"
					:href="action.hyperlink"
					class="other-actions">
					<template #icon>
						<img class="contact__action__icon" :src="action.icon">
					</template>
					{{ action.title }}
				</NcActionLink>
				<NcActionText v-else :key="idx" class="other-actions">
					<template #icon>
						<img class="contact__action__icon" :src="action.icon">
					</template>
					{{ action.title }}
				</NcActionText>
			</template>
		</NcActions>
	</li>
</template>

<script>
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcActionText from '@nextcloud/vue/dist/Components/NcActionText.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'

export default {
	name: 'Contact',
	components: {
		NcActionLink,
		NcActionText,
		NcActions,
		NcAvatar,
	},
	props: {
		contact: {
			required: true,
			type: Object,
		},
	},
	computed: {
		actions() {
			if (this.contact.topAction) {
				return [this.contact.topAction, ...this.contact.actions]
			}
			return this.contact.actions
		},
		preloadedUserStatus() {
			if (this.contact.status) {
				return {
					status: this.contact.status,
					message: this.contact.statusMessage,
					icon: this.contact.statusIcon,
				}
			}
			return undefined
		}
	},
}
</script>

<style scoped lang="scss">
.contact {
	display: flex;
	position: relative;
	align-items: center;
	padding: 3px 3px 3px 10px;

	&__action {
		&__icon {
			width: 20px;
			height: 20px;
			padding: 12px;
			filter: var(--background-invert-if-dark);
		}
	}

	&__avatar-wrapper {
	}

	&__avatar {
		display: inherit;
	}

	&__body {
		flex-grow: 1;
		padding-left: 10px;
		min-width: 0;

		div {
			position: relative;
			width: 100%;
			overflow-x: hidden;
			text-overflow: ellipsis;
			margin: -1px 0;
		}
		div:first-of-type {
			margin-top: 0;
		}
		div:last-of-type {
			margin-bottom: 0;
		}

		&__last-message, &__status-message, &__email-address {
			color: var(--color-text-maxcontrast);
		}
	}

	.other-actions {
		width: 16px;
		height: 16px;
		cursor: pointer;

		img {
			filter: var(--background-invert-if-dark);
		}
	}

	button.other-actions {
		width: 44px;

		&:focus {
			border-color: transparent;
			box-shadow: 0 0 0 2px var(--color-main-text);
		}

		&:focus-visible {
			border-radius: var(--border-radius-pill);
		}
	}

	/* actions menu */
	.menu {
		top: 47px;
		margin-right: 13px;
	}

	.popovermenu::after {
		right: 2px;
	}
}
</style>
