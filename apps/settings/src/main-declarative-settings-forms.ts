import Vue from 'vue';
import { loadState } from '@nextcloud/initial-state';
import { translate as t, translatePlural as n } from '@nextcloud/l10n';
import DeclarativeSection from './components/DeclarativeSettings/DeclarativeSection.vue';

interface DeclarativeFormField {
	id: string,
	title: string,
	description: string,
	type: string,
	placeholder: string,
	label: string,
	options: Array<any>|null,
	value: any,
	default: any,
}

interface DeclarativeForm {
	id: number,
	priority: number,
	section_type: string,
	section_id: string,
	storage_type: string,
	title: string,
	description: string,
	doc_url: string,
	app: string,
	fields: Array<DeclarativeFormField>,
}

const forms = loadState('settings', 'declarative-settings-forms', []) as Array<DeclarativeForm>;
console.debug('Loaded declarative forms:', forms);

function renderDeclarativeSettingsSections(forms: Array<DeclarativeForm>): void {
	Vue.mixin({ methods: { t, n } })
	const DeclarativeSettingsSection = Vue.extend(<any>DeclarativeSection);
	for (const form of forms) {
		const el = `#${form.app}_${form.id}`
		new DeclarativeSettingsSection({
			el: el,
			propsData: {
				form,
			},
		})
	}
}

document.addEventListener('DOMContentLoaded', () => {
	renderDeclarativeSettingsSections(forms);
});
