<template>
	<div :class="{preview: true}">
		<div class="preview-image" :style="{backgroundImage: 'url(' + preview.img + ')'}"></div>
		<div class="preview-description">
			<h3>{{preview.title}}</h3>
			<p>{{preview.text}}</p>
			<input type="checkbox" class="checkbox" :id="'accessibility-' + preview.id" v-model="checked" @change="selectItem" />
			<label :for="'accessibility-' + preview.id">{{t('accessibility', 'Enable')}} {{preview.title.toLowerCase()}}</label>
		</div>
	</div>
</template>

<script>
export default {
	name: 'itemPreview',
	props: ['preview', 'selected'],
	data() {
		return {
			checked: this.selected === this.preview.id,
		};
	},
	methods: {
		selectItem() {
			this.$emit(
				'select',
				this.checked ? this.preview.id : false
			);
		}
	}
};
</script>
