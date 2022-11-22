 <!--
  - @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
  -
  -->
<template>
	<div :id="id"
		v-click-outside="clickOutsideConfig"
		:class="{ 'header-menu--opened': opened }"
		class="header-menu">
		<a class="header-menu__trigger"
			href="#"
			:aria-label="ariaLabel"
			:aria-controls="`header-menu-${id}`"
			:aria-expanded="opened.toString()"
			@click.prevent="toggleMenu">
			<slot name="trigger" />
		</a>
		<div v-show="opened" class="header-menu__carret" />
		<div v-show="opened"
			:id="`header-menu-${id}`"
			class="header-menu__wrapper"
			role="menu"
			@focusout="handleFocusOut">
			<div class="header-menu__content">
				<slot />
			</div>
		</div>
	</div>
</template>

<script>
import { directive as ClickOutside } from 'v-click-outside'
import excludeClickOutsideClasses from '@nextcloud/vue/dist/Mixins/excludeClickOutsideClasses'

export default {
	name: 'HeaderMenu',

	directives: {
		ClickOutside,
	},

	mixins: [
		excludeClickOutsideClasses,
	],

	props: {
		id: {
			type: String,
			required: true,
		},
		ariaLabel: {
			type: String,
			default: '',
		},
		open: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			opened: this.open,
			clickOutsideConfig: {
				handler: this.closeMenu,
				middleware: this.clickOutsideMiddleware,
			},
			shortcutsDisabled: OCP.Accessibility.disableKeyboardShortcuts(),
		}
	},

	watch: {
		open(newVal) {
			this.opened = newVal
			this.$nextTick(() => {
				if (this.opened) {
					this.openMenu()
				} else {
					this.closeMenu()
				}
			})
		},
	},

	mounted() {
		document.addEventListener('keydown', this.onKeyDown)
	},
	beforeDestroy() {
		document.removeEventListener('keydown', this.onKeyDown)
	},

	methods: {
		/**
		 * Toggle the current menu open state
		 */
		toggleMenu() {
			// Toggling current state
			if (!this.opened) {
				this.openMenu()
			} else {
				this.closeMenu()
			}
		},

		/**
		 * Close the current menu
		 */
		closeMenu() {
			if (!this.opened) {
				return
			}

			this.opened = false
			this.$emit('close')
			this.$emit('update:open', false)
		},

		/**
		 * Open the current menu
		 */
		openMenu() {
			if (this.opened) {
				return
			}

			this.opened = true
			this.$emit('open')
			this.$emit('update:open', true)
		},

		onKeyDown(event) {
			if (this.shortcutsDisabled) {
				return
			}

			// If opened and escape pressed, close
			if (event.key === 'Escape' && this.opened) {
				event.preventDefault()

				/** user cancelled the menu by pressing escape */
				this.$emit('cancel')

				/** we do NOT fire a close event to differentiate cancel and close */
				this.opened = false
				this.$emit('update:open', false)
			}
		},

		handleFocusOut(event) {
			if (!event.currentTarget.contains(event.relatedTarget)) {
				this.closeMenu()
			}
		},
	},
}
</script>

<style lang="scss" scoped>
$externalMargin: 8px;

.header-menu {
	&__trigger {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 50px;
		height: 44px;
		margin: 2px 0;
		padding: 0;
		cursor: pointer;
		opacity: .85;
	}

	&--opened &__trigger,
	&__trigger:hover,
	&__trigger:focus,
	&__trigger:active {
		opacity: 1;
	}

	&__trigger:focus-visible {
		outline: none;
	}

	&__wrapper {
		position: fixed;
		z-index: 2000;
		top: 50px;
		right: 0;
		box-sizing: border-box;
		margin: 0 $externalMargin;
		border-radius: 0 0 var(--border-radius) var(--border-radius);
		background-color: var(--color-main-background);
		filter: drop-shadow(0 1px 5px var(--color-box-shadow));
		padding: 8px;
		border-radius: var(--border-radius-large);
	}

	&__carret {
		position: absolute;
		z-index: 2001; // Because __wrapper is 2000.
		left: calc(50% - 10px);
		bottom: 0;
		width: 0;
		height: 0;
		content: ' ';
		pointer-events: none;
		border: 10px solid transparent;
		border-bottom-color: var(--color-main-background);
	}

	&__content {
		overflow: auto;
		width: 350px;
		max-width: calc(100vw - 2 * $externalMargin);
		min-height: calc(44px * 1.5);
		max-height: calc(100vh - 50px * 2);
	}
}

</style>
