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
	if (!OCA.Files) {
		OCA.Files = {};
	}
	OCA.Files.Search = {
		attach: function(search) {
			search.setFilter('files', function (query) {
				if (OCA.Files.Search.fileAppLoaded()) {
					OCA.Files.App.fileList.setFilter(query);

				}
			});

			search.setRenderer('folder', OCA.Files.Search.renderFolderResult);
			search.setRenderer('file',   OCA.Files.Search.renderFileResult);
			search.setRenderer('audio',  OCA.Files.Search.renderAudioResult);
			search.setRenderer('image',  OCA.Files.Search.renderImageResult);

			search.setHandler('folder',  OCA.Files.Search.handleFolderClick);
			search.setHandler(['file', 'audio', 'image'], OCA.Files.Search.handleFileClick);
		},
		renderFolderResult: function($row, result) {
			if (OCA.Files.Search.inFileList($row, result)) {
				return null;
			}
			/*render folder icon, show path beneath filename,
			 show size and last modified date on the right */
			// backward compatibility:
			if (typeof result.mime !== 'undefined') {
				result.mime_type = result.mime;
			} else if (typeof result.mime_type !== 'undefined') {
				result.mime = result.mime_type;
			}

			var $pathDiv = $('<div class="path"></div>').text(result.path);
			$row.find('td.info div.name').after($pathDiv).text(result.name);

			$row.find('td.result a').attr('href', result.link);
			$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'filetypes/folder') + ')');
			return $row;
		},
		renderFileResult: function($row, result) {
			if (OCA.Files.Search.inFileList($row, result)) {
				return null;
			}
			/*render preview icon, show path beneath filename,
			 show size and last modified date on the right */
			// backward compatibility:
			if (typeof result.mime !== 'undefined') {
				result.mime_type = result.mime;
			} else if (typeof result.mime_type !== 'undefined') {
				result.mime = result.mime_type;
			}

			$pathDiv = $('<div class="path"></div>').text(result.path);
			$row.find('td.info div.name').after($pathDiv).text(result.name);

			$row.find('td.result a').attr('href', result.link);

			if (OCA.Files.Search.fileAppLoaded()) {
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
			return $row;
		},
		renderAudioResult: function($row, result) {
			if (OCA.Files.Search.inFileList($row, result)) {
				return null;
			}
			/*render preview icon, show path beneath filename,
			 show size and last modified date on the right
			 show Artist and Album */
			return $row;
		},
		renderImageResult: function($row, result) {
			if (OCA.Files.Search.inFileList($row, result)) {
				return null;
			}
			/*render preview icon, show path beneath filename,
			 show size and last modified date on the right
			 show width and height */
			return $row;
		},
		inFileList: function($row, result){
			if (OCA.Files.Search.fileAppLoaded() && OCA.Files.App.fileList.inList(result.name)) {
				return true;
			} else {
				return false;
			}
		},
		handleFolderClick: function($row, result, event) {
			// open folder
			if (OCA.Files.Search.fileAppLoaded()) {
				OCA.Files.App.fileList.changeDirectory(result.path);
				return false;
			} else {
				return true;
			}
		},
		handleFileClick: function($row, result, event) {
			if (OCA.Files.Search.fileAppLoaded()) {
				OCA.Files.App.fileList.changeDirectory(OC.dirname(result.path));
				OCA.Files.App.fileList.scrollTo(result.name);
				return false;
			} else {
				return true;
			}
		},
		fileAppLoaded: function() {
			return OCA.Files && OCA.Files.App;
		}
	};
})();
OC.Plugins.register('OCA.Search', OCA.Files.Search);
