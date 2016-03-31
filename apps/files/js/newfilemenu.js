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

	var TEMPLATE_MENU =
		'<ul>' +
		'<li>' +
		'<label for="file_upload_start" class="menuitem" data-action="upload" title="{{uploadMaxHumanFilesize}}"><span class="svg icon icon-upload"></span><span class="displayname">{{uploadLabel}}</span></label>' +
		'</li>' +
		'{{#each items}}' +
		'<li>' +
		'<a href="#" class="menuitem" data-templatename="{{templateName}}" data-filetype="{{fileType}}" data-action="{{id}}"><span class="icon {{iconClass}} svg"></span><span class="displayname">{{displayName}}</span></a>' +
		'</li>' +
		'{{/each}}' +
		'</ul>';

	var TEMPLATE_FILENAME_FORM =
		'<form class="filenameform">' +
		'<label class="hidden-visually" for="{{cid}}-input-{{fileType}}">{{fileName}}</label>' +
		'<input id="{{cid}}-input-{{fileType}}" type="text" value="{{fileName}}" autocomplete="off" autocapitalize="off">' +
		'</form>';

	/**
	 * Construct a new NewFileMenu instance
	 * @constructs NewFileMenu
	 *
	 * @memberof OCA.Files
	 */
	var NewFileMenu = OC.Backbone.View.extend({
		tagName: 'div',
		className: 'newFileMenu popovermenu bubble hidden open menu',

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
				displayName: t('files', 'Folder'),
				templateName: t('files', 'New folder'),
				iconClass: 'icon-folder',
				fileType: 'folder',
				actionHandler: function(name) {
					self.fileList.createDirectory(name);
				}
		        }];

			OC.Plugins.attach('OCA.Files.NewFileMenu', this);
		},

		template: function(data) {
			if (!OCA.Files.NewFileMenu._TEMPLATE) {
				OCA.Files.NewFileMenu._TEMPLATE = Handlebars.compile(TEMPLATE_MENU);
			}
			return OCA.Files.NewFileMenu._TEMPLATE(data);
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
			if (!OCA.Files.NewFileMenu._TEMPLATE_FORM) {
				OCA.Files.NewFileMenu._TEMPLATE_FORM = Handlebars.compile(TEMPLATE_FILENAME_FORM);
			}

			if ($target.find('form').length) {
				$target.find('input').focus();
				return;
			}

			// discard other forms
			this.$el.find('form').remove();
			this.$el.find('.displayname').removeClass('hidden');

			$target.find('.displayname').addClass('hidden');

			var newName = $target.attr('data-templatename');
			var fileType = $target.attr('data-filetype');
			var $form = $(OCA.Files.NewFileMenu._TEMPLATE_FORM({
				fileName: newName,
				cid: this.cid,
				fileType: fileType
			}));

			//this.trigger('actionPerformed', action);
			$target.append($form);

			// here comes the OLD code
			var $input = $form.find('input');

			var lastPos;
			var checkInput = function () {
				var filename = $input.val();
				try {
					if (!Files.isFileNameValid(filename)) {
						// Files.isFileNameValid(filename) throws an exception itself
					} else if (self.fileList.inList(filename)) {
						throw t('files', '{newname} already exists', {newname: filename});
					} else {
						return true;
					}
				} catch (error) {
					$input.attr('title', error);
					$input.tooltip({placement: 'right', trigger: 'manual'});
					$input.tooltip('show');
					$input.addClass('error');
				}
				return false;
			};

			// verify filename on typing
			$input.keyup(function() {
				if (checkInput()) {
					$input.tooltip('hide');
					$input.removeClass('error');
				}
			});

			$input.focus();
			// pre select name up to the extension
			lastPos = newName.lastIndexOf('.');
			if (lastPos === -1) {
				lastPos = newName.length;
			}
			$input.selectRange(0, lastPos);

			$form.submit(function(event) {
				event.stopPropagation();
				event.preventDefault();

				if (checkInput()) {
					var newname = $input.val();

					/* Find the right actionHandler that should be called.
					 * Actions is retrieved by using `actionSpec.id` */
					action = _.filter(self._menuItems, function(item) {
						return item.id == $target.attr('data-action');
					}).pop();
					action.actionHandler(newname);

					$form.remove();
					$target.find('.displayname').removeClass('hidden');
					OC.hideMenus();
				}
			});
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
		        });
		},

		/**
		 * Renders the menu with the currently set items
		 */
		render: function() {
			this.$el.html(this.template({
				uploadMaxHumanFileSize: 'TODO',
				uploadLabel: t('files', 'Upload'),
				items: this._menuItems
			}));
			OC.Util.scaleFixForIE8(this.$('.svg'));
		},

		/**
		 * Displays the menu under the given element
		 *
		 * @param {Object} $target target element
		 */
		showAt: function($target) {
			this.render();
			var targetOffset = $target.offset();
			this.$el.css({
				left: targetOffset.left,
				top: targetOffset.top + $target.height()
			});
			this.$el.removeClass('hidden');

			OC.showMenu(null, this.$el);
		}
	});

	OCA.Files.NewFileMenu = NewFileMenu;

})();
