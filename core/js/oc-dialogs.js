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
	/**
	* displays alert dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses OK
	* @param modal make the dialog modal
	*/
	alert:function(text, title, callback, modal) {
		OCdialogs.message(text, title, 'alert', OCdialogs.OK_BUTTON, callback, modal);
	},
	/**
	* displays info dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses OK
	* @param modal make the dialog modal
	*/
	info:function(text, title, callback, modal) {
		OCdialogs.message(text, title, 'info', OCdialogs.OK_BUTTON, callback, modal);
	},
	/**
	* displays confirmation dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses YES or NO (true or false would be passed to callback respectively)
	* @param modal make the dialog modal
	*/
	confirm:function(text, title, callback, modal) {
		OCdialogs.message(text, title, 'notice', OCdialogs.YES_NO_BUTTONS, callback, modal);
	},
	/**
	* prompt for user input
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses OK (input text will be passed to callback)
	* @param modal make the dialog modal
	*/
	prompt:function(text, title, default_value, callback, modal) {
		var input = '<input type="text" id="oc-dialog-prompt-input" value="' + escapeHTML(default_value) + '" style="width:90%">';
		var content = '<p><span class="ui-icon ui-icon-pencil"></span>' + escapeHTML(text) + ':<br/>' + input + '</p>';
		OCdialogs.message(content, title, 'prompt', OCdialogs.OK_BUTTON, callback, modal);
	},
	/**
	* prompt user for input with custom form
	* fields should be passed in following format: [{text:'prompt text', name:'return name', type:'input type', value: 'default value'},...]
	* example:
	* var fields=[{text:'Test', name:'test', type:'select', options:[{text:'hello1',value:1},{text:'hello2',value:2}] }];
	* @param fields to display 
	* @param title dialog title
	* @param callback which will be triggered when user presses OK (user answers will be passed to callback in following format: [{name:'return name', value: 'user value'},...])
	* @param modal make the dialog modal
	*/
	form:function(fields, title, callback, modal) {
		var content = '<table>';
		$.each(fields, function(index, field){
			content += '<tr><td>' + escapeHTML(field.text) + '</td><td>';
			var type = field.type;
			
			if (type === 'text' || type === 'checkbox' || type === 'password') {
				content += '<input type="' + type + '" name="' + field.name + '"';
				if (type === 'checkbox' && field.value === true) {
					content += ' checked="checked"';
				} else if (type === 'text' || type === 'password' && val.value) {
					content += ' value="' + escapeHTML(field.value) + '"';
				}
				content += '>';
			} else if (type === 'select') {
				content += '<select name="' + escapeHTML(field.name) + '"';
				if (field.value !== undefined) {
					content += ' value="' + escapeHTML(field.value) + '"';
				}
				content += '>';
				$.each(field.options, function(index, field_option){
					content += '<option value="' + escapeHTML(field_option.value) + '">' + escapeHTML(field_option.text) + '</option>';
				});
				content += '</select>';
			}
			content += '</td></tr>';

		});
		content += '</table>';

		var dialog_name = 'oc-dialog-' + OCdialogs.dialogs_counter + '-content';
		var dialog_id = '#' + dialog_name;
		var dialog_div = '<div id="' + dialog_name + '" title="' + escapeHTML(title) + '">' + content + '</div>';
		if (modal === undefined) { modal = false };
		$('body').append(dialog_div);
		var buttonlist = [{
			text: t('core', 'Ok'),
			click: function(){ OCdialogs.form_ok_handler(callback, dialog_id); }
		},
		{
			text: t('core', 'Cancel'),
			click: function(){ $(dialog_id).dialog('close'); }
		}];
		var dialog_height = ( $('tr', dialog_div).length + 1 ) * 30 + 120;
		$(dialog_id).dialog({
			width: (4/9) * $(document).width(),
			height: dialog_height,
			modal: modal,
			buttons: buttonlist
		});
		OCdialogs.dialogs_counter++;
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
	_getFileList: function(dir, mimeType) {
		return $.getJSON(
			OC.filePath('files', 'ajax', 'rawlist.php'),
			{dir: dir, mimetype: mimeType}
		);
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
				self.$filePicker.dialog('close');
			}
			self.$filePicker = $tmpl.octemplate({
				dialog_name: dialog_name,
				title: title
			}).data('path', '');

			if (modal === undefined) { modal = false };
			if (multiselect === undefined) { multiselect = false };
			if (mimetype_filter === undefined) { mimetype_filter = '' };

			$('body').append(self.$filePicker);


			self.$filePicker.ready(function() {
				self.$filelist = self.$filePicker.find('.filelist');
				self.$dirUp = self.$filePicker.find('.dirup');
				self.$dirTree = self.$filePicker.find('.dirtree');
				self.$dirTree.on('click', 'span', self, self.handleTreeListSelect);
				self.$dirUp.click(self, self.filepickerDirUp);
				self.$filelist.on('click', 'li', function(event) {
					self.handlePickerClick(event, $(this));
				});
				self.fillFilePicker('');
			}).data('multiselect', multiselect).data('mimetype',mimetype_filter);

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
						var datapath = self.$filePicker.data('path');
						datapath += '/' + self.$filelist.find('.filepicker_element_selected .filename').text();
					}
					callback(datapath);
					self.$filePicker.dialog('close');
				}
			};
			var buttonlist = [{
				text: t('core', 'Choose'),
				click: functionToCall
				},
				{
				text: t('core', 'Cancel'),
				click: function(){self.$filePicker.dialog('close'); }
			}];

			self.$filePicker.dialog({
				width: (4/9)*$(document).width(),
				height: 420,
				modal: modal,
				buttons: buttonlist,
				close: function(event, ui) {
					self.$filePicker.dialog('destroy').remove();
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
			if (modal === undefined) { modal = false };
			$('body').append($dlg);
			var buttonlist = [];
			switch (buttons) {
				case OCdialogs.YES_NO_BUTTONS:
					buttonlist = [{
						text: t('core', 'Yes'),
						click: function(){
							if (callback !== undefined) { callback(true) };
							$(dialog_id).dialog('close');
						}
					},
					{
						text: t('core', 'No'),
						click: function(){
							if (callback !== undefined) { callback(false) };
							$(dialog_id).dialog('close');
						}
					}];
				break;
				case OCdialogs.OK_BUTTON:
					var functionToCall;
					switch(dialog_type) {
						case 'prompt':
							buttonlist[1] = {
								text: t('core', 'Cancel'),
								click: function() { $(dialog_id).dialog('close'); }
							};
							functionToCall = function() { OCdialogs.prompt_ok_handler(callback, dialog_id); };
						break;
						default:
							functionToCall = function() {
								$(dialog_id).dialog('close');
								if(callback !== undefined) { callback() };
							};
						break;
					}
					buttonlist[0] = {
						text: t('core', 'Ok'),
						click: functionToCall
					};
				break;
			};

			$(dialog_id).dialog({
				modal: modal,
				buttons: buttonlist
			});
			OCdialogs.dialogs_counter++;
		})
		.fail(function() {
			alert(t('core', 'Error loading file picker template'));
		});
	},
	// dialog button types
	YES_NO_BUTTONS:		70,
	OK_BUTTONS:		71,
	// used to name each dialog
	dialogs_counter: 0,

	determineValue: function(element) {
		if ( $(element).attr('type') === 'checkbox' ) {
			return element.checked;
		} else {
			return $(element).val();
		}
	},

	prompt_ok_handler: function(callback, dialog_id) {
		$(dialog_id).dialog('close');
		if (callback !== undefined) { callback($(dialog_id + ' input#oc-dialog-prompt-input').val()) };
	},

	form_ok_handler: function(callback, dialog_id) {
		if (callback !== undefined) {
			var valuelist = [];
			$(dialog_id + ' input, ' + dialog_id + ' select').each(function(index, element) {
				valuelist[index] = { name: $(element).attr('name'), value: OCdialogs.determineValue(element) };
			});
			$(dialog_id).dialog('close');
			callback(valuelist);
		} else {
			$(dialog_id).dialog('close');
		}
	},
	/**
	 * fills the filepicker with files
	*/
	fillFilePicker:function(dir) {
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

			self.fillTreeList();
			var sorted = dirs.concat(others);

			$.each(sorted, function(idx, entry) {
				$li = self.$listTmpl.octemplate({
					type: entry.type,
					dir: dir,
					imgsrc: entry.mimetype_icon,
					filename: entry.name,
					date: OC.mtime2date(entry.mtime)
				});
				self.$filelist.append($li);
			});

			self.$filelist.removeClass('loading');
		});
	},
	/**
	 * fills the tree list with directories
	*/
	fillTreeList: function() {
		this.$dirTree.empty();
		var self = this
		var path = this.$filePicker.data('path');
		if(!path) {
			return;
		}
		var $template = $('<span data-dir="{dir}">{name}</span>');
		var paths = path.split('/');
		paths.pop();
		$.each(paths, function(index, dir) {
			var dir = paths.pop();
			if(dir === '') {
				return false;
			}
			self.$dirTree.prepend($template.octemplate({
				dir: paths.join('/') + '/' + dir,
				name: dir
			}));
		});
		self.$dirTree.prepend($template.octemplate({
			dir: '',
			name: '/'
		}));
	},
	/**
	 * handle selection made in the tree list
	*/
	handleTreeListSelect:function(event) {
		var self = event.data;
		var dir = $(event.target).data('dir');
		self.fillFilePicker(dir);
	},
	/**
	 * go one directory up
	*/
	filepickerDirUp:function(event) {
		var self = event.data;
		var old_path = self.$filePicker.data('path');
		if (old_path !== '') {
			var splitted_path = old_path.split('/');
			splitted_path.pop();
			self.fillFilePicker(splitted_path.join('/'));
		}
	},
	/**
	 * handle clicks made in the filepicker
	*/
	handlePickerClick:function(event, $element) {
		if ($element.data('type') === 'file') {
			if (this.$filePicker.data('multiselect') !== true || !event.ctrlKey) {
				this.$filelist.find('.filepicker_element_selected').removeClass('filepicker_element_selected');
			}
			$element.toggleClass('filepicker_element_selected');
			return;
		} else if ( $element.data('type') === 'dir' ) {
			this.fillFilePicker(this.$filePicker.data('path') + '/' + $element.data('entryname'))
		}
	}
};
