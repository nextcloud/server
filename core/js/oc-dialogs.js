/**
 * ownCloud
 *
 * @author Bartek Przybylski
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
		var content = '<p><span class="ui-icon ui-icon-alert"></span>' + escapeHTML(text) + '</p>';
		OCdialogs.message(content, title, OCdialogs.ALERT_DIALOG, OCdialogs.OK_BUTTON, callback, modal);
	},
	/**
	* displays info dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses OK
	* @param modal make the dialog modal
	*/
	info:function(text, title, callback, modal) {
		var content = '<p><span class="ui-icon ui-icon-info"></span>' + escapeHTML(text) + '</p>';
		OCdialogs.message(content, title, OCdialogs.ALERT_DIALOG, OCdialogs.OK_BUTTON, callback, modal);
	},
	/**
	* displays confirmation dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses YES or NO (true or false would be passed to callback respectively)
	* @param modal make the dialog modal
	*/
	confirm:function(text, title, callback, modal) {
		var content = '<p><span class="ui-icon ui-icon-notice"></span>' + escapeHTML(text) + '</p>';
		OCdialogs.message(content, title, OCdialogs.ALERT_DIALOG, OCdialogs.YES_NO_BUTTONS, callback, modal);
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
		OCdialogs.message(content, title, OCdialogs.PROMPT_DIALOG, OCdialogs.OK_BUTTON, callback, modal);
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
	/**
	 * show a file picker to pick a file from
	 * @param title dialog title
	 * @param callback which will be triggered when user presses Choose
	 * @param multiselect whether it should be possible to select multiple files
	 * @param mimetype_filter mimetype to filter by
	 * @param modal make the dialog modal
	*/
	filepicker:function(title, callback, multiselect, mimetype_filter, modal) {
		var dialog_name = 'oc-dialog-' + OCdialogs.dialogs_counter + '-content';
		var dialog_id = '#' + dialog_name;
		var dialog_content = '<button id="dirup">↑</button><select id="dirtree"></select><div id="filelist"></div>';
		var dialog_loader = '<div class="filepicker_loader"><img src="' + OC.filePath('gallery','img','loading.gif') + '"></div>';
		var dialog_div = '<div id="' + dialog_name + '" title="' + escapeHTML(title) + '">' + dialog_content + dialog_loader + '</div>';
		if (modal === undefined) { modal = false };
		if (multiselect === undefined) { multiselect = false };
		if (mimetype_filter === undefined) { mimetype_filter = '' };

		$('body').append(dialog_div);

		$(dialog_id).data('path', '/');

		$(dialog_id + ' #dirtree').focus().change( {dcid: dialog_id}, OCdialogs.handleTreeListSelect );
		$(dialog_id + ' #dirup').click( {dcid: dialog_id}, OCdialogs.filepickerDirUp );

		$(dialog_id).ready(function(){
			$.getJSON(OC.filePath('files', 'ajax', 'rawlist.php'), { mimetype: mimetype_filter } ,function(request) {
				OCdialogs.fillFilePicker(request, dialog_id);
			});
			$.getJSON(OC.filePath('files', 'ajax', 'rawlist.php'), { mimetype: "httpd/unix-directory" }, function(request) {
				OCdialogs.fillTreeList(request, dialog_id);
			});
		}).data('multiselect', multiselect).data('mimetype',mimetype_filter);

		// build buttons
		var functionToCall = function() {
			if (callback !== undefined) {
				var datapath;
				if (multiselect === true) {
					datapath = [];
					$(dialog_id + ' .filepicker_element_selected .filename').each(function(index, element) {
						datapath.push( $(dialog_id).data('path') + $(element).text() );
					});
				} else {
					var datapath = $(dialog_id).data('path');
					datapath += $(dialog_id+' .filepicker_element_selected .filename').text();
				}
				callback(datapath);
				$(dialog_id).dialog('close');
			}
		};
		var buttonlist = [{
			text: t('core', 'Choose'), 
			click: functionToCall
			},
			{
			text: t('core', 'Cancel'), 
			click: function(){$(dialog_id).dialog('close'); }
		}];

		$(dialog_id).dialog({
			width: (4/9)*$(document).width(),
			height: 420,
			modal: modal,
			buttons: buttonlist
		});
		OCdialogs.dialogs_counter++;
	},
	/**
	 * Displays raw dialog
	 * You better use a wrapper instead ...
	*/
	message:function(content, title, dialog_type, buttons, callback, modal) {
		var dialog_name = 'oc-dialog-' + OCdialogs.dialogs_counter + '-content';
		var dialog_id = '#' + dialog_name;
		var dialog_div = '<div id="' + dialog_name + '" title="' + escapeHTML(title) + '">' + content + '</div>';
		if (modal === undefined) { modal = false };
		$('body').append(dialog_div);
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
					case OCdialogs.ALERT_DIALOG:
						functionToCall = function() {
							$(dialog_id).dialog('close');
							if(callback !== undefined) { callback() };
						};
					break;
					case OCdialogs.PROMPT_DIALOG:
						buttonlist[1] = {
							text: t('core', 'Cancel'),
							click: function() { $(dialog_id).dialog('close'); }
						};
						functionToCall = function() { OCdialogs.prompt_ok_handler(callback, dialog_id); };
					break;
				}
				buttonlist[0] = {
					text: t('core', 'Ok'),
					click: functionToCall
				};
			break;
		};

		$(dialog_id).dialog({
			width: (4/9) * $(document).width(),
			height: 180,
			modal: modal,
			buttons: buttonlist
		});
		OCdialogs.dialogs_counter++;
	},
	// dialog button types
	YES_NO_BUTTONS:		70,
	OK_BUTTONS:		71,
	// dialogs types
	ALERT_DIALOG:	80,
	INFO_DIALOG:	81,
	FORM_DIALOG:	82,
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
		if (callback !== undefined) { callback($(dialog_id + " input#oc-dialog-prompt-input").val()) };
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
	fillFilePicker:function(request, dialog_content_id) {
		var template_content = '<img src="*MIMETYPEICON*" style="margin: 2px 1em 0 4px;"><span class="filename">*NAME*</span><div style="float:right;margin-right:1em;">*LASTMODDATE*</div>';
		var template = '<div data-entryname="*ENTRYNAME*" data-dcid="' + escapeHTML(dialog_content_id) + '" data="*ENTRYTYPE*">*CONTENT*</div>';
		var files = '';
		var dirs = [];
		var others = [];
		$.each(request.data, function(index, file) {
			if (file.type === 'dir') {
				dirs.push(file);
			} else {
				others.push(file);
			}
		});
		var sorted = dirs.concat(others);
		for (var i = 0; i < sorted.length; i++) {
			files_content = template_content.replace('*LASTMODDATE*', OC.mtime2date(sorted[i].mtime)).replace('*NAME*', escapeHTML(sorted[i].name)).replace('*MIMETYPEICON*', sorted[i].mimetype_icon);
			files += template.replace('*ENTRYNAME*', escapeHTML(sorted[i].name)).replace('*ENTRYTYPE*', escapeHTML(sorted[i].type)).replace('*CONTENT*', files_content);
		}

		$(dialog_content_id + ' #filelist').html(files);
		$('#filelist div').click(function() {
			OCdialogs.handlePickerClick($(this), $(this).data('entryname'), dialog_content_id);
		});

		$(dialog_content_id + ' .filepicker_loader').css('visibility', 'hidden');
	},
	/**
	 * fills the tree list with directories
	*/
	fillTreeList: function(request, dialog_id) {
		var template = '<option value="*COUNT*">*NAME*</option>';
		var paths = '<option value="0">' + escapeHTML($(dialog_id).data('path')) + '</option>';
		$.each(request.data, function(index, file) {
			paths += template.replace('*COUNT*', index).replace('*NAME*', escapeHTML(file.name));
		});

		$(dialog_id + ' #dirtree').html(paths);
	},
	/**
	 * handle selection made in the tree list
	*/
	handleTreeListSelect:function(event) {
		if ($("option:selected", this).html().indexOf('/') !== -1) { // if there's a slash in the selected path, don't append it
			$(event.data.dcid).data('path', $("option:selected", this).html());
		} else {
			$(event.data.dcid).data('path', $(event.data.dcid).data('path') + $("option:selected", this).html() + '/');
		}
		$(event.data.dcid + ' .filepicker_loader').css('visibility', 'visible');
		$.getJSON(
			OC.filePath('files', 'ajax', 'rawlist.php'),
			{
				dir: $(event.data.dcid).data('path'),
				mimetype: $(event.data.dcid).data('mimetype')
			},
			function(request) { OCdialogs.fillFilePicker(request, event.data.dcid) }
		);
		$.getJSON(
			OC.filePath('files', 'ajax', 'rawlist.php'),
			{
				dir: $(event.data.dcid).data('path'),
				mimetype: "httpd/unix-directory"
			},
			function(request) { OCdialogs.fillTreeList(request, event.data.dcid) }
		);
	},
	/**
	 * go one directory up
	*/
	filepickerDirUp:function(event) {
		var old_path = $(event.data.dcid).data('path');
		if ( old_path !== "/") {
			var splitted_path = old_path.split("/");
			var new_path = ""
			for (var i = 0; i < splitted_path.length - 2; i++) {
				new_path += splitted_path[i] + "/"
			}
			$(event.data.dcid).data('path', new_path);
			$.getJSON(
				OC.filePath('files', 'ajax', 'rawlist.php'),
				{
					dir: $(event.data.dcid).data('path'),
					mimetype: $(event.data.dcid).data('mimetype')
				},
				function(request) { OCdialogs.fillFilePicker(request, event.data.dcid) }
			);
			$.getJSON(
				OC.filePath('files', 'ajax', 'rawlist.php'),
				{
					dir: $(event.data.dcid).data('path'),
					mimetype: "httpd/unix-directory"
				},
				function(request) { OCdialogs.fillTreeList(request, event.data.dcid) }
			);
		}
	},
	/**
	 * handle clicks made in the filepicker
	*/
	handlePickerClick:function(element, name, dialog_content_id) {
		if ( $(element).attr('data') === 'file' ){
			if ( $(dialog_content_id).data('multiselect') !== true) {
				$(dialog_content_id + ' .filepicker_element_selected').removeClass('filepicker_element_selected');
			}
			$(element).toggleClass('filepicker_element_selected');
			return;
		} else if ( $(element).attr('data') === 'dir' ) {
			var datapath = escapeHTML( $(dialog_content_id).data('path') + name + '/' );
			$(dialog_content_id).data('path', datapath);
			$(dialog_content_id + ' .filepicker_loader').css('visibility', 'visible');
			$.getJSON(
				OC.filePath('files', 'ajax', 'rawlist.php'),
				{
					dir: datapath,
					mimetype: $(dialog_content_id).data('mimetype')
				},
				function(request){ OCdialogs.fillFilePicker(request, dialog_content_id) }
			);
			$.getJSON(
				OC.filePath('files', 'ajax', 'rawlist.php'),
				{
					dir: datapath,
					mimetype: "httpd/unix-directory"
				},
				function(request) { OCdialogs.fillTreeList(request, dialog_content_id) }
			);
		}
	}
};
