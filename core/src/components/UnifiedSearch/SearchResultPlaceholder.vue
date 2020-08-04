<template>
	<svg
		class="unified-search__result-placeholder"
		xmlns="http://www.w3.org/2000/svg"
		fill="url(#unified-search__result-placeholder-gradient)">
		<defs>
			<linearGradient id="unified-search__result-placeholder-gradient">
				<stop offset="0%" stop-color="#ededed"><animate attributeName="stop-color"
					values="#ededed; #ededed; #cccccc; #cccccc; #ededed"
					dur="2s"
					repeatCount="indefinite" /></stop>
				<stop offset="100%" stop-color="#cccccc"><animate attributeName="stop-color"
					values="#cccccc; #ededed; #ededed; #cccccc; #cccccc"
					dur="2s"
					repeatCount="indefinite" /></stop>
			</linearGradient>
		</defs>
		<rect class="unified-search__result-placeholder-icon" />
		<rect class="unified-search__result-placeholder-line-one" />
		<rect class="unified-search__result-placeholder-line-two" :style="{width: `calc(${randWidth}%)`}" />
	</svg>
</template>

<script>
export default {
	name: 'SearchResultPlaceholder',

	data() {
		return {
			randWidth: Math.floor(Math.random() * 20) + 30,
		}
	},
}
</script>

<style lang="scss" scoped>
$clickable-area: 44px;
$margin: 10px;

.unified-search__result-placeholder {
	width: calc(100% - 2 * #{$margin});
	height: $clickable-area;
	margin: $margin;

	&-icon {
		width: $clickable-area;
		height: $clickable-area;
		rx: var(--border-radius);
		ry: var(--border-radius);
	}

	&-line-one,
	&-line-two {
		width: calc(100% - #{$margin + $clickable-area});
		height: 1em;
		x: $margin + $clickable-area;
	}

	&-line-one {
		y: 5px;
	}

	&-line-two {
		y: 25px;
	}
}

</style>
