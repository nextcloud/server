<template>
	<a :class="{preview: true, selected: preview.id === selected}"
	   href="#" @click="selectItem">
		<div class="preview-image" :style="{backgroundImage: 'url(' + preview.img + ')'}"></div>
		<h3>
			<span>{{preview.title}}</span>
			<div class="icon-checkmark-color">{{t('accessibility', 'enabled')}}</div>
		</h3>
		<p>{{preview.text}}</p>
	</a>
</template>

<script>
export default {
	name: 'itemPreview',
	props: ['preview', 'selected'],
	methods: {
		selectItem() {
			this.$emit(
				'select',
				// if we clicked the already selected one: disable it
				this.preview.id === this.selected ? false : this.preview.id
			);
		}
	}
};
</script>
