/* eslint-disable valid-typeof */
const Handlebars = require('handlebars')
const template = Handlebars.template
const templates = Handlebars.templates = Handlebars.templates || {}
templates.result_form = template({
	1(container, depth0, helpers, partials, data) {
		let helper; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return '		<a href="#" class="delete icon icon-delete" title="'
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers, 'deleteTooltip') || (depth0 != null ? lookupProperty(depth0, 'deleteTooltip') : depth0)) != null ? helper : container.hooks.helperMissing), (typeof helper === 'function' ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}), { name: 'deleteTooltip', hash: {}, data, loc: { start: { line: 5, column: 53 }, end: { line: 5, column: 70 } } }) : helper)))
    + '"></a>\n'
	},
	compiler: [8, '>= 4.3.0'],
	main(container, depth0, helpers, partials, data) {
		let stack1; let helper; const alias1 = depth0 != null ? depth0 : (container.nullContext || {}); const alias2 = container.hooks.helperMissing; const alias3 = 'function'; const alias4 = container.escapeExpression; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return '<form class="systemtags-rename-form">\n	 <label class="hidden-visually" for="'
    + alias4(((helper = (helper = lookupProperty(helpers, 'cid') || (depth0 != null ? lookupProperty(depth0, 'cid') : depth0)) != null ? helper : alias2), (typeof helper === alias3 ? helper.call(alias1, { name: 'cid', hash: {}, data, loc: { start: { line: 2, column: 38 }, end: { line: 2, column: 45 } } }) : helper)))
    + '-rename-input">'
    + alias4(((helper = (helper = lookupProperty(helpers, 'renameLabel') || (depth0 != null ? lookupProperty(depth0, 'renameLabel') : depth0)) != null ? helper : alias2), (typeof helper === alias3 ? helper.call(alias1, { name: 'renameLabel', hash: {}, data, loc: { start: { line: 2, column: 60 }, end: { line: 2, column: 75 } } }) : helper)))
    + '</label>\n	<input id="'
    + alias4(((helper = (helper = lookupProperty(helpers, 'cid') || (depth0 != null ? lookupProperty(depth0, 'cid') : depth0)) != null ? helper : alias2), (typeof helper === alias3 ? helper.call(alias1, { name: 'cid', hash: {}, data, loc: { start: { line: 3, column: 12 }, end: { line: 3, column: 19 } } }) : helper)))
    + '-rename-input" type="text" value="'
    + alias4(((helper = (helper = lookupProperty(helpers, 'name') || (depth0 != null ? lookupProperty(depth0, 'name') : depth0)) != null ? helper : alias2), (typeof helper === alias3 ? helper.call(alias1, { name: 'name', hash: {}, data, loc: { start: { line: 3, column: 53 }, end: { line: 3, column: 61 } } }) : helper)))
    + '">\n'
    + ((stack1 = lookupProperty(helpers, 'if').call(alias1, (depth0 != null ? lookupProperty(depth0, 'isAdmin') : depth0), { name: 'if', hash: {}, fn: container.program(1, data, 0), inverse: container.noop, data, loc: { start: { line: 4, column: 1 }, end: { line: 6, column: 8 } } })) != null ? stack1 : '')
    + '</form>\n'
	},
	useData: true,
})
