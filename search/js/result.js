/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

//FIXME move to files?
$(document).ready(function() {
	// wait for other apps/extensions to register their event handlers and file actions
	// in the "ready" clause
	_.defer(function() {
		OC.Search.setFormatter('file', function ($row, result) {
			// backward compatibility:
			if (typeof result.mime !== 'undefined') {
				result.mime_type = result.mime;
			} else if (typeof result.mime_type !== 'undefined') {
				result.mime = result.mime_type;
			}

			$pathDiv = $('<div class="path"></div>').text(result.path);
			$row.find('td.info div.name').after($pathDiv).text(result.name);

			$row.find('td.result a').attr('href', result.link);

			if (OCA.Files) {
				OCA.Files.App.fileList.lazyLoadPreview({
					path: result.path,
					mime: result.mime,
					callback: function (url) {
						$row.find('td.icon').css('background-image', 'url(' + url + ')');
					}
				});
			} else {
				// FIXME how to get mime icon if not in files app
				var mimeicon = result.mime.replace('/', '-');
				$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'filetypes/' + mimeicon) + ')');
				var dir = OC.dirname(result.path);
				if (dir === '') {
					dir = '/';
				}
				$row.find('td.info a').attr('href',
					OC.generateUrl('/apps/files/?dir={dir}&scrollto={scrollto}', {dir: dir, scrollto: result.name})
				);
			}
		});
		OC.Search.setHandler('file', function ($row, result, event) {
			if (OCA.Files) {
				OCA.Files.App.fileList.changeDirectory(OC.dirname(result.path));
				OCA.Files.App.fileList.scrollTo(result.name);
				return false;
			} else {
				return true;
			}
		});

		OC.Search.setFormatter('folder', function ($row, result) {
			// backward compatibility:
			if (typeof result.mime !== 'undefined') {
				result.mime_type = result.mime;
			} else if (typeof result.mime_type !== 'undefined') {
				result.mime = result.mime_type;
			}

			var $pathDiv = $('<div class="path"></div>').text(result.path)
			$row.find('td.info div.name').after($pathDiv).text(result.name);

			$row.find('td.result a').attr('href', result.link);
			$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'filetypes/folder') + ')');
		});
		OC.Search.setHandler('folder', function ($row, result, event) {
			if (OCA.Files) {
				OCA.Files.App.fileList.changeDirectory(result.path);
				return false;
			} else {
				return true;
			}
		});
	});
});
