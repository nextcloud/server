const Handlebars = require('handlebars'); const template = Handlebars.template; const templates = Handlebars.templates = Handlebars.templates || {}
templates.selection = template({
	1(container, depth0, helpers, partials, data) {
		let stack1; let helper; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return '	<span class="label">'
    + ((stack1 = ((helper = (helper = lookupProperty(helpers, 'tagMarkup') || (depth0 != null ? lookupProperty(depth0, 'tagMarkup') : depth0)) != null ? helper : container.hooks.helperMissing), (typeof helper === 'function' ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}), { name: 'tagMarkup', hash: {}, data, loc: { start: { line: 2, column: 21 }, end: { line: 2, column: 36 } } }) : helper))) != null ? stack1 : '')
    + '</span>\n'
	},
	3(container, depth0, helpers, partials, data) {
		let helper; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return '	<span class="label">'
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers, 'name') || (depth0 != null ? lookupProperty(depth0, 'name') : depth0)) != null ? helper : container.hooks.helperMissing), (typeof helper === 'function' ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}), { name: 'name', hash: {}, data, loc: { start: { line: 4, column: 21 }, end: { line: 4, column: 29 } } }) : helper)))
    + '</span>\n'
	},
	compiler: [8, '>= 4.3.0'],
	main(container, depth0, helpers, partials, data) {
		let stack1; const lookupProperty = container.lookupProperty || function(parent, propertyName) {
			if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
				return parent[propertyName]
			}
			return undefined
		}

		return ((stack1 = lookupProperty(helpers, 'if').call(depth0 != null ? depth0 : (container.nullContext || {}), (depth0 != null ? lookupProperty(depth0, 'isAdmin') : depth0), { name: 'if', hash: {}, fn: container.program(1, data, 0), inverse: container.program(3, data, 0), data, loc: { start: { line: 1, column: 0 }, end: { line: 5, column: 7 } } })) != null ? stack1 : '')
	},
	useData: true,
})
