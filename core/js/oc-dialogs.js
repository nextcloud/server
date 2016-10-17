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

/* global alert */

/**
 * this class to ease the usage of jquery dialogs
 * @lends OC.dialogs
 */
var OCdialogs = {
	// dialog button types
	YES_NO_BUTTONS:		70,
	OK_BUTTONS:		71,
	// used to name each dialog
	dialogsCounter: 0,
	/**
	* displays alert dialog
	* @param text content of dialog
	* @param title dialog title
	* @param callback which will be triggered when user presses OK
	* @param modal make the dialog modal
	*/
	alert:function(text, title, callback, modal) {
		this.message(
			text,
			title,
			'alert',
			OCdialogs.OK_BUTTON,
			callback,
			modal
		);
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
	* @param callback which will be triggered when user presses YES or NO
	*        (true or false would be passed to callback respectively)
	* @param modal make the dialog modal
	*/
	confirm:function(text, title, callback, modal) {
		return this.message(
			text,
			title,
			'notice',
			OCdialogs.YES_NO_BUTTONS,
			callback,
			modal
		);
	},
	/**
	 * displays prompt dialog
	 * @param text content of dialog
	 * @param title dialog title
	 * @param callback which will be triggered when user presses YES or NO
	 *        (true or false would be passed to callback respectively)
	 * @param modal make the dialog modal
	 * @param name name of the input field
	 * @param password whether the input should be a password input
	 */
	prompt: function (text, title, callback, modal, name, password) {
		return $.when(this._getMessageTemplate()).then(function ($tmpl) {
			var dialogName = 'oc-dialog-' + OCdialogs.dialogsCounter + '-content';
			var dialogId = '#' + dialogName;
			var $dlg = $tmpl.octemplate({
				dialog_name: dialogName,
				title      : title,
				message    : text,
				type       : 'notice'
			});
			var input = $('<input/>');
			input.attr('type', password ? 'password' : 'text').attr('id', dialogName + '-input');
			var label = $('<label/>').attr('for', dialogName + '-input').text(name + ': ');
			$dlg.append(label);
			$dlg.append(input);
			if (modal === undefined) {
				modal = false;
			}
			$('body').append($dlg);
			var buttonlist = [{
					text : t('core', 'No'),
					click: function () {
						if (callback !== undefined) {
							callback(false, input.val());
						}
						$(dialogId).ocdialog('close');
					}
				}, {
					text         : t('core', 'Yes'),
					click        : function () {
						if (callback !== undefined) {
							callback(true, input.val());
						}
						$(dialogId).ocdialog('close');
					},
					defaultButton: true
				}
			];

			$(dialogId).ocdialog({
				closeOnEscape: true,
				modal        : modal,
				buttons      : buttonlist
			});
			OCdialogs.dialogsCounter++;
		});
	},
	/**
	 * show a file picker to pick a file from
	 * @param title dialog title
	 * @param callback which will be triggered when user presses Choose
	 * @param multiselect whether it should be possible to select multiple files
	 * @param mimetypeFilter mimetype to filter by - directories will always be included
	 * @param modal make the dialog modal
	*/
	filepicker:function(title, callback, multiselect, mimetypeFilter, modal) {
		var self = this;
		// avoid opening the picker twice
		if (this.filepicker.loading) {
			return;
		}
		this.filepicker.loading = true;
		$.when(this._getFilePickerTemplate()).then(function($tmpl) {
			self.filepicker.loading = false;
			var dialogName = 'oc-dialog-filepicker-content';
			if(self.$filePicker) {
				self.$filePicker.ocdialog('close');
			}
			self.$filePicker = $tmpl.octemplate({
				dialog_name: dialogName,
				title: title
			}).data('path', '').data('multiselect', multiselect).data('mimetype', mimetypeFilter);

			if (modal === undefined) {
				modal = false;
			}
			if (multiselect === undefined) {
				multiselect = false;
			}
			if (mimetypeFilter === undefined) {
				mimetypeFilter = '';
			}

			$('body').append(self.$filePicker);

			self.$filePicker.ready(function() {
				self.$filelist = self.$filePicker.find('.filelist');
				self.$dirTree = self.$filePicker.find('.dirtree');
				self.$dirTree.on('click', 'div:not(:last-child)', self, self._handleTreeListSelect.bind(self));
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
				// max-width of 600
				width: Math.min((4/5)*$(document).width(), 600),
				height: 420,
				modal: modal,
				buttons: buttonlist,
				close: function() {
					try {
						$(this).ocdialog('destroy').remove();
					} catch(e) {}
					self.$filePicker = null;
				}
			});
			if (!OC.Util.hasSVGSupport()) {
				OC.Util.replaceSVG(self.$filePicker.parent());
			}
		})
		.fail(function(status, error) {
			// If the method is called while navigating away
			// from the page, it is probably not needed ;)
			self.filepicker.loading = false;
			if(status !== 0) {
				alert(t('core', 'Error loading file picker template: {error}', {error: error}));
			}
		});
	},
	/**
	 * Displays raw dialog
	 * You better use a wrapper instead ...
	*/
	message:function(content, title, dialogType, buttons, callback, modal) {
		return $.when(this._getMessageTemplate()).then(function($tmpl) {
			var dialogName = 'oc-dialog-' + OCdialogs.dialogsCounter + '-content';
			var dialogId = '#' + dialogName;
			var $dlg = $tmpl.octemplate({
				dialog_name: dialogName,
				title: title,
				message: content,
				type: dialogType
			});
			if (modal === undefined) {
				modal = false;
			}
			$('body').append($dlg);
			var buttonlist = [];
			switch (buttons) {
			case OCdialogs.YES_NO_BUTTONS:
				buttonlist = [{
					text: t('core', 'No'),
					click: function(){
						if (callback !== undefined) {
							callback(false);
						}
						$(dialogId).ocdialog('close');
					}
				},
				{
					text: t('core', 'Yes'),
					click: function(){
						if (callback !== undefined) {
							callback(true);
						}
						$(dialogId).ocdialog('close');
					},
					defaultButton: true
				}];
				break;
			case OCdialogs.OK_BUTTON:
				var functionToCall = function() {
					$(dialogId).ocdialog('close');
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

			$(dialogId).ocdialog({
				closeOnEscape: true,
				modal: modal,
				buttons: buttonlist
			});
			OCdialogs.dialogsCounter++;
		})
		.fail(function(status, error) {
			// If the method is called while navigating away from
			// the page, we still want to deliver the message.
			if(status === 0) {
				alert(title + ': ' + content);
			} else {
				alert(t('core', 'Error loading message template: {error}', {error: error}));
			}
		});
	},
	_fileexistsshown: false,
	/**
	 * Displays file exists dialog
	 * @param {object} data upload object
	 * @param {object} original file with name, size and mtime
	 * @param {object} replacement file with name, size and mtime
	 * @param {object} controller with onCancel, onSkip, onReplace and onRename methods
	 * @return {Promise} jquery promise that resolves after the dialog template was loaded
	*/
	fileexists:function(data, original, replacement, controller) {
		var self = this;
		var dialogDeferred = new $.Deferred();

		var getCroppedPreview = function(file) {
			var deferred = new $.Deferred();
			// Only process image files.
			var type = file.type && file.type.split('/').shift();
			if (window.FileReader && type === 'image') {
				var reader = new FileReader();
				reader.onload = function (e) {
					var blob = new Blob([e.target.result]);
					window.URL = window.URL || window.webkitURL;
					var originalUrl = window.URL.createObjectURL(blob);
					var image = new Image();
					image.src = originalUrl;
					image.onload = function () {
						var url = crop(image);
						deferred.resolve(url);
					};
				};
				reader.readAsArrayBuffer(file);
			} else {
				deferred.reject();
			}
			return deferred;
		};

		var crop = function(img) {
			var canvas = document.createElement('canvas'),
					targetSize = 96,
					width = img.width,
					height = img.height,
					x, y, size;

			// Calculate the width and height, constraining the proportions
			if (width > height) {
				y = 0;
				x = (width - height) / 2;
			} else {
				y = (height - width) / 2;
				x = 0;
			}
			size = Math.min(width, height);

			// Set canvas size to the cropped area
			canvas.width = size;
			canvas.height = size;
			var ctx = canvas.getContext("2d");
			ctx.drawImage(img, x, y, size, size, 0, 0, size, size);

			// Resize the canvas to match the destination (right size uses 96px)
			resampleHermite(canvas, size, size, targetSize, targetSize);

			return canvas.toDataURL("image/png", 0.7);
		};

		/**
		 * Fast image resize/resample using Hermite filter with JavaScript.
		 *
		 * @author: ViliusL
		 *
		 * @param {*} canvas
		 * @param {number} W
		 * @param {number} H
		 * @param {number} W2
		 * @param {number} H2
		 */
		var resampleHermite = function (canvas, W, H, W2, H2) {
			W2 = Math.round(W2);
			H2 = Math.round(H2);
			var img = canvas.getContext("2d").getImageData(0, 0, W, H);
			var img2 = canvas.getContext("2d").getImageData(0, 0, W2, H2);
			var data = img.data;
			var data2 = img2.data;
			var ratio_w = W / W2;
			var ratio_h = H / H2;
			var ratio_w_half = Math.ceil(ratio_w / 2);
			var ratio_h_half = Math.ceil(ratio_h / 2);

			for (var j = 0; j < H2; j++) {
				for (var i = 0; i < W2; i++) {
					var x2 = (i + j * W2) * 4;
					var weight = 0;
					var weights = 0;
					var weights_alpha = 0;
					var gx_r = 0;
					var gx_g = 0;
					var gx_b = 0;
					var gx_a = 0;
					var center_y = (j + 0.5) * ratio_h;
					for (var yy = Math.floor(j * ratio_h); yy < (j + 1) * ratio_h; yy++) {
						var dy = Math.abs(center_y - (yy + 0.5)) / ratio_h_half;
						var center_x = (i + 0.5) * ratio_w;
						var w0 = dy * dy; //pre-calc part of w
						for (var xx = Math.floor(i * ratio_w); xx < (i + 1) * ratio_w; xx++) {
							var dx = Math.abs(center_x - (xx + 0.5)) / ratio_w_half;
							var w = Math.sqrt(w0 + dx * dx);
							if (w >= -1 && w <= 1) {
								//hermite filter
								weight = 2 * w * w * w - 3 * w * w + 1;
								if (weight > 0) {
									dx = 4 * (xx + yy * W);
									//alpha
									gx_a += weight * data[dx + 3];
									weights_alpha += weight;
									//colors
									if (data[dx + 3] < 255)
										weight = weight * data[dx + 3] / 250;
									gx_r += weight * data[dx];
									gx_g += weight * data[dx + 1];
									gx_b += weight * data[dx + 2];
									weights += weight;
								}
							}
						}
					}
					data2[x2] = gx_r / weights;
					data2[x2 + 1] = gx_g / weights;
					data2[x2 + 2] = gx_b / weights;
					data2[x2 + 3] = gx_a / weights_alpha;
				}
			}
			canvas.getContext("2d").clearRect(0, 0, Math.max(W, W2), Math.max(H, H2));
			canvas.width = W2;
			canvas.height = H2;
			canvas.getContext("2d").putImageData(img2, 0, 0);
		};

		var addConflict = function($conflicts, original, replacement) {

			var $conflict = $conflicts.find('.template').clone().removeClass('template').addClass('conflict');
			var $originalDiv = $conflict.find('.original');
			var $replacementDiv = $conflict.find('.replacement');

			$conflict.data('data',data);

			$conflict.find('.filename').text(original.name);
			$originalDiv.find('.size').text(humanFileSize(original.size));
			$originalDiv.find('.mtime').text(formatDate(original.mtime));
			// ie sucks
			if (replacement.size && replacement.lastModifiedDate) {
				$replacementDiv.find('.size').text(humanFileSize(replacement.size));
				$replacementDiv.find('.mtime').text(formatDate(replacement.lastModifiedDate));
			}
			var path = original.directory + '/' +original.name;
			var urlSpec = {
				file:		path,
				x:		96,
				y:		96,
				c:		original.etag,
				forceIcon:	0
			};
			var previewpath = Files.generatePreviewUrl(urlSpec);
			// Escaping single quotes
			previewpath = previewpath.replace(/'/g, "%27");
			$originalDiv.find('.icon').css({"background-image":   "url('" + previewpath + "')"});
			getCroppedPreview(replacement).then(
				function(path){
					$replacementDiv.find('.icon').css('background-image','url(' + path + ')');
				}, function(){
					path = OC.MimeType.getIconUrl(replacement.type);
					$replacementDiv.find('.icon').css('background-image','url(' + path + ')');
				}
			);
			// connect checkboxes with labels
			var checkboxId = $conflicts.find('.conflict').length;
			$originalDiv.find('input:checkbox').attr('id', 'checkbox_original_'+checkboxId);
			$replacementDiv.find('input:checkbox').attr('id', 'checkbox_replacement_'+checkboxId);

			$conflicts.append($conflict);

			//set more recent mtime bold
			// ie sucks
			if (replacement.lastModifiedDate && replacement.lastModifiedDate.getTime() > original.mtime) {
				$replacementDiv.find('.mtime').css('font-weight', 'bold');
			} else if (replacement.lastModifiedDate && replacement.lastModifiedDate.getTime() < original.mtime) {
				$originalDiv.find('.mtime').css('font-weight', 'bold');
			} else {
				//TODO add to same mtime collection?
			}

			// set bigger size bold
			if (replacement.size && replacement.size > original.size) {
				$replacementDiv.find('.size').css('font-weight', 'bold');
			} else if (replacement.size && replacement.size < original.size) {
				$originalDiv.find('.size').css('font-weight', 'bold');
			} else {
				//TODO add to same size collection?
			}

			//TODO show skip action for files with same size and mtime in bottom row

			// always keep readonly files

			if (original.status === 'readonly') {
				$originalDiv
					.addClass('readonly')
					.find('input[type="checkbox"]')
						.prop('checked', true)
						.prop('disabled', true);
				$originalDiv.find('.message')
					.text(t('core','read-only'))
			}
		};
		//var selection = controller.getSelection(data.originalFiles);
		//if (selection.defaultAction) {
		//	controller[selection.defaultAction](data);
		//} else {
		var dialogName = 'oc-dialog-fileexists-content';
		var dialogId = '#' + dialogName;
		if (this._fileexistsshown) {
			// add conflict

			var $conflicts = $(dialogId+ ' .conflicts');
			addConflict($conflicts, original, replacement);

			var count = $(dialogId+ ' .conflict').length;
			var title = n('core',
							'{count} file conflict',
							'{count} file conflicts',
							count,
							{count:count}
						);
			$(dialogId).parent().children('.oc-dialog-title').text(title);

			//recalculate dimensions
			$(window).trigger('resize');
			dialogDeferred.resolve();
		} else {
			//create dialog
			this._fileexistsshown = true;
			$.when(this._getFileExistsTemplate()).then(function($tmpl) {
				var title = t('core','One file conflict');
				var $dlg = $tmpl.octemplate({
					dialog_name: dialogName,
					title: title,
					type: 'fileexists',

					allnewfiles: t('core','New Files'),
					allexistingfiles: t('core','Already existing files'),

					why: t('core','Which files do you want to keep?'),
					what: t('core','If you select both versions, the copied file will have a number added to its name.')
				});
				$('body').append($dlg);

				if (original && replacement) {
					var $conflicts = $dlg.find('.conflicts');
					addConflict($conflicts, original, replacement);
				}

				var buttonlist = [{
						text: t('core', 'Cancel'),
						classes: 'cancel',
						click: function(){
							if ( typeof controller.onCancel !== 'undefined') {
								controller.onCancel(data);
							}
							$(dialogId).ocdialog('close');
						}
					},
					{
						text: t('core', 'Continue'),
						classes: 'continue',
						click: function(){
							if ( typeof controller.onContinue !== 'undefined') {
								controller.onContinue($(dialogId + ' .conflict'));
							}
							$(dialogId).ocdialog('close');
						}
					}];

				$(dialogId).ocdialog({
					width: 500,
					closeOnEscape: true,
					modal: true,
					buttons: buttonlist,
					closeButton: null,
					close: function() {
							self._fileexistsshown = false;
							$(this).ocdialog('destroy').remove();
						}
				});

				$(dialogId).css('height','auto');

				var $primaryButton = $dlg.closest('.oc-dialog').find('button.continue');
				$primaryButton.prop('disabled', true);

				function updatePrimaryButton() {
					var checkedCount = $dlg.find('.conflicts .checkbox:checked').length;
					$primaryButton.prop('disabled', checkedCount === 0);
				}

				//add checkbox toggling actions
				$(dialogId).find('.allnewfiles').on('click', function() {
					var $checkboxes = $(dialogId).find('.conflict .replacement input[type="checkbox"]');
					$checkboxes.prop('checked', $(this).prop('checked'));
				});
				$(dialogId).find('.allexistingfiles').on('click', function() {
					var $checkboxes = $(dialogId).find('.conflict .original:not(.readonly) input[type="checkbox"]');
					$checkboxes.prop('checked', $(this).prop('checked'));
				});
				$(dialogId).find('.conflicts').on('click', '.replacement,.original:not(.readonly)', function() {
					var $checkbox = $(this).find('input[type="checkbox"]');
					$checkbox.prop('checked', !$checkbox.prop('checked'));
				});
				$(dialogId).find('.conflicts').on('click', '.replacement input[type="checkbox"],.original:not(.readonly) input[type="checkbox"]', function() {
					var $checkbox = $(this);
					$checkbox.prop('checked', !$checkbox.prop('checked'));
				});

				//update counters
				$(dialogId).on('click', '.replacement,.allnewfiles', function() {
					var count = $(dialogId).find('.conflict .replacement input[type="checkbox"]:checked').length;
					if (count === $(dialogId+ ' .conflict').length) {
						$(dialogId).find('.allnewfiles').prop('checked', true);
						$(dialogId).find('.allnewfiles + .count').text(t('core','(all selected)'));
					} else if (count > 0) {
						$(dialogId).find('.allnewfiles').prop('checked', false);
						$(dialogId).find('.allnewfiles + .count').text(t('core','({count} selected)',{count:count}));
					} else {
						$(dialogId).find('.allnewfiles').prop('checked', false);
						$(dialogId).find('.allnewfiles + .count').text('');
					}
					updatePrimaryButton();
				});
				$(dialogId).on('click', '.original,.allexistingfiles', function(){
					var count = $(dialogId).find('.conflict .original input[type="checkbox"]:checked').length;
					if (count === $(dialogId+ ' .conflict').length) {
						$(dialogId).find('.allexistingfiles').prop('checked', true);
						$(dialogId).find('.allexistingfiles + .count').text(t('core','(all selected)'));
					} else if (count > 0) {
						$(dialogId).find('.allexistingfiles').prop('checked', false);
						$(dialogId).find('.allexistingfiles + .count')
							.text(t('core','({count} selected)',{count:count}));
					} else {
						$(dialogId).find('.allexistingfiles').prop('checked', false);
						$(dialogId).find('.allexistingfiles + .count').text('');
					}
					updatePrimaryButton();
				});

				dialogDeferred.resolve();
			})
			.fail(function() {
				dialogDeferred.reject();
				alert(t('core', 'Error loading file exists template'));
			});
		}
		//}
		return dialogDeferred.promise();
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
			.fail(function(jqXHR, textStatus, errorThrown) {
				defer.reject(jqXHR.status, errorThrown);
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
			.fail(function(jqXHR, textStatus, errorThrown) {
				defer.reject(jqXHR.status, errorThrown);
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
		if (typeof(mimeType) === "string") {
			mimeType = [mimeType];
		}

		return $.getJSON(
			OC.filePath('files', 'ajax', 'list.php'),
			{
				dir: dir,
				mimetypes: JSON.stringify(mimeType)
			}
		);
	},

	/**
	 * fills the filepicker with files
	*/
	_fillFilePicker:function(dir) {
		var dirs = [];
		var others = [];
		var self = this;
		this.$filelist.empty().addClass('icon-loading');
		this.$filePicker.data('path', dir);
		$.when(this._getFileList(dir, this.$filePicker.data('mimetype'))).then(function(response) {

			$.each(response.data.files, function(index, file) {
				if (file.type === 'dir') {
					dirs.push(file);
				} else {
					others.push(file);
				}
			});

			self._fillSlug();
			var sorted = dirs.concat(others);

			$.each(sorted, function(idx, entry) {
				entry.icon = OC.MimeType.getIconUrl(entry.mimetype);
				var $li = self.$listTmpl.octemplate({
					type: entry.type,
					dir: dir,
					filename: entry.name,
					date: OC.Util.relativeModifiedDate(entry.mtime)
				});
				if (entry.type === 'file') {
					var urlSpec = {
						file: dir + '/' + entry.name,
					};
					$li.find('img').attr('src', OC.MimeType.getIconUrl(entry.mimetype));
					var img = new Image();
					var previewUrl = OC.generateUrl('/core/preview.png?') + $.param(urlSpec);
					img.onload = function() {
						if (img.width > 5) {
							$li.find('img').attr('src', previewUrl);
						}
					};
					img.src = previewUrl;
				}
				else {
					$li.find('img').attr('src', OC.Util.replaceSVGIcon(entry.icon));
				}
				self.$filelist.append($li);
			});

			self.$filelist.removeClass('icon-loading');
			if (!OC.Util.hasSVGSupport()) {
				OC.Util.replaceSVG(self.$filePicker.find('.dirtree'));
			}
		});
	},
	/**
	 * fills the tree list with directories
	*/
	_fillSlug: function() {
		this.$dirTree.empty();
		var self = this;
		var dir;
		var path = this.$filePicker.data('path');
		var $template = $('<div data-dir="{dir}"><a>{name}</a></div>').addClass('crumb');
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
			name: '' // Ugly but works ;)
		}, {escapeFunction: null}).prependTo(this.$dirTree);
	},
	/**
	 * handle selection made in the tree list
	*/
	_handleTreeListSelect:function(event) {
		var self = event.data;
		var dir = $(event.target).parent().data('dir');
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
		} else if ( $element.data('type') === 'dir' ) {
			this._fillFilePicker(this.$filePicker.data('path') + '/' + $element.data('entryname'));
		}
	}
};
