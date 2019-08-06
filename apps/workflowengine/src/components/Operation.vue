<template>
	<div class="actions__item" :class="{'colored': !!color}" :style="{ backgroundColor: color }">
		<div class="icon" :class="icon"></div>
		<h3>{{ title }}</h3>
		<small>{{ description }}</small>
		<slot />
	</div>
</template>

<script>
	export default {
		name: "Operation",
		props: {
			icon: {
				type: String,
				required: true
			},
			title: {
				type: String,
				required: true
			},
			description: {
				type: String,
				required: true
			},
			color: {
				type: String,
				required: false
			},
		}
	}
</script>

<style scoped lang="scss">
	.actions__item {
		display: flex;
		flex-direction: column;
		flex-grow: 1;
		margin-left: -1px;
		padding: 10px;
		border-radius: var(--border-radius-large);
		max-width: 230px;
		margin-right: 20px;
	}
	.icon {
		display: block;
		width: 100%;
		height: 50px;
		background-size: 50px 50px;
		background-position: center center;
		margin-top: 10px;
		margin-bottom: 20px;
	}
	h3, small {
		padding: 6px;
		text-align: center;
		display: block;
	}
	h3 {
		margin: 0;
		padding: 0;
		font-weight: 500;
	}
	small {
		font-size: 10pt;
	}
	.icon-block {
		background-image: url(/apps-extra/files_accesscontrol/img/app-dark.svg);
	}

	.icon-convert-pdf {
		background-image: url(/apps-extra/workflow_pdf_converter/img/app-dark.svg);
	}

	.colored {
		.icon {
			filter: invert(1);
		}
		* {
			color: var(--color-primary-text)
		}
	}
</style>