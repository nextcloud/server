/* global OC.Backbone, Handlebars, Promise, _ */

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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

(function(OC, $, _, Handlebars) {
	'use strict';

	var MENU_TEMPLATE = ''
			+ '<label class="hidden-visually" for="contactsmenu-search">' + t('core', 'Search contacts …') + '</label>'
			+ '<input id="contactsmenu-search" type="search" placeholder="' + t('core', 'Search contacts …') + '" value="{{searchTerm}}">'
			+ '<div class="content">'
			+ '</div>';
	var CONTACTS_LIST_TEMPLATE = ''
			+ '{{#unless contacts.length}}'
			+ '<div class="emptycontent">'
			+ '    <div class="icon-search"></div>'
			+ '    <h2>' + t('core', 'No contacts found') + '</h2>'
			+ '</div>'
			+ '{{/unless}}'
			+ '<div id="contactsmenu-contacts"></div>'
			+ '{{#if contactsAppEnabled}}<div class="footer"><a href="{{contactsAppURL}}">' + t('core', 'Show all contacts …') + '</a></div>{{/if}}';
	var LOADING_TEMPLATE = ''
			+ '<div class="emptycontent">'
			+ '    <div class="icon-loading"></div>'
			+ '    <h2>{{loadingText}}</h2>'
			+ '</div>';
	var ERROR_TEMPLATE = ''
			+ '<div class="emptycontent">'
			+ '    <div class="icon-search"></div>'
			+ '    <h2>' + t('core', 'There was an error loading your contacts') + '</h2>'
			+ '</div>';
	var CONTACT_TEMPLATE = ''
			+ '{{#if contact.avatar}}'
			+ '<img src="{{contact.avatar}}&size=32" class="avatar"'
			+ 'srcset="{{contact.avatar}}&size=32 1x, {{contact.avatar}}&size=64 2x, {{contact.avatar}}&size=128 4x" alt="">'
			+ '{{else}}'
			+ '<div class="avatar"></div>'
			+ '{{/if}}'
			+ '<div class="body">'
			+ '    <div class="full-name">{{contact.fullName}}</div>'
			+ '    <div class="last-message">{{contact.lastMessage}}</div>'
			+ '</div>'
			+ '{{#if contact.topAction}}'
			+ '<a class="top-action" href="{{contact.topAction.hyperlink}}" title="{{contact.topAction.title}}">'
			+ '    <img src="{{contact.topAction.icon}}" alt="{{contact.topAction.title}}">'
			+ '</a>'
			+ '{{/if}}'
			+ '{{#if contact.hasTwoActions}}'
			+ '<a class="second-action" href="{{contact.secondAction.hyperlink}}" title="{{contact.secondAction.title}}">'
			+ '    <img src="{{contact.secondAction.icon}}" alt="{{contact.secondAction.title}}">'
			+ '</a>'
			+ '{{/if}}'
			+ '{{#if contact.hasManyActions}}'
			+ '    <span class="other-actions icon-more"></span>'
			+ '    <div class="menu popovermenu">'
			+ '        <ul>'
			+ '            {{#each contact.actions}}'
			+ '            <li>'
			+ '                <a href="{{hyperlink}}">'
			+ '                    <img src="{{icon}}" alt="">'
			+ '                    <span>{{title}}</span>'
			+ '                </a>'
			+ '            </li>'
			+ '            {{/each}}'
			+ '        </ul>'
			+ '    </div>'
			+ '{{/if}}';

	/**
	 * @class Contact
	 */
	var Contact = OC.Backbone.Model.extend({
		defaults: {
			fullName: '',
			lastMessage: '',
			actions: [],
			hasOneAction: false,
			hasTwoActions: false,
			hasManyActions: false
		},

		/**
		 * @returns {undefined}
		 */
		initialize: function() {
			// Add needed property for easier template rendering
			if (this.get('actions').length === 0) {
				this.set('hasOneAction', true);
			} else if (this.get('actions').length === 1) {
				this.set('hasTwoActions', true);
				this.set('secondAction', this.get('actions')[0]);
			} else {
				this.set('hasManyActions', true);
			}
		}
	});

	/**
	 * @class ContactCollection
	 */
	var ContactCollection = OC.Backbone.Collection.extend({
		model: Contact
	});

	/**
	 * @class ContactsListView
	 */
	var ContactsListView = OC.Backbone.View.extend({

		/** @type {ContactsCollection} */
		_collection: undefined,

		/** @type {array} */
		_subViews: [],

		/**
		 * @param {object} options
		 * @returns {undefined}
		 */
		initialize: function(options) {
			this._collection = options.collection;
		},

		/**
		 * @returns {self}
		 */
		render: function() {
			var self = this;
			self.$el.html('');
			self._subViews = [];

			self._collection.forEach(function(contact) {
				var item = new ContactsListItemView({
					model: contact
				});
				item.render();
				self.$el.append(item.$el);
				item.on('toggle:actionmenu', self._onChildActionMenuToggle, self);
				self._subViews.push(item);
			});

			return self;
		},

		/**
		 * Event callback to propagate opening (another) entry's action menu
		 *
		 * @param {type} $src
		 * @returns {undefined}
		 */
		_onChildActionMenuToggle: function($src) {
			this._subViews.forEach(function(view) {
				view.trigger('parent:toggle:actionmenu', $src);
			});
		}
	});

	/**
	 * @class CotnactsListItemView
	 */
	var ContactsListItemView = OC.Backbone.View.extend({

		/** @type {string} */
		className: 'contact',

		/** @type {undefined|function} */
		_template: undefined,

		/** @type {Contact} */
		_model: undefined,

		/** @type {boolean} */
		_actionMenuShown: false,

		events: {
			'click .icon-more': '_onToggleActionsMenu'
		},

		/**
		 * @param {object} data
		 * @returns {undefined}
		 */
		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(CONTACT_TEMPLATE);
			}
			return this._template(data);
		},

		/**
		 * @param {object} options
		 * @returns {undefined}
		 */
		initialize: function(options) {
			this._model = options.model;
			this.on('parent:toggle:actionmenu', this._onOtherActionMenuOpened, this);
		},

		/**
		 * @returns {self}
		 */
		render: function() {
			this.$el.html(this.template({
				contact: this._model.toJSON()
			}));
			this.delegateEvents();

			// Show placeholder if no avatar is available (avatar is rendered as img, not div)
			this.$('div.avatar').imageplaceholder(this._model.get('fullName'));

			// Show tooltip for top action
			this.$('.top-action').tooltip({placement: 'left'});
			// Show tooltip for second action
			this.$('.second-action').tooltip({placement: 'left'});

			return this;
		},

		/**
		 * Toggle the visibility of the action popover menu
		 *
		 * @private
		 * @returns {undefined}
		 */
		_onToggleActionsMenu: function() {
			this._actionMenuShown = !this._actionMenuShown;
			if (this._actionMenuShown) {
				this.$('.menu').show();
			} else {
				this.$('.menu').hide();
			}
			this.trigger('toggle:actionmenu', this.$el);
		},

		/**
		 * @private
		 * @argument {jQuery} $src
		 * @returns {undefined}
		 */
		_onOtherActionMenuOpened: function($src) {
			if (this.$el.is($src)) {
				// Ignore
				return;
			}
			this._actionMenuShown = false;
			this.$('.menu').hide();
		}
	});

	/**
	 * @class ContactsMenuView
	 */
	var ContactsMenuView = OC.Backbone.View.extend({

		/** @type {undefined|function} */
		_loadingTemplate: undefined,

		/** @type {undefined|function} */
		_errorTemplate: undefined,

		/** @type {undefined|function} */
		_contentTemplate: undefined,

		/** @type {undefined|function} */
		_contactsTemplate: undefined,

		/** @type {undefined|ContactCollection} */
		_contacts: undefined,

		/** @type {string} */
		_searchTerm: '',

		events: {
			'input #contactsmenu-search': '_onSearch'
		},

		/**
		 * @returns {undefined}
		 */
		_onSearch: _.debounce(function(e) {
			var searchTerm = this.$('#contactsmenu-search').val();
			// IE11 triggers an 'input' event after the view has been rendered
			// resulting in an endless loading loop. To prevent this, we remember
			// the last search term to savely ignore some events
			// See https://github.com/nextcloud/server/issues/5281
			if (searchTerm !== this._searchTerm) {
				this.trigger('search', this.$('#contactsmenu-search').val());
				this._searchTerm = searchTerm;
			}
		}, 700),

		/**
		 * @param {object} data
		 * @returns {string}
		 */
		loadingTemplate: function(data) {
			if (!this._loadingTemplate) {
				this._loadingTemplate = Handlebars.compile(LOADING_TEMPLATE);
			}
			return this._loadingTemplate(data);
		},

		/**
		 * @param {object} data
		 * @returns {string}
		 */
		errorTemplate: function(data) {
			if (!this._errorTemplate) {
				this._errorTemplate = Handlebars.compile(ERROR_TEMPLATE);
			}
			return this._errorTemplate(data);
		},

		/**
		 * @param {object} data
		 * @returns {string}
		 */
		contentTemplate: function(data) {
			if (!this._contentTemplate) {
				this._contentTemplate = Handlebars.compile(MENU_TEMPLATE);
			}
			return this._contentTemplate(data);
		},

		/**
		 * @param {object} data
		 * @returns {string}
		 */
		contactsTemplate: function(data) {
			if (!this._contactsTemplate) {
				this._contactsTemplate = Handlebars.compile(CONTACTS_LIST_TEMPLATE);
			}
			return this._contactsTemplate(data);
		},

		/**
		 * @param {object} options
		 * @returns {undefined}
		 */
		initialize: function(options) {
			this.options = options;
		},

		/**
		 * @param {string} text
		 * @returns {undefined}
		 */
		showLoading: function(text) {
			this.render();
			this._contacts = undefined;
			this.$('.content').html(this.loadingTemplate({
				loadingText: text
			}));
		},

		/**
		 * @returns {undefined}
		 */
		showError: function() {
			this.render();
			this._contacts = undefined;
			this.$('.content').html(this.errorTemplate());
		},

		/**
		 * @param {object} viewData
		 * @param {string} searchTerm
		 * @returns {undefined}
		 */
		showContacts: function(viewData, searchTerm) {
			this._contacts = viewData.contacts;
			this.render({
				contacts: viewData.contacts
			});

			var list = new ContactsListView({
				collection: viewData.contacts
			});
			list.render();
			this.$('.content').html(this.contactsTemplate({
				contacts: viewData.contacts,
				searchTerm: searchTerm,
				contactsAppEnabled: viewData.contactsAppEnabled,
				contactsAppURL: OC.generateUrl('/apps/contacts')
			}));
			this.$('#contactsmenu-contacts').html(list.$el);
		},

		/**
		 * @param {object} data
		 * @returns {self}
		 */
		render: function(data) {
			var searchVal = this.$('#contactsmenu-search').val();
			this.$el.html(this.contentTemplate(data));

			// Focus search
			this.$('#contactsmenu-search').val(searchVal);
			this.$('#contactsmenu-search').focus();
			return this;
		}

	});

	/**
	 * @param {Object} options
	 * @param {jQuery} options.el
	 * @param {jQuery} options.trigger
	 * @class ContactsMenu
	 */
	var ContactsMenu = function(options) {
		this.initialize(options);
	};

	ContactsMenu.prototype = {
		/** @type {jQuery} */
		$el: undefined,

		/** @type {jQuery} */
		_$trigger: undefined,

		/** @type {ContactsMenuView} */
		_view: undefined,

		/** @type {Promise} */
		_contactsPromise: undefined,

		/**
		 * @param {Object} options
		 * @param {jQuery} options.el - the element to render the menu in
		 * @param {jQuery} options.trigger - the element to click on to open the menu
		 * @returns {undefined}
		 */
		initialize: function(options) {
			this.$el = options.el;
			this._$trigger = options.trigger;

			this._view = new ContactsMenuView({
				el: this.$el
			});
			this._view.on('search', function(searchTerm) {
				this._loadContacts(searchTerm);
			}, this);

			OC.registerMenu(this._$trigger, this.$el, function() {
				this._toggleVisibility(true);
			}.bind(this));
			this.$el.on('beforeHide', function() {
				this._toggleVisibility(false);
			}.bind(this));
		},

		/**
		 * @private
		 * @param {boolean} show
		 * @returns {Promise}
		 */
		_toggleVisibility: function(show) {
			if (show) {
				return this._loadContacts();
			} else {
				this.$el.html('');
				return Promise.resolve();
			}
		},

		/**
		 * @private
		 * @param {string|undefined} searchTerm
		 * @returns {Promise}
		 */
		_getContacts: function(searchTerm) {
			var url = OC.generateUrl('/contactsmenu/contacts');
			return Promise.resolve($.ajax(url, {
				method: 'POST',
				data: {
					filter: searchTerm
				}
			}));
		},

		/**
		 * @param {string|undefined} searchTerm
		 * @returns {undefined}
		 */
		_loadContacts: function(searchTerm) {
			var self = this;

			if (!self._contactsPromise) {
				self._contactsPromise = self._getContacts(searchTerm);
			}

			if (_.isUndefined(searchTerm) || searchTerm === '') {
				self._view.showLoading(t('core', 'Loading your contacts …'));
			} else {
				self._view.showLoading(t('core', 'Looking for {term} …', {
					term: searchTerm
				}));
			}
			return self._contactsPromise.then(function(data) {
				// Convert contact entries to Backbone collection
				data.contacts = new ContactCollection(data.contacts);

				self._view.showContacts(data, searchTerm);
			}, function(e) {
				self._view.showError();
				console.error('There was an error loading your contacts', e);
			}).then(function() {
				// Delete promise, so that contacts are fetched again when the
				// menu is opened the next time.
				delete self._contactsPromise;
			}).catch(console.error.bind(this));
		}
	};

	OC.ContactsMenu = ContactsMenu;

})(OC, $, _, Handlebars);
