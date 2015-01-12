/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {

	/**
	 * Construct a new FileActions instance
	 * @constructs FileActions
	 * @memberof OCA.Files
	 */
	var FileActions = function() {
		this.initialize();
	};
	FileActions.prototype = {
		/** @lends FileActions.prototype */
		actions: {},
		defaults: {},
		icons: {},
		currentFile: null,

		/**
		 * Dummy jquery element, for events
		 */
		$el: null,

		/**
		 * List of handlers to be notified whenever a register() or
		 * setDefault() was called.
		 *
		 * @member {Function[]}
		 */
		_updateListeners: {},

		/**
		 * @private
		 */
		initialize: function() {
			this.clear();
			// abusing jquery for events until we get a real event lib
			this.$el = $('<div class="dummy-fileactions hidden"></div>');
			$('body').append(this.$el);
		},

		/**
		 * Adds an event handler
		 *
		 * @param {String} eventName event name
		 * @param {Function} callback
		 */
		on: function(eventName, callback) {
			this.$el.on(eventName, callback);
		},

		/**
		 * Removes an event handler
		 *
		 * @param {String} eventName event name
		 * @param Function callback
		 */
		off: function(eventName, callback) {
			this.$el.off(eventName, callback);
		},

		/**
		 * Notifies the event handlers
		 *
		 * @param {String} eventName event name
		 * @param {Object} data data
		 */
		_notifyUpdateListeners: function(eventName, data) {
			this.$el.trigger(new $.Event(eventName, data));
		},

		/**
		 * Merges the actions from the given fileActions into
		 * this instance.
		 *
		 * @param {OCA.Files.FileActions} fileActions instance of OCA.Files.FileActions
		 */
		merge: function(fileActions) {
			var self = this;
			// merge first level to avoid unintended overwriting
			_.each(fileActions.actions, function(sourceMimeData, mime) {
				var targetMimeData = self.actions[mime];
				if (!targetMimeData) {
					targetMimeData = {};
				}
				self.actions[mime] = _.extend(targetMimeData, sourceMimeData);
			});

			this.defaults = _.extend(this.defaults, fileActions.defaults);
			this.icons = _.extend(this.icons, fileActions.icons);
		},
		/**
		 * @deprecated use #registerAction() instead
		 */
		register: function(mime, name, permissions, icon, action, displayName) {
			return this.registerAction({
				name: name,
				mime: mime,
				permissions: permissions,
				icon: icon,
				actionHandler: action,
				displayName: displayName || name
			});
		},
		/**
		 * Register action
		 *
		 * @param {OCA.Files.FileAction} action object
		 */
		registerAction: function (action) {
			var mime = action.mime;
			var name = action.name;
			var actionSpec = {
				action: action.actionHandler,
				name: name,
				displayName: action.displayName,
				mime: mime,
				icon: action.icon,
				permissions: action.permissions
			};
			if (_.isUndefined(action.displayName)) {
				actionSpec.displayName = t('files', name);
			}
			if (_.isFunction(action.render)) {
				actionSpec.render = action.render;
			} else {
				actionSpec.render = _.bind(this._defaultRenderAction, this);
			}
			if (!this.actions[mime]) {
				this.actions[mime] = {};
			}
			this.actions[mime][name] = actionSpec;
			this.icons[name] = action.icon;
			this._notifyUpdateListeners('registerAction', {action: action});
		},
		/**
		 * Clears all registered file actions.
		 */
		clear: function() {
			this.actions = {};
			this.defaults = {};
			this.icons = {};
			this.currentFile = null;
			this._updateListeners = [];
		},
		/**
		 * Sets the default action for a given mime type.
		 *
		 * @param {String} mime mime type
		 * @param {String} name action name
		 */
		setDefault: function (mime, name) {
			this.defaults[mime] = name;
			this._notifyUpdateListeners('setDefault', {defaultAction: {mime: mime, name: name}});
		},
		get: function (mime, type, permissions) {
			var actions = this.getActions(mime, type, permissions);
			var filteredActions = {};
			$.each(actions, function (name, action) {
				filteredActions[name] = action.action;
			});
			return filteredActions;
		},
		getActions: function (mime, type, permissions) {
			var actions = {};
			if (this.actions.all) {
				actions = $.extend(actions, this.actions.all);
			}
			if (type) {//type is 'dir' or 'file'
				if (this.actions[type]) {
					actions = $.extend(actions, this.actions[type]);
				}
			}
			if (mime) {
				var mimePart = mime.substr(0, mime.indexOf('/'));
				if (this.actions[mimePart]) {
					actions = $.extend(actions, this.actions[mimePart]);
				}
				if (this.actions[mime]) {
					actions = $.extend(actions, this.actions[mime]);
				}
			}
			var filteredActions = {};
			$.each(actions, function (name, action) {
				if (action.permissions & permissions) {
					filteredActions[name] = action;
				}
			});
			return filteredActions;
		},
		getDefault: function (mime, type, permissions) {
			var mimePart;
			if (mime) {
				mimePart = mime.substr(0, mime.indexOf('/'));
			}
			var name = false;
			if (mime && this.defaults[mime]) {
				name = this.defaults[mime];
			} else if (mime && this.defaults[mimePart]) {
				name = this.defaults[mimePart];
			} else if (type && this.defaults[type]) {
				name = this.defaults[type];
			} else {
				name = this.defaults.all;
			}
			var actions = this.get(mime, type, permissions);
			return actions[name];
		},
		/**
		 * Default function to render actions
		 *
		 * @param {OCA.Files.FileAction} actionSpec file action spec
		 * @param {boolean} isDefault true if the action is a default one,
		 * false otherwise
		 * @param {OCA.Files.FileActionContext} context action context
		 */
		_defaultRenderAction: function(actionSpec, isDefault, context) {
			var name = actionSpec.name;
			if (name === 'Download' || !isDefault) {
				var $actionLink = this._makeActionLink(actionSpec, context);
				context.$file.find('a.name>span.fileactions').append($actionLink);
				return $actionLink;
			}
		},
		/**
		 * Renders the action link element
		 *
		 * @param {OCA.Files.FileAction} actionSpec action object
		 * @param {OCA.Files.FileActionContext} context action context
		 */
		_makeActionLink: function(actionSpec, context) {
			var img = actionSpec.icon;
			if (img && img.call) {
				img = img(context.$file.attr('data-file'));
			}
			var html = '<a href="#">';
			if (img) {
				html += '<img class="svg" alt="" src="' + img + '" />';
			}
			if (actionSpec.displayName) {
				html += '<span> ' + actionSpec.displayName + '</span>';
			}
			html += '</a>';

			return $(html);
		},
		/**
		 * Custom renderer for the "Rename" action.
		 * Displays the rename action as an icon behind the file name.
		 *
		 * @param {OCA.Files.FileAction} actionSpec file action to render
		 * @param {boolean} isDefault true if the action is a default action,
		 * false otherwise
		 * @param {OCAFiles.FileActionContext} context rendering context
		 */
		_renderRenameAction: function(actionSpec, isDefault, context) {
			var $actionEl = this._makeActionLink(actionSpec, context);
			var $container = context.$file.find('a.name span.nametext');
			$actionEl.find('img').attr('alt', t('files', 'Rename'));
			$container.find('.action-rename').remove();
			$container.append($actionEl);
			return $actionEl;
		},
		/**
		 * Custom renderer for the "Delete" action.
		 * Displays the "Delete" action as a trash icon at the end of
		 * the table row.
		 *
		 * @param {OCA.Files.FileAction} actionSpec file action to render
		 * @param {boolean} isDefault true if the action is a default action,
		 * false otherwise
		 * @param {OCAFiles.FileActionContext} context rendering context
		 */
		_renderDeleteAction: function(actionSpec, isDefault, context) {
			var mountType = context.$file.attr('data-mounttype');
			var deleteTitle = t('files', 'Delete');
			if (mountType === 'external-root') {
				deleteTitle = t('files', 'Disconnect storage');
			} else if (mountType === 'shared-root') {
				deleteTitle = t('files', 'Unshare');
			}
			var $actionLink = $('<a href="#" original-title="' +
				escapeHTML(deleteTitle) +
				'" class="action delete icon-delete">' +
				'<span class="hidden-visually">' + escapeHTML(deleteTitle) + '</span>' +
				'</a>'
			);
			var $container = context.$file.find('td:last');
			$container.find('.delete').remove();
			$container.append($actionLink);
			return $actionLink;
		},
		/**
		 * Renders the action element by calling actionSpec.render() and
		 * registers the click event to process the action.
		 *
		 * @param {OCA.Files.FileAction} actionSpec file action to render
		 * @param {boolean} isDefault true if the action is a default action,
		 * false otherwise
		 * @param {OCAFiles.FileActionContext} context rendering context
		 */
		_renderAction: function(actionSpec, isDefault, context) {
			var $actionEl = actionSpec.render(actionSpec, isDefault, context);
			if (!$actionEl || !$actionEl.length) {
				return;
			}
			$actionEl.addClass('action action-' + actionSpec.name.toLowerCase());
			$actionEl.attr('data-action', actionSpec.name);
			$actionEl.on(
				'click', {
					a: null
				},
				function(event) {
					var $file = $(event.target).closest('tr');
					var currentFile = $file.find('td.filename');
					var fileName = $file.attr('data-file');
					event.stopPropagation();
					event.preventDefault();

					context.fileActions.currentFile = currentFile;
					// also set on global object for legacy apps
					window.FileActions.currentFile = currentFile;

					actionSpec.action(
						fileName,
						_.extend(context, {
							dir: $file.attr('data-path') || context.fileList.getCurrentDirectory()
						})
					);
				}
			);
			return $actionEl;
		},
		/**
		 * Display file actions for the given element
		 * @param parent "td" element of the file for which to display actions
		 * @param triggerEvent if true, triggers the fileActionsReady on the file
		 * list afterwards (false by default)
		 * @param fileList OCA.Files.FileList instance on which the action is
		 * done, defaults to OCA.Files.App.fileList
		 */
		display: function (parent, triggerEvent, fileList) {
			if (!fileList) {
				console.warn('FileActions.display() MUST be called with a OCA.Files.FileList instance');
				return;
			}
			this.currentFile = parent;
			var self = this;
			var $tr = parent.closest('tr');
			var actions = this.getActions(
				this.getCurrentMimeType(),
				this.getCurrentType(),
				this.getCurrentPermissions()
			);
			var nameLinks;
			if ($tr.data('renaming')) {
				return;
			}

			// recreate fileactions container
			nameLinks = parent.children('a.name');
			nameLinks.find('.fileactions, .nametext .action').remove();
			nameLinks.append('<span class="fileactions" />');
			var defaultAction = this.getDefault(
				this.getCurrentMimeType(),
				this.getCurrentType(),
				this.getCurrentPermissions()
			);

			$.each(actions, function (name, actionSpec) {
				if (name !== 'Share') {
					self._renderAction(
						actionSpec,
						actionSpec.action === defaultAction, {
							$file: $tr,
							fileActions: this,
							fileList : fileList
						}
					);
				}
			});
			// added here to make sure it's always the last action
			var shareActionSpec = actions.Share;
			if (shareActionSpec){
				this._renderAction(
					shareActionSpec,
					shareActionSpec.action === defaultAction, {
						$file: $tr,
						fileActions: this,
						fileList: fileList
					}
				);
			}

			if (triggerEvent){
				fileList.$fileList.trigger(jQuery.Event("fileActionsReady", {fileList: fileList, $files: $tr}));
			}
		},
		getCurrentFile: function () {
			return this.currentFile.parent().attr('data-file');
		},
		getCurrentMimeType: function () {
			return this.currentFile.parent().attr('data-mime');
		},
		getCurrentType: function () {
			return this.currentFile.parent().attr('data-type');
		},
		getCurrentPermissions: function () {
			return this.currentFile.parent().data('permissions');
		},

		/**
		 * Register the actions that are used by default for the files app.
		 */
		registerDefaultActions: function() {
			this.registerAction({
				name: 'Delete',
				displayName: '',
				mime: 'all',
				permissions: OC.PERMISSION_DELETE,
				icon: function() {
					return OC.imagePath('core', 'actions/delete');
				},
				render: _.bind(this._renderDeleteAction, this),
				actionHandler: function(fileName, context) {
					context.fileList.do_delete(fileName, context.dir);
					$('.tipsy').remove();
				}
			});

			// t('files', 'Rename')
			this.registerAction({
				name: 'Rename',
				displayName: '',
				mime: 'all',
				permissions: OC.PERMISSION_UPDATE,
				icon: function() {
					return OC.imagePath('core', 'actions/rename');
				},
				render: _.bind(this._renderRenameAction, this),
				actionHandler: function (filename, context) {
					context.fileList.rename(filename);
				}
			});

			this.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
				var dir = context.$file.attr('data-path') || context.fileList.getCurrentDirectory();
				if (dir !== '/') {
					dir = dir + '/';
				}
				context.fileList.changeDirectory(dir + filename);
			});

			this.setDefault('dir', 'Open');

			this.register('all', 'Download', OC.PERMISSION_READ, function () {
				return OC.imagePath('core', 'actions/download');
			}, function (filename, context) {
				var dir = context.dir || context.fileList.getCurrentDirectory();
				var url = context.fileList.getDownloadUrl(filename, dir);
				if (url) {
					OC.redirect(url);
				}
			}, t('files', 'Download'));
		}
	};

	OCA.Files.FileActions = FileActions;

	/**
	 * File action attributes.
	 *
	 * @todo make this a real class in the future
	 * @typedef {Object} OCA.Files.FileAction
	 *
	 * @property {String} name identifier of the action
	 * @property {String} displayName display name of the action, defaults
	 * to the name given in name property
	 * @property {String} mime mime type
	 * @property {int} permissions permissions
	 * @property {(Function|String)} icon icon path to the icon or function
	 * that returns it
	 * @property {OCA.Files.FileActions~renderActionFunction} [render] optional rendering function
	 * @property {OCA.Files.FileActions~actionHandler} actionHandler action handler function
	 */

	/**
	 * File action context attributes.
	 *
	 * @typedef {Object} OCA.Files.FileActionContext
	 *
	 * @property {Object} $file jQuery file row element
	 * @property {OCA.Files.FileActions} fileActions file actions object
	 * @property {OCA.Files.FileList} fileList file list object
	 */

	/**
	 * Render function for actions.
	 * The function must render a link element somewhere in the DOM
	 * and return it. The function should NOT register the event handler
	 * as this will be done after the link was returned.
	 *
	 * @callback OCA.Files.FileActions~renderActionFunction
	 * @param {OCA.Files.FileAction} actionSpec action definition
	 * @param {Object} $row row container
	 * @param {boolean} isDefault true if the action is the default one,
	 * false otherwise
	 * @return {Object} jQuery link object
	 */

	/**
	 * Action handler function for file actions
	 *
	 * @callback OCA.Files.FileActions~actionHandler
	 * @param {String} fileName name of the clicked file
	 * @param context context
	 * @param {String} context.dir directory of the file
	 * @param context.$file jQuery element of the file
	 * @param {OCA.Files.FileList} context.fileList the FileList instance on which the action occurred
	 * @param {OCA.Files.FileActions} context.fileActions the FileActions instance on which the action occurred
	 */

	// global file actions to be used by all lists
	OCA.Files.fileActions = new OCA.Files.FileActions();
	OCA.Files.legacyFileActions = new OCA.Files.FileActions();

	// for backward compatibility
	// 
	// legacy apps are expecting a stateful global FileActions object to register
	// their actions on. Since legacy apps are very likely to break with other
	// FileList views than the main one ("All files"), actions registered
	// through window.FileActions will be limited to the main file list.
	// @deprecated use OCA.Files.FileActions instead
	window.FileActions = OCA.Files.legacyFileActions;
	window.FileActions.register = function (mime, name, permissions, icon, action, displayName) {
		console.warn('FileActions.register() is deprecated, please use OCA.Files.fileActions.register() instead', arguments);
		OCA.Files.FileActions.prototype.register.call(
				window.FileActions, mime, name, permissions, icon, action, displayName
		);
	};
	window.FileActions.display = function (parent, triggerEvent, fileList) {
		fileList = fileList || OCA.Files.App.fileList;
		console.warn('FileActions.display() is deprecated, please use OCA.Files.fileActions.register() which automatically redisplays actions', mime, name);
		OCA.Files.FileActions.prototype.display.call(window.FileActions, parent, triggerEvent, fileList);
	};
})();

