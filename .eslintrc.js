module.exports = {
	globals: {
		__webpack_nonce__: true,
		_: true,
		$: true,
		dayNames: true,
		escapeHTML: true,
		firstDay: true,
		moment: true,
		oc_userconfig: true,
		sinon: true,
	},
	plugins: [
		'cypress',
	],
	extends: [
		'@nextcloud/eslint-config/typescript',
		'plugin:cypress/recommended',
	],
	rules: {
		'no-tabs': 'warn',
		// TODO: make sure we fix this as this is bad vue coding style.
		// Use proper sync modifier
		'vue/no-mutating-props': 'warn',
		'vue/custom-event-name-casing': ['error', 'kebab-case', {
			// allows custom xxxx:xxx events formats
			ignores: ['/^[a-z]+(?:-[a-z]+)*:[a-z]+(?:-[a-z]+)*$/u'],
		}],
	},
	settings: {
		jsdoc: {
			mode: 'typescript',
		},
	},
}
