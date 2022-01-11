/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Files */

(function() {

	/**
	 * Construct a new NewFileMenu instance
	 * @constructs NewFileMenu
	 *
	 * @memberof OCA.Files
	 */
	var NewFileMenu = OC.Backbone.View.extend({
		tagName: 'div',
		// Menu is opened by default because it's rendered on "add-button" click
		className: 'newFileMenu popovermenu bubble menu open menu-left',

		events: {
			'click .menuitem': '_onClickAction'
		},

		/**
		 * @type OCA.Files.FileList
		 */
		fileList: null,

		initialize: function(options) {
			var self = this;
			var $uploadEl = $('#file_upload_start');
			if ($uploadEl.length) {
				$uploadEl.on('fileuploadstart', function() {
					self.trigger('actionPerformed', 'upload');
				});
			} else {
				console.warn('Missing upload element "file_upload_start"');
			}

			this.fileList = options && options.fileList;

			this._menuItems = [{
				id: 'folder',
				displayName: t('files', 'New folder'),
				templateName: t('files', 'New folder'),
				iconClass: 'icon-folder',
				fileType: 'folder',
				actionHandler: function(name) {
					const uniqueName = self.fileList.getUniqueName(name);
                	let tempPromise = self.fileList.createDirectory(uniqueName, { showDetailsView: false });
                	Promise.all([tempPromise]).then(() => {
                		self.fileList.rename(uniqueName);
                	});
				}
		        }];

			OC.Plugins.attach('OCA.Files.NewFileMenu', this);
		},

		template: function(data) {
			return OCA.Files.Templates['newfilemenu'](data);
		},

		/**
		 * Event handler whenever an action has been clicked within the menu
		 *
		 * @param {Object} event event object
		 */
		_onClickAction: function(event) {
			var $target = $(event.target);
			if (!$target.hasClass('menuitem')) {
				$target = $target.closest('.menuitem');
			}
			var action = $target.attr('data-action');
			// note: clicking the upload label will automatically
			// set the focus on the "file_upload_start" hidden field
			// which itself triggers the upload dialog.
			// Currently the upload logic is still in file-upload.js and filelist.js
			if (action === 'upload') {
				OC.hideMenus();
			} else {
				event.preventDefault();
				this.$el.find('.menuitem.active').removeClass('active');
				$target.addClass('active');
				this._promptFileName($target);
			}
		},

		_promptFileName: function($target) {
			var self = this;
			var newname = $target.attr('data-templatename');
        	var action = _.filter(self._menuItems, function(item) {
            	return item.id == $target.attr('data-action');
        	}).pop();
        	action.actionHandler(newname);
			OC.hideMenus();
		},

		/**
		* Add a new item menu entry in the “New” file menu (in
		* last position). By clicking on the item, the
		* `actionHandler` function is called.
		*
		* @param {Object} actionSpec item’s properties
		*/
		addMenuEntry: function(actionSpec) {
			this._menuItems.push({
				id: actionSpec.id,
				displayName: actionSpec.displayName,
				templateName: actionSpec.templateName,
				iconClass: actionSpec.iconClass,
				fileType: actionSpec.fileType,
				actionHandler: actionSpec.actionHandler,
				checkFilename: actionSpec.checkFilename
			});
		},

		/**
		 * Remove a menu item from the "New" file menu
		 * @param {string} actionId
		 */
		removeMenuEntry: function(actionId) {
			var index = this._menuItems.findIndex(function (actionSpec) {
				return actionSpec.id === actionId;
			});
			if (index > -1) {
				this._menuItems.splice(index, 1);
			}
		},

		/**
		 * Renders the menu with the currently set items
		 */
		render: function() {
			this.$el.html(this.template({
				uploadMaxHumanFileSize: 'TODO',
				uploadLabel: t('files', 'Upload file'),
				items: this._menuItems
			}));

			// Trigger upload action also with keyboard navigation on enter
			this.$el.find('[for="file_upload_start"]').on('keyup', function(event) {
				if (event.key === " " || event.key === "Enter") {
					$('#file_upload_start').trigger('click');
				}
			});
		},

		/**
		 * Displays the menu under the given element
		 *
		 * @param {Object} $target target element
		 */
		showAt: function($target) {
			this.render();
			OC.showMenu(null, this.$el);
		}
	});

	OCA.Files.NewFileMenu = NewFileMenu;

})();
