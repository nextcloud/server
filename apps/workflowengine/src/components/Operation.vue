<template>
	<div class="actions__item" :class="{'colored': colored}" :style="{ backgroundColor: colored ? operation.color : 'transparent' }">
		<div class="icon" :class="operation.iconClass" :style="{ backgroundImage: operation.iconClass ? '' : `url(${operation.icon})` }" />
		<div class="actions__item__description">
			<h3>{{ operation.name }}</h3>
			<small>{{ operation.description }}</small>
		</div>
		<div class="actions__item_options">
			<slot />
		</div>
	</div>
</template>

<script>
export default {
	name: 'Operation',
	props: {
		operation: {
			type: Object,
			required: true
		},
		colored: {
			type: Boolean,
			default: true
		}
	}
}
</script>

<style scoped lang="scss">
	.actions__item {
		display: flex;
		flex-wrap: wrap;
		flex-direction: column;
		flex-grow: 1;
		margin-left: -1px;
		padding: 10px;
		border-radius: var(--border-radius-large);
		margin-right: 20px;
		margin-bottom: 20px;
	}
	.icon {
		display: block;
		width: 100%;
		height: 50px;
		background-size: 50px 50px;
		background-position: center center;
		margin-top: 10px;
		margin-bottom: 20px;
		background-repeat: no-repeat;
	}
	.actions__item__description {
		text-align: center;
	}
	.actions__item_options {
		width: 100%;
		margin-top: 10px;
		padding-left: 60px;
	}
	h3, small {
		padding: 6px;
		display: block;
	}
	h3 {
		margin: 0;
		padding: 0;
		font-weight: 600;
	}
	small {
		font-size: 10pt;
	}

	.colored {
		background-color: var(--color-primary-element);
		* {
			color: var(--color-primary-text)
		}
	}

	.actions__item:not(.colored) {
		flex-direction: row;

		.actions__item__description {
			padding-top: 5px;
			text-align: left;
			width: calc(100% - 105px);
			small {
				padding: 0;
			}
		}
		.icon {
			width: 50px;
			margin: 0;
			margin-right: 10px;
			&:not(.icon-invert) {
				filter: invert(1);
			}
		}
	}

	/* TODO: those should be provided by the backend, remove once ready */
	.icon-block {
		background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='UTF-8' standalone='no'%3F%3E%3Csvg xmlns='http://www.w3.org/2000/svg' height='32' width='32' version='1.1' viewBox='0 0 32 32'%3E%3Cpath fill='%23fff' d='m10.203 2-8.203 8.203v11.594l8.203 8.203h11.594l8.203-8.203v-11.594l-8.203-8.203h-11.594zm11.097 5.3092 3.345 3.3448-5.346 5.346 5.346 5.346-3.299 3.299-5.346-5.346-5.346 5.346-3.2992-3.299 5.3462-5.346-5.3462-5.346 3.2992-3.2992 5.346 5.3462 5.3-5.3918z'/%3E%3C/svg%3E");
	}
	.icon-convert-pdf {
		background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' version='1.1' viewBox='0 0 16 16' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23fff'%3E%3Cpath d='m7.0624 2.9056c-0.20526 0-0.36653 0.14989-0.36653 0.34066v8.8571c0 0.19077 0.16127 0.34066 0.36653 0.34066h8.0637c0.20526 0 0.36653-0.14989 0.36653-0.34066v-7.1538l-2.1992-2.044zm4.2518 2.6571s0.05132 0.64725-0.10996 1.567c0.52414 1.3987 1.0996 1.5875 1.3562 1.7033 0.54247-0.040873 1.1472-0.068129 1.6861 0.2044 0.36653 0.19486 0.65536 1.022-0.21992 1.022-0.39586-0.023161-1.1267-0.23574-1.6494-0.47692-0.78145 0.081762-1.752 0.21802-2.5657 0.54505-0.91633 1.4308-1.3268 1.6352-1.6494 1.6352-0.89067-0.21802-0.41052-1.3149-0.073304-1.4989 0.40319-0.32022 0.87601-0.50417 1.0263-0.54505 0.065969-0.10221 1.0146-1.8327 1.2462-2.5549-0.21992-0.69767-0.27123-1.4349-0.14661-1.8736 0.57179-0.69358 1.0996-0.23846 1.0996 0.27253zm-0.51315 2.1121c-0.19793 0.72015-0.98817 2.1012-0.95299 2.044 0.81004-0.33044 1.5394-0.42923 2.3458-0.54505-0.38559-0.16011-0.84009-0.17033-1.3928-1.4989z' stroke-width='.70672'/%3E%3Cpath d='m16.246-9.7651c-2.05e-4 0.0144-6e-3 0.027629-6e-3 0.042066-0.0044 2.2592 2.0761 3.742 4.0564 3.6477v1.2349l2.3737-2.2265-2.3377-2.3407-3e-3 1.2289c-1.0287 0.1337-1.8811-0.66867-1.8659-1.5414 2.9e-4 -0.016152 0.0083-0.029062 9e-3 -0.045071z' stroke-width='.67694'/%3E%3Cpath d='m3.2734 5.1094v1.4492h-2.7676v2.5h2.7246l-0.0019532 1.4629 3.0996-2.6387-3.0547-2.7734z'/%3E%3Cpath d='m8.334-11.356c-0.78035-0.78051-1.9205-1.0863-2.9866-0.80073a0.51533 0.51533 0 1 0 0.26293 0.99405c0.71208-0.19075 1.4702 0.01747 1.9914 0.53876 0.46076 0.46083 0.65567 1.1026 0.56688 1.7376a0.61838 0.61838 0 1 0-0.87225 0.87442l0.8687 0.86886a0.61838 0.61838 0 0 0 0.86992 7.91e-5l0.86886-0.8687a0.61838 0.61838 0 0 0 0.0011543-0.88702 0.61838 0.61838 0 0 0-0.67634-0.12303c0.04094-0.86013-0.27221-1.7117-0.89472-2.3343zm-3.3067 1.0814a0.61838 0.61838 0 0 0-0.015967-0.01364l-0.86984-0.87a0.61838 0.61838 0 0 0-0.042126-0.04213 0.61838 0.61838 0 0 0-0.82551 0.04205l-0.87 0.86984a0.61838 0.61838 0 0 0 0.66145 1.0237c-0.024276 0.84049 0.29182 1.6675 0.90045 2.2762 0.78035 0.78052 1.9205 1.0863 2.9866 0.80073a0.51533 0.51533 0 1 0-0.27202-0.99408c-0.71208 0.19075-1.4669-0.011716-1.988-0.53306-0.45484-0.45491-0.65183-1.0905-0.57258-1.7183l0.018216 0.018221a0.61843 0.61843 0 0 0 0.88935-0.85959z' stroke-width='.68342'/%3E%3Cpath d='m31.219 0.33675v0.00113h-6.9286v1.3295l6.9286 0.036145c0.0026-1.821e-4 0.0053 2.074e-4 0.0079 0 0.0053-4.166e-4 0.01058-0.00137 0.01581-0.00113 0.65203-0.00106 1.1749 0.44619 1.1867 1.0392 0.0108 0.5673-0.60099 1.0888-1.3381 1.0019l-0.0013-0.79858-1.6753 1.5203 1.7016 1.4481-0.0013-0.8031c1.419 0.06127 2.9112-0.90236 2.9081-2.3709-0.0029-1.3197-1.2547-2.4007-2.7961-2.4014-0.0023-1e-6 -0.0043-0.00113-0.0066-0.00113z' stroke-width='.462'/%3E%3Crect x='31.116' y='-1.6777' width='4.3279' height='7.5909'/%3E%3C/g%3E%3C/svg%3E");
	}
	.colored .icon-invert {
		filter: invert(1);
	}
</style>
