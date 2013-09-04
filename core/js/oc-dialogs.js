/**
 * ownCloud
 *
 * @author Bartek Przybylski, Christopher Schäpers, Thomas Tanghus
 * @copyright 2012 Bartek Przybylski bartek@alefzero.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * this class to ease the usage of jquery dialogs
 */
var OCdialogs = {
	// dialog button types
	YES_NO_BUTTONS:		70,
	OK_BUTTONS:		71,
	// used to name each dialog
	dialogs_counter: 0,
	/**
	* displays alert dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses OK
	* @param modal make the dialog modal
	*/
	alert:function(text, title, callback, modal) {
		this.message(text, title, 'alert', OCdialogs.OK_BUTTON, callback, modal);
	},
	/**
	* displays info dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses OK
	* @param modal make the dialog modal
	*/
	info:function(text, title, callback, modal) {
		this.message(text, title, 'info', OCdialogs.OK_BUTTON, callback, modal);
	},
	/**
	* displays confirmation dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses YES or NO (true or false would be passed to callback respectively)
	* @param modal make the dialog modal
	*/
	confirm:function(text, title, callback, modal) {
		this.message(text, title, 'notice', OCdialogs.YES_NO_BUTTONS, callback, modal);
	},
	/**
	 * show a file picker to pick a file from
	 * @param title dialog title
	 * @param callback which will be triggered when user presses Choose
	 * @param multiselect whether it should be possible to select multiple files
	 * @param mimetype_filter mimetype to filter by
	 * @param modal make the dialog modal
	*/
	filepicker:function(title, callback, multiselect, mimetype_filter, modal) {
		var self = this;
		$.when(this._getFilePickerTemplate()).then(function($tmpl) {
			var dialog_name = 'oc-dialog-filepicker-content';
			var dialog_id = '#' + dialog_name;
			if(self.$filePicker) {
				self.$filePicker.ocdialog('close');
			}
			self.$filePicker = $tmpl.octemplate({
				dialog_name: dialog_name,
				title: title
			}).data('path', '').data('multiselect', multiselect).data('mimetype', mimetype_filter);

			if (modal === undefined) {
				modal = false;
			}
			if (multiselect === undefined) {
				multiselect = false;
			}
			if (mimetype_filter === undefined) {
				mimetype_filter = '';
			}

			$('body').append(self.$filePicker);


			self.$filePicker.ready(function() {
				self.$filelist = self.$filePicker.find('.filelist');
				self.$dirTree = self.$filePicker.find('.dirtree');
				self.$dirTree.on('click', 'span:not(:last-child)', self, self._handleTreeListSelect);
				self.$filelist.on('click', 'li', function(event) {
					self._handlePickerClick(event, $(this));
				});
				self._fillFilePicker('');
			});

			// build buttons
			var functionToCall = function() {
				if (callback !== undefined) {
					var datapath;
					if (multiselect === true) {
						datapath = [];
						self.$filelist.find('.filepicker_element_selected .filename').each(function(index, element) {
							datapath.push(self.$filePicker.data('path') + '/' + $(element).text());
						});
					} else {
						datapath = self.$filePicker.data('path');
						datapath += '/' + self.$filelist.find('.filepicker_element_selected .filename').text();
					}
					callback(datapath);
					self.$filePicker.ocdialog('close');
				}
			};
			var buttonlist = [{
				text: t('core', 'Choose'),
				click: functionToCall,
				defaultButton: true
			}];

			self.$filePicker.ocdialog({
				closeOnEscape: true,
				width: (4/9)*$(document).width(),
				height: 420,
				modal: modal,
				buttons: buttonlist,
				close: function(event, ui) {
					try {
						$(this).ocdialog('destroy').remove();
					} catch(e) {}
					self.$filePicker = null;
				}
			});
		})
		.fail(function() {
			alert(t('core', 'Error loading file picker template'));
		});
	},
	/**
	 * Displays raw dialog
	 * You better use a wrapper instead ...
	*/
	message:function(content, title, dialog_type, buttons, callback, modal) {
		$.when(this._getMessageTemplate()).then(function($tmpl) {
			var dialog_name = 'oc-dialog-' + OCdialogs.dialogs_counter + '-content';
			var dialog_id = '#' + dialog_name;
			var $dlg = $tmpl.octemplate({
				dialog_name: dialog_name,
				title: title,
				message: content,
				type: dialog_type
			});
			if (modal === undefined) {
				modal = false;
			}
			$('body').append($dlg);
			var buttonlist = [];
			switch (buttons) {
				case OCdialogs.YES_NO_BUTTONS:
					buttonlist = [{
						text: t('core', 'Yes'),
						click: function(){
							if (callback !== undefined) {
								callback(true);
							}
							$(dialog_id).ocdialog('close');
						},
						defaultButton: true
					},
					{
						text: t('core', 'No'),
						click: function(){
							if (callback !== undefined) {
								callback(false);
							}
							$(dialog_id).ocdialog('close');
						}
					}];
				break;
				case OCdialogs.OK_BUTTON:
					var functionToCall = function() {
						$(dialog_id).ocdialog('close');
						if(callback !== undefined) {
							callback();
						}
					};
					buttonlist[0] = {
						text: t('core', 'Ok'),
						click: functionToCall,
						defaultButton: true
					};
				break;
			}

			$(dialog_id).ocdialog({
				closeOnEscape: true,
				modal: modal,
				buttons: buttonlist
			});
			OCdialogs.dialogs_counter++;
		})
		.fail(function() {
			alert(t('core', 'Error loading message template'));
		});
	},
	_fileexistsshown: false,
	/**
	 * Displays file exists dialog
	 * @param {object} data upload object
	 * @param {object} original file with name, size and mtime
	 * @param {object} replacement file with name, size and mtime
	 * @param {object} controller with onCancel, onSkip, onReplace and onRename methods
	*/
	fileexists:function(data, original, replacement, controller) {
		var self = this;
		var selection = controller.getSelection(data.originalFiles);
		if (selection.defaultAction) {
			controller[selection.defaultAction](data);
		} else {
			var dialog_name = 'oc-dialog-fileexists-content';
			var dialog_id = '#' + dialog_name;
			if (this._fileexistsshown) {
				// add row
				var conflict = $(dialog_id+ ' .conflict').last().clone();
				conflict.find('.name').text(original.name);
				conflict.find('.original .size').text(humanFileSize(original.size));
				conflict.find('.original .mtime').text(formatDate(original.mtime*1000));
				conflict.find('.replacement .size').text(humanFileSize(replacement.size));
				conflict.find('.replacement .mtime').text(formatDate(replacement.lastModifiedDate));
				getMimeIcon(original.type,function(path){
					conflict.find('.original .icon').css('background-image','url('+path+')');
				});
				getMimeIcon(replacement.type,function(path){
					conflict.find('.replacement .icon').css('background-image','url('+path+')');
				});
				$(dialog_id+' .conflict').last().after(conflict);
				$(dialog_id).parent().children('.oc-dialog-title').text(t('files','{count} file conflicts',{count:$(dialog_id+ ' .conflict').length}));
				
				//set more recent mtime bold
				if (replacement.lastModifiedDate.getTime() > original.mtime*1000) {
					conflict.find('.replacement .mtime').css('font-weight', 'bold');
				} else if (replacement.lastModifiedDate.getTime() < original.mtime*1000) {
					conflict.find('.original .mtime').css('font-weight', 'bold');
				} else {
					//TODO add to same mtime colletion?
				}
				
				// set bigger size bold
				if (replacement.size > original.size) {
					conflict.find('.replacement .size').css('font-weight','bold');
				} else if (replacement.size < original.size) {
					conflict.find('.original .size').css('font-weight','bold');
				} else {
					//TODO add to same size colletion?
				}
				
				//add checkbox toggling actions
				conflict.find('.replacement,.original').on('click', function(){
					var checkbox = $(this).find('input[type="checkbox"]');
					checkbox.prop('checkbox', !checkbox.prop('checkbox'));
				}).find('input[type="checkbox"]').prop('checkbox',false);
				
				//TODO show skip action for files with same size and mtime
				
				$(window).trigger('resize');
			} else {
				//create dialog
				this._fileexistsshown = true;
				$.when(this._getFileExistsTemplate()).then(function($tmpl) {
					var title = t('files','One file conflict');
					var original_size = humanFileSize(original.size);
					var original_mtime = formatDate(original.mtime*1000);
					var replacement_size= humanFileSize(replacement.size);
					var replacement_mtime = formatDate(replacement.lastModifiedDate);
					var $dlg = $tmpl.octemplate({
						dialog_name: dialog_name,
						title: title,
						type: 'fileexists',

						why: t('files','Which files do you want to keep?'),
						what: t('files','If you select both versions, the copied file will have a number added to its name.'),
						filename: original.name,
						
						original_size: original_size,
						original_mtime: original_mtime,

						replacement_size: replacement_size,
						replacement_mtime: replacement_mtime
					});
					$('body').append($dlg);

					getMimeIcon(original.type,function(path){
						$(dialog_id + ' .original .icon').css('background-image','url('+path+')');
					});
					getMimeIcon(replacement.type,function(path){
						$(dialog_id + ' .replacement .icon').css('background-image','url('+path+')');
					});
					$(dialog_id + ' #newname').val(original.name);

					$(dialog_id + ' #newname').on('keyup', function(e){
						if ($(dialog_id + ' #newname').val() === original.name) {
							$(dialog_id + ' + div .rename').removeClass('primary').hide();
							$(dialog_id + ' + div .replace').addClass('primary').show();
						} else {
							$(dialog_id + ' + div .rename').addClass('primary').show();
							$(dialog_id + ' + div .replace').removeClass('primary').hide();
						}
					});

					buttonlist = [{
							text: t('core', 'Cancel'),
							classes: 'cancel',
							click: function(){
								self._fileexistsshown = false;
								if ( typeof controller.onCancel !== 'undefined') {
									controller.onCancel(data);
								}
								$(dialog_id).ocdialog('close');
							}
						},
						{
							text: t('core', 'Continue'),
							classes: 'continue',
							click: function(){
								self._fileexistsshown = false;
								if ( typeof controller.onRename !== 'undefined') {
									//TODO use autorename when repeat is checked
									controller.onRename(data, $(dialog_id + ' #newname').val());
								}
								$(dialog_id).ocdialog('close');
							}
						}];

					$(dialog_id).ocdialog({
						width: 500,
						closeOnEscape: true,
						modal: true,
						buttons: buttonlist,
						closeButton: null
					});
					
					$(dialog_id + ' + div .rename').hide();
					$(dialog_id + ' #newname').hide();

					$(dialog_id + ' #newnamecb').on('change', function(){
						if ($(dialog_id + ' #newnamecb').prop('checked')) {
							$(dialog_id + ' #newname').fadeIn();
						} else {
							$(dialog_id + ' #newname').fadeOut();
							$(dialog_id + ' #newname').val(original.name);
						}
					});
					$(dialog_id).css('height','auto');

					var conflict = $(dialog_id + ' .conflict').last();
					//set more recent mtime bold
					if (replacement.lastModifiedDate.getTime() > original.mtime*1000) {
						conflict.find('.replacement .mtime').css('font-weight','bold');
					} else if (replacement.lastModifiedDate.getTime() < original.mtime*1000) {
						conflict.find('.original .mtime').css('font-weight','bold');
					} else {
						//TODO add to same mtime colletion?
					}

					// set bigger size bold
					if (replacement.size > original.size) {
						conflict.find('.replacement .size').css('font-weight','bold');
					} else if (replacement.size < original.size) {
						conflict.find('.original .size').css('font-weight','bold');
					} else {
						//TODO add to same size colletion?
					}
				
					//add checkbox toggling actions
					//add checkbox toggling actions
					$(dialog_id).find('.allnewfiles').on('click', function(){
						var checkboxes = $(dialog_id).find('.replacement input[type="checkbox"]');
						checkboxes.prop('checked', $(this).prop('checked'));
					});
					$(dialog_id).find('.allexistingfiles').on('click', function(){
						var checkboxes = $(dialog_id).find('.original input[type="checkbox"]');
						checkboxes.prop('checked', $(this).prop('checked'));
					});
					conflict.find('.replacement,.original').on('click', function(){
						var checkbox = $(this).find('input[type="checkbox"]');
						checkbox.prop('checked', !checkbox.prop('checked'));
					});
					
					//update counters
					$(dialog_id).on('click', '.replacement,.allnewfiles', function(){
						var count = $(dialog_id).find('.replacement input[type="checkbox"]:checked').length;
						if (count === $(dialog_id+ ' .conflict').length) {
							$(dialog_id).find('.allnewfiles').prop('checked', true);
							$(dialog_id).find('.allnewfiles + .count').text(t('files','(all selected)'));
						} else if (count > 0) {
							$(dialog_id).find('.allnewfiles').prop('checked', false);
							$(dialog_id).find('.allnewfiles + .count').text(t('files','({count} selected)',{count:count}));
						} else {
							$(dialog_id).find('.allnewfiles').prop('checked', false);
							$(dialog_id).find('.allnewfiles + .count').text('');
						}
					});
					$(dialog_id).on('click', '.original,.allexistingfiles', function(){
						var count = $(dialog_id).find('.original input[type="checkbox"]:checked').length;
						if (count === $(dialog_id+ ' .conflict').length) {
							$(dialog_id).find('.allexistingfiles').prop('checked', true);
							$(dialog_id).find('.allexistingfiles + .count').text(t('files','(all selected)'));
						} else if (count > 0) {
							$(dialog_id).find('.allexistingfiles').prop('checked', false);
							$(dialog_id).find('.allexistingfiles + .count').text(t('files','({count} selected)',{count:count}));
						} else {
							$(dialog_id).find('.allexistingfiles').prop('checked', false);
							$(dialog_id).find('.allexistingfiles + .count').text('');
						}
					});
				})
				.fail(function() {
					alert(t('core', 'Error loading file exists template'));
				});
			}
		}
	},
	_getFilePickerTemplate: function() {
		var defer = $.Deferred();
		if(!this.$filePickerTemplate) {
			var self = this;
			$.get(OC.filePath('core', 'templates', 'filepicker.html'), function(tmpl) {
				self.$filePickerTemplate = $(tmpl);
				self.$listTmpl = self.$filePickerTemplate.find('.filelist li:first-child').detach();
				defer.resolve(self.$filePickerTemplate);
			})
			.fail(function() {
				defer.reject();
			});
		} else {
			defer.resolve(this.$filePickerTemplate);
		}
		return defer.promise();
	},
	_getMessageTemplate: function() {
		var defer = $.Deferred();
		if(!this.$messageTemplate) {
			var self = this;
			$.get(OC.filePath('core', 'templates', 'message.html'), function(tmpl) {
				self.$messageTemplate = $(tmpl);
				defer.resolve(self.$messageTemplate);
			})
			.fail(function() {
				defer.reject();
			});
		} else {
			defer.resolve(this.$messageTemplate);
		}
		return defer.promise();
	},
	_getFileExistsTemplate: function () {
		var defer = $.Deferred();
		if (!this.$fileexistsTemplate) {
			var self = this;
			$.get(OC.filePath('files', 'templates', 'fileexists.html'), function (tmpl) {
				self.$fileexistsTemplate = $(tmpl);
				defer.resolve(self.$fileexistsTemplate);
			})
			.fail(function () {
				defer.reject();
			});
		} else {
			defer.resolve(this.$fileexistsTemplate);
		}
		return defer.promise();
	},
	_getFileList: function(dir, mimeType) {
		return $.getJSON(
			OC.filePath('files', 'ajax', 'rawlist.php'),
			{dir: dir, mimetype: mimeType}
		);
	},
	_determineValue: function(element) {
		if ( $(element).attr('type') === 'checkbox' ) {
			return element.checked;
		} else {
			return $(element).val();
		}
	},

	/**
	 * fills the filepicker with files
	*/
	_fillFilePicker:function(dir) {
		var dirs = [];
		var others = [];
		var self = this;
		this.$filelist.empty().addClass('loading');
		this.$filePicker.data('path', dir);
		$.when(this._getFileList(dir, this.$filePicker.data('mimetype'))).then(function(response) {
			$.each(response.data, function(index, file) {
				if (file.type === 'dir') {
					dirs.push(file);
				} else {
					others.push(file);
				}
			});

			self._fillSlug();
			var sorted = dirs.concat(others);

			$.each(sorted, function(idx, entry) {
				var $li = self.$listTmpl.octemplate({
					type: entry.type,
					dir: dir,
					filename: entry.name,
					date: OC.mtime2date(entry.mtime)
				});
				if (entry.mimetype === "httpd/unix-directory") {
					$li.find('img').attr('src', OC.imagePath('core', 'filetypes/folder.png'));
				} else {
					$li.find('img').attr('src', OC.Router.generate('core_ajax_preview', {x:32, y:32, file:escapeHTML(dir+'/'+entry.name)}) );
				}
				self.$filelist.append($li);
			});

			self.$filelist.removeClass('loading');
		});
	},
	/**
	 * fills the tree list with directories
	*/
	_fillSlug: function() {
		this.$dirTree.empty();
		var self = this;
		var path = this.$filePicker.data('path');
		var $template = $('<span data-dir="{dir}">{name}</span>');
		if(path) {
			var paths = path.split('/');
			$.each(paths, function(index, dir) {
				dir = paths.pop();
				if(dir === '') {
					return false;
				}
				self.$dirTree.prepend($template.octemplate({
					dir: paths.join('/') + '/' + dir,
					name: dir
				}));
			});
		}
		$template.octemplate({
			dir: '',
			name: '&nbsp;&nbsp;&nbsp;&nbsp;' // Ugly but works ;)
		}, {escapeFunction: null}).addClass('home svg').prependTo(this.$dirTree);
	},
	/**
	 * handle selection made in the tree list
	*/
	_handleTreeListSelect:function(event) {
		var self = event.data;
		var dir = $(event.target).data('dir');
		self._fillFilePicker(dir);
	},
	/**
	 * handle clicks made in the filepicker
	*/
	_handlePickerClick:function(event, $element) {
		if ($element.data('type') === 'file') {
			if (this.$filePicker.data('multiselect') !== true || !event.ctrlKey) {
				this.$filelist.find('.filepicker_element_selected').removeClass('filepicker_element_selected');
			}
			$element.toggleClass('filepicker_element_selected');
			return;
		} else if ( $element.data('type') === 'dir' ) {
			this._fillFilePicker(this.$filePicker.data('path') + '/' + $element.data('entryname'));
		}
	}
};
