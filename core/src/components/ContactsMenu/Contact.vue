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
	<li :title="contact.displayName"
		role="button"
		class="contact">
		<NcAvatar :user="contact.user"
			:show-user-status="false"
			:hide-favorite="false"
			:disable-menu="true" 
			class="contact__avatar"/>
		<div class="contact__body">
			<div>{{ contact.displayName }}</div>
			<div @click.stop="(event) => event">
				<NcActions v-if="actions.length"
					:inline="contact.topAction ? 1 : 0">
					<template v-for="(action, idx) in actions">
						<NcActionLink v-if="action.hyperlink !== '#'"
							:key="idx"
							:href="action.hyperlink"
							class="other-actions"
							@click.stop="(event) => event">
							<template #icon>
								<img class="contact__action__icon" :src="action.icon">
							</template>
							{{ action.title }}
						</NcActionLink>
						<NcActionText v-else
							:key="idx"
							class="other-actions"
							@click.stop="(event) => event">
							<template #icon>
								<img class="contact__action__icon" :src="action.icon">
							</template>
							{{ action.title }}
						</NcActionText>
					</template>
				</NcActions>
			</div>
		</div>
	</li>
</template>

<script>
import {NcActionLink, NcButton, NcActionText, NcActions, NcAvatar} from '@nextcloud/vue'

export default {
	name: 'Contact',
	components: {
		NcActionLink,
		NcActionText,
		NcActions,
		NcAvatar,
		NcButton,
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
	},
}
</script>

<style scoped lang="scss">
.contact {
	display: flex;
	position: relative;
	align-items: center;
	justify-content: start;
	padding: 3px 3px 3px 10px;
	cursor: pointer;

	&:hover {
		background: var(--color-background-hover);
		border-radius: var(--border-radius-pill);
	}

	&__action {
		&__icon {
			width: 20px;
			height: 20px;
			padding: 12px;
			filter: var(--background-invert-if-dark);
		}
	}

	&__avatar {
		display: inherit;
	}

	&__body {
		flex-grow: 1;
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-left: 10px;
		min-width: 0;

		div {
			position: relative;
			width: 100%;
			overflow-x: hidden;
			text-overflow: ellipsis;
		}
		
		&:focus-visible {
			box-shadow: 0 0 0 4px var(--color-main-background) !important;
			outline: 2px solid var(--color-main-text) !important;
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
