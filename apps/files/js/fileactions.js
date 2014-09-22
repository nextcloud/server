/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global trashBinApp */
(function() {

	/**
	 * Construct a new FileActions instance
	 */
	var FileActions = function() {
		this.initialize();
	}
	FileActions.prototype = {
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
		 */
		_updateListeners: {},

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
		 * @param Function callback
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
		 * @param fileActions instance of OCA.Files.FileActions
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
				displayName: displayName
			});
		},
		/**
		 * Register action
		 *
		 * @param {Object} action action object
		 * @param {String} action.name identifier of the action
		 * @param {String} action.displayName display name of the action, defaults
		 * to the name given in action.name
		 * @param {String} action.mime mime type
		 * @param {int} action.permissions permissions
		 * @param {(Function|String)} action.icon icon
		 * @param {Function} action.actionHandler function that performs the action
		 */
		registerAction: function (action) {
			var mime = action.mime;
			var name = action.name;
			if (!this.actions[mime]) {
				this.actions[mime] = {};
			}
			this.actions[mime][name] = {
				action: action.actionHandler,
				permissions: action.permissions,
				displayName: action.displayName || t('files', name)
			};
			this.icons[name] = action.icon;
			this._notifyUpdateListeners('registerAction', {action: action});
		},
		clear: function() {
			this.actions = {};
			this.defaults = {};
			this.icons = {};
			this.currentFile = null;
			this._updateListeners = [];
		},
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
			var $tr = parent.closest('tr');
			var self = this;
			var actions = this.getActions(this.getCurrentMimeType(), this.getCurrentType(), this.getCurrentPermissions());
			var file = this.getCurrentFile();
			var nameLinks;
			if ($tr.data('renaming')) {
				return;
			}

			// recreate fileactions
			nameLinks = parent.children('a.name');
			nameLinks.find('.fileactions, .nametext .action').remove();
			nameLinks.append('<span class="fileactions" />');
			var defaultAction = this.getDefault(this.getCurrentMimeType(), this.getCurrentType(), this.getCurrentPermissions());

			var actionHandler = function (event) {
				event.stopPropagation();
				event.preventDefault();

				self.currentFile = event.data.elem;
				// also set on global object for legacy apps
				window.FileActions.currentFile = self.currentFile;

				var file = self.getCurrentFile();
				var $tr = $(this).closest('tr');

				event.data.actionFunc(file, {
					$file: $tr,
					fileList: fileList,
					fileActions: self,
					dir: $tr.attr('data-path') || fileList.getCurrentDirectory()
				});
			};

			var addAction = function (name, action, displayName) {

				if ((name === 'Download' || action !== defaultAction) && name !== 'Delete') {

					var img = self.icons[name],
						actionText = displayName,
						actionContainer = 'a.name>span.fileactions';

					if (name === 'Rename') {
						// rename has only an icon which appears behind
						// the file name
						actionText = '';
						actionContainer = 'a.name span.nametext';
					}
					if (img.call) {
						img = img(file);
					}
					var html = '<a href="#" class="action action-' + name.toLowerCase() + '" data-action="' + name + '">';
					if (img) {
						html += '<img class ="svg" src="' + img + '" />';
					}
					html += '<span> ' + actionText + '</span></a>';

					var element = $(html);
					element.data('action', name);
					element.on('click', {a: null, elem: parent, actionFunc: actions[name].action}, actionHandler);
					parent.find(actionContainer).append(element);
				}

			};

			$.each(actions, function (name, action) {
				if (name !== 'Share') {
					displayName = action.displayName;
					ah = action.action;

					addAction(name, ah, displayName);
				}
			});
			if(actions.Share){
				displayName = t('files', 'Share');
				addAction('Share', actions.Share, displayName);
			}

			// remove the existing delete action
			parent.parent().children().last().find('.action.delete').remove();
			if (actions['Delete']) {
				var img = self.icons['Delete'];
				var html;
				var mountType = $tr.attr('data-mounttype');
				var deleteTitle = t('files', 'Delete');
				if (mountType === 'external-root') {
					deleteTitle = t('files', 'Disconnect storage');
				} else if (mountType === 'shared-root') {
					deleteTitle = t('files', 'Unshare');
				} else if (fileList.id === 'trashbin') {
					deleteTitle = t('files', 'Delete permanently');
				}

				if (img.call) {
					img = img(file);
				}
				html = '<a href="#" original-title="' + escapeHTML(deleteTitle) + '" class="action delete icon-delete" />';
				var element = $(html);
				element.data('action', actions['Delete']);
				element.on('click', {a: null, elem: parent, actionFunc: actions['Delete'].action}, actionHandler);
				parent.parent().children().last().append(element);
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
			this.register('all', 'Delete', OC.PERMISSION_DELETE, function () {
				return OC.imagePath('core', 'actions/delete');
			}, function (filename, context) {
				context.fileList.do_delete(filename, context.dir);
				$('.tipsy').remove();
			});

			// t('files', 'Rename')
			this.register('all', 'Rename', OC.PERMISSION_UPDATE, function () {
				return OC.imagePath('core', 'actions/rename');
			}, function (filename, context) {
				context.fileList.rename(filename);
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
			});
		}
	};

	OCA.Files.FileActions = FileActions;

	// global file actions to be used by all lists
	OCA.Files.fileActions = new OCA.Files.FileActions();
	OCA.Files.legacyFileActions = new OCA.Files.FileActions();

	// for backward compatibility
	// 
	// legacy apps are expecting a stateful global FileActions object to register
	// their actions on. Since legacy apps are very likely to break with other
	// FileList views than the main one ("All files"), actions registered
	// through window.FileActions will be limited to the main file list.
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

