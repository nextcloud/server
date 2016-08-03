/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function() {
	Handlebars.registerHelper('selectItem', function(currentValue, itemValue) {
		if(currentValue === itemValue) {
			return 'selected=selected';
		}

		return "";
	});

	Handlebars.registerHelper('getOperators', function(classname) {
		var check = OCA.WorkflowEngine.getCheckByClass(classname);
		if (!_.isUndefined(check)) {
			return check['operators'];
		}
		return [];
	});

	OCA.WorkflowEngine = _.extend(OCA.WorkflowEngine || {}, {
			availablePlugins: [],
			availableChecks: [],

			getCheckByClass: function(className) {
				var length = OCA.WorkflowEngine.availableChecks.length;
				for (var i = 0; i < length; i++) {
					if (OCA.WorkflowEngine.availableChecks[i]['class'] === className) {
						return OCA.WorkflowEngine.availableChecks[i];
					}
				}
				return undefined;
			}
	});

	/**
	 * 888b     d888               888          888
	 * 8888b   d8888               888          888
	 * 88888b.d88888               888          888
	 * 888Y88888P888  .d88b.   .d88888  .d88b.  888 .d8888b
	 * 888 Y888P 888 d88""88b d88" 888 d8P  Y8b 888 88K
	 * 888  Y8P  888 888  888 888  888 88888888 888 "Y8888b.
	 * 888   "   888 Y88..88P Y88b 888 Y8b.     888      X88
	 * 888       888  "Y88P"   "Y88888  "Y8888  888  88888P'
	 */

	/**
	 * @class OCA.WorkflowEngine.Operation
	 */
	OCA.WorkflowEngine.Operation =
		OC.Backbone.Model.extend({
			defaults: {
				'class': 'OCA\\WorkflowEngine\\Operation',
				'name': '',
				'checks': [],
				'operation': ''
			}
		});

	/**
	 *  .d8888b.           888 888                   888    d8b
	 * d88P  Y88b          888 888                   888    Y8P
	 * 888    888          888 888                   888
	 * 888         .d88b.  888 888  .d88b.   .d8888b 888888 888  .d88b.  88888b.  .d8888b
	 * 888        d88""88b 888 888 d8P  Y8b d88P"    888    888 d88""88b 888 "88b 88K
	 * 888    888 888  888 888 888 88888888 888      888    888 888  888 888  888 "Y8888b.
	 * Y88b  d88P Y88..88P 888 888 Y8b.     Y88b.    Y88b.  888 Y88..88P 888  888      X88
	 *  "Y8888P"   "Y88P"  888 888  "Y8888   "Y8888P  "Y888 888  "Y88P"  888  888  88888P'
	 */

	/**
	 * @class OCA.WorkflowEngine.OperationsCollection
	 *
	 * collection for all configurated operations
	 */
	OCA.WorkflowEngine.OperationsCollection =
		OC.Backbone.Collection.extend({
			model: OCA.WorkflowEngine.Operation,
			url: OC.generateUrl('apps/workflowengine/operations')
		});

	/**
	 * 888     888 d8b
	 * 888     888 Y8P
	 * 888     888
	 * Y88b   d88P 888  .d88b.  888  888  888 .d8888b
	 *  Y88b d88P  888 d8P  Y8b 888  888  888 88K
	 *   Y88o88P   888 88888888 888  888  888 "Y8888b.
	 *    Y888P    888 Y8b.     Y88b 888 d88P      X88
	 *     Y8P     888  "Y8888   "Y8888888P"   88888P'
	 */

	/**
	 * @class OCA.WorkflowEngine.TemplateView
	 *
	 * a generic template that handles the Handlebars template compile step
	 * in a method called "template()"
	 */
	OCA.WorkflowEngine.TemplateView =
		OC.Backbone.View.extend({
			_template: null,
			template: function(vars) {
				if (!this._template) {
					this._template = Handlebars.compile($(this.templateId).html());
				}
				return this._template(vars);
			}
		});

	/**
	 * @class OCA.WorkflowEngine.OperationView
	 *
	 * this creates the view for a single operation
	 */
	OCA.WorkflowEngine.OperationView =
		OCA.WorkflowEngine.TemplateView.extend({
			templateId: '#operation-template',
			events: {
				'change .check-class': 'checkChanged',
				'change .check-operator': 'checkChanged',
				'change .check-value': 'checkChanged',
				'change .operation-name': 'operationChanged',
				'change .operation-operation': 'operationChanged',
				'click .button-reset': 'reset',
				'click .button-save': 'save',
				'click .button-add': 'add',
				'click .button-delete': 'delete',
				'click .button-delete-check': 'deleteCheck'
			},
			originalModel: null,
			hasChanged: false,
			message: '',
			errorMessage: '',
			saving: false,
			initialize: function() {
				// this creates a new copy of the object to definitely have a new reference and being able to reset the model
				this.originalModel = JSON.parse(JSON.stringify(this.model));
				this.model.on('change', function(){
					console.log('model changed');
					this.hasChanged = true;
					this.render();
				}, this);

				if (this.model.get('id') === undefined) {
					this.hasChanged = true;
				}
			},
			delete: function() {
				this.model.destroy();
				this.remove();
			},
			reset: function() {
				this.hasChanged = false;
				// silent is need to not trigger the change event which resets the hasChanged attribute
				this.model.set(this.originalModel, {silent: true});
				this.render();
			},
			save: function() {
				var success = function(model, response, options) {
					this.saving = false;
					this.originalModel = JSON.parse(JSON.stringify(this.model));

					this.message = t('workflowengine', 'Successfully saved');
					this.errorMessage = '';
					this.render();
				};
				var error = function(model, response, options) {
					this.saving = false;
					this.hasChanged = true;

					this.message = t('workflowengine', 'Saving failed:');
					this.errorMessage = response.responseText;
					this.render();
				};
				this.hasChanged = false;
				this.saving = true;
				this.render();
				this.model.save(null, {success: success, error: error, context: this});
			},
			add: function() {
				var checks = _.clone(this.model.get('checks')),
					classname = OCA.WorkflowEngine.availableChecks[0]['class'],
					operators = OCA.WorkflowEngine.availableChecks[0]['operators'];

				checks.push({
					'class': classname,
					'operator': operators[0]['operator'],
					'value': ''
				});
				this.model.set({'checks': checks});
			},
			checkChanged: function(event) {
				var value = event.target.value,
					id = $(event.target.parentElement).data('id'),
					// this creates a new copy of the object to definitely have a new reference
					checks = JSON.parse(JSON.stringify(this.model.get('checks'))),
					key = null;

				for (var i = 0; i < event.target.classList.length; i++) {
					var className = event.target.classList[i];
					if (className.substr(0, 'check-'.length) === 'check-') {
						key = className.substr('check-'.length);
						break;
					}
				}

				if (key === null) {
					console.warn('checkChanged triggered but element doesn\'t have any "check-" class');
					return;
				}

				if (!_.has(checks[id], key)) {
					console.warn('key "' + key + '" is not available in check', check);
					return;
				}

				checks[id][key] = value;
				// if the class is changed most likely also the operators have changed
				// with this we set the operator to the first possible operator
				if (key === 'class') {
					var check = OCA.WorkflowEngine.getCheckByClass(value);
					if (!_.isUndefined(check)) {
						checks[id]['operator'] = check['operators'][0]['operator'];
					}
				}
				// model change will trigger render
				this.model.set({'checks': checks});
			},
			deleteCheck: function(event) {
				console.log(arguments);
				var id = $(event.target.parentElement).data('id'),
					checks = JSON.parse(JSON.stringify(this.model.get('checks')));

				// splice removes 1 element at index `id`
				checks.splice(id, 1);
				// model change will trigger render
				this.model.set({'checks': checks});
			},
			operationChanged: function(event) {
				var value = event.target.value,
					key = null;

				for (var i = 0; i < event.target.classList.length; i++) {
					var className = event.target.classList[i];
					if (className.substr(0, 'operation-'.length) === 'operation-') {
						key = className.substr('operation-'.length);
						break;
					}
				}

				if (key === null) {
					console.warn('operationChanged triggered but element doesn\'t have any "operation-" class');
					return;
				}

				if (key !== 'name' && key !== 'operation') {
					console.warn('key "' + key + '" is no valid attribute');
					return;
				}

				// model change will trigger render
				this.model.set(key, value);
			},
			render: function() {
				this.$el.html(this.template({
					operation: this.model.toJSON(),
					classes: OCA.WorkflowEngine.availableChecks,
					hasChanged: this.hasChanged,
					message: this.message,
					errorMessage: this.errorMessage,
					saving: this.saving
				}));

				var checks = this.model.get('checks');
				_.each(this.$el.find('.check'), function(element){
					var $element = $(element),
						id = $element.data('id'),
						check = checks[id],
						valueElement = $element.find('.check-value').first();

					_.each(OCA.WorkflowEngine.availablePlugins, function(plugin) {
						if (_.isFunction(plugin.render)) {
							plugin.render(valueElement, check);
						}
					});
				}, this);

				if (this.message !== '') {
					// hide success messages after some time
					_.delay(function(elements){
						$(elements).css('opacity', 0);
					}, 7000, this.$el.find('.msg.success'));
					this.message = '';
				}

				return this.$el;
			}
		});

	/**
	 * @class OCA.WorkflowEngine.OperationsView
	 *
	 * this creates the view for configured operations
	 */
	OCA.WorkflowEngine.OperationsView =
		OCA.WorkflowEngine.TemplateView.extend({
			templateId: '#operations-template',
			collection: null,
			$el: null,
			events: {
				'click .button-add-operation': 'add'
			},
			initialize: function(classname) {
				if (!OCA.WorkflowEngine.availablePlugins.length) {
					OCA.WorkflowEngine.availablePlugins = OC.Plugins.getPlugins('OCA.WorkflowEngine.CheckPlugins');
					_.each(OCA.WorkflowEngine.availablePlugins, function(plugin) {
						if (_.isFunction(plugin.getCheck)) {
							OCA.WorkflowEngine.availableChecks.push(plugin.getCheck(classname));
						}
					});
				}

				this.collection.fetch({data: {
					'class': classname
				}});
				this.collection.once('sync', this.render, this);
			},
			add: function() {
				var operation = this.collection.create();
				this.renderOperation(operation);
			},
			renderOperation: function(subView){
				var operationsElement = this.$el.find('.operations');
				operationsElement.append(subView.$el);
				subView.render();
			},
			render: function() {
				this.$el.html(this.template());
				this.collection.each(this.renderOperation, this);
			}
		});
})();
