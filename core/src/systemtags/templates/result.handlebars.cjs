/* eslint-disable valid-typeof */
const Handlebars = require('handlebars')
const template = Handlebars.template
const templates = Handlebars.templates = Handlebars.templates || {}
templates.result = template({
	1(container, depth0, helpers, partials, data) {
		return ' new-item'
	},
	3(container, depth0, helpers, partials, data) {
		let stack1; let helper; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return '		<span class="label">'
    + ((stack1 = ((helper = (helper = lookupProperty(helpers, 'tagMarkup') || (depth0 != null ? lookupProperty(depth0, 'tagMarkup') : depth0)) != null ? helper : container.hooks.helperMissing), (typeof helper === 'function' ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}), { name: 'tagMarkup', hash: {}, data, loc: { start: { line: 4, column: 22 }, end: { line: 4, column: 37 } } }) : helper))) != null ? stack1 : '')
    + '</span>\n'
	},
	5(container, depth0, helpers, partials, data) {
		let helper; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return '		<span class="label">'
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers, 'name') || (depth0 != null ? lookupProperty(depth0, 'name') : depth0)) != null ? helper : container.hooks.helperMissing), (typeof helper === 'function' ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}), { name: 'name', hash: {}, data, loc: { start: { line: 6, column: 22 }, end: { line: 6, column: 30 } } }) : helper)))
    + '</span>\n'
	},
	7(container, depth0, helpers, partials, data) {
		let helper; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return '		<span class="systemtags-actions">\n			<a href="#" class="rename icon icon-rename" title="'
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers, 'renameTooltip') || (depth0 != null ? lookupProperty(depth0, 'renameTooltip') : depth0)) != null ? helper : container.hooks.helperMissing), (typeof helper === 'function' ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}), { name: 'renameTooltip', hash: {}, data, loc: { start: { line: 10, column: 54 }, end: { line: 10, column: 71 } } }) : helper)))
    + '"></a>\n		</span>\n'
	},
	compiler: [8, '>= 4.3.0'],
	main(container, depth0, helpers, partials, data) {
		let stack1; let helper; let options; const alias1 = depth0 != null ? depth0 : (container.nullContext || {}); const alias2 = container.hooks.helperMissing; const alias3 = 'function'; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}; let buffer
  = '<span class="systemtags-item'
    + ((stack1 = lookupProperty(helpers, 'if').call(alias1, (depth0 != null ? lookupProperty(depth0, 'isNew') : depth0), { name: 'if', hash: {}, fn: container.program(1, data, 0), inverse: container.noop, data, loc: { start: { line: 1, column: 28 }, end: { line: 1, column: 57 } } })) != null ? stack1 : '')
    + '" data-id="'
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers, 'id') || (depth0 != null ? lookupProperty(depth0, 'id') : depth0)) != null ? helper : alias2), (typeof helper === alias3 ? helper.call(alias1, { name: 'id', hash: {}, data, loc: { start: { line: 1, column: 68 }, end: { line: 1, column: 74 } } }) : helper)))
    + '">\n<span class="checkmark icon icon-checkmark"></span>\n'
    + ((stack1 = lookupProperty(helpers, 'if').call(alias1, (depth0 != null ? lookupProperty(depth0, 'isAdmin') : depth0), { name: 'if', hash: {}, fn: container.program(3, data, 0), inverse: container.program(5, data, 0), data, loc: { start: { line: 3, column: 1 }, end: { line: 7, column: 8 } } })) != null ? stack1 : '')
		stack1 = ((helper = (helper = lookupProperty(helpers, 'allowActions') || (depth0 != null ? lookupProperty(depth0, 'allowActions') : depth0)) != null ? helper : alias2), (options = { name: 'allowActions', hash: {}, fn: container.program(7, data, 0), inverse: container.noop, data, loc: { start: { line: 8, column: 1 }, end: { line: 12, column: 18 } } }), (typeof helper === alias3 ? helper.call(alias1, options) : helper))
		if (!lookupProperty(helpers, 'allowActions')) { stack1 = container.hooks.blockHelperMissing.call(depth0, stack1, options) }
		if (stack1 != null) { buffer += stack1 }
		return buffer + '</span>\n'
	},
	useData: true,
})
