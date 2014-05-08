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

	var FileActions = {
		actions: {},
		defaults: {},
		icons: {},
		currentFile: null,
		register: function (mime, name, permissions, icon, action, displayName) {
			if (!this.actions[mime]) {
				this.actions[mime] = {};
			}
			if (!this.actions[mime][name]) {
				this.actions[mime][name] = {};
			}
			if (!displayName) {
				displayName = t('files', name);
			}
			this.actions[mime][name]['action'] = action;
			this.actions[mime][name]['permissions'] = permissions;
			this.actions[mime][name]['displayName'] = displayName;
			this.icons[name] = icon;
		},
		clear: function() {
			this.actions = {};
			this.defaults = {};
			this.icons = {};
			this.currentFile = null;
		},
		setDefault: function (mime, name) {
			this.defaults[mime] = name;
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
		 */
		display: function (parent, triggerEvent) {
			this.currentFile = parent;
			var self = this;
			var actions = this.getActions(this.getCurrentMimeType(), this.getCurrentType(), this.getCurrentPermissions());
			var file = this.getCurrentFile();
			var nameLinks;
			if (parent.closest('tr').data('renaming')) {
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
				var file = self.getCurrentFile();

				event.data.actionFunc(file);
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
				if (img.call) {
					img = img(file);
				}
				if (typeof trashBinApp !== 'undefined' && trashBinApp) {
					html = '<a href="#" original-title="' + t('files', 'Delete permanently') + '" class="action delete delete-icon" />';
				} else {
					html = '<a href="#" class="action delete delete-icon" />';
				}
				var element = $(html);
				element.data('action', actions['Delete']);
				element.on('click', {a: null, elem: parent, actionFunc: actions['Delete'].action}, actionHandler);
				parent.parent().children().last().append(element);
			}

			if (triggerEvent){
				$('#fileList').trigger(jQuery.Event("fileActionsReady"));
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
		registerDefaultActions: function(fileList) {
			this.register('all', 'Delete', OC.PERMISSION_DELETE, function () {
				return OC.imagePath('core', 'actions/delete');
			}, function (filename) {
				fileList.do_delete(filename);
				$('.tipsy').remove();
			});

			// t('files', 'Rename')
			this.register('all', 'Rename', OC.PERMISSION_UPDATE, function () {
				return OC.imagePath('core', 'actions/rename');
			}, function (filename) {
				fileList.rename(filename);
			});

			this.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename) {
				var dir = fileList.getCurrentDirectory();
				if (dir !== '/') {
					dir = dir + '/';
				}
				fileList.changeDirectory(dir + filename);
			});

			this.setDefault('dir', 'Open');
			var downloadScope;
			if ($('#allowZipDownload').val() == 1) {
				downloadScope = 'all';
			} else {
				downloadScope = 'file';
			}

			this.register(downloadScope, 'Download', OC.PERMISSION_READ, function () {
				return OC.imagePath('core', 'actions/download');
			}, function (filename) {
				var url = OCA.Files.Files.getDownloadUrl(filename, fileList.getCurrentDirectory());
				if (url) {
					OC.redirect(url);
				}
			});

			fileList.$fileList.trigger(jQuery.Event("fileActionsReady"));
		}
	};

	OCA.Files.FileActions = FileActions;
})();

// for backward compatibility
window.FileActions = OCA.Files.FileActions;

