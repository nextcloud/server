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

	/**
	 * Construct a new FileActions instance
	 * @constructs Files
	 */
	var Files = function() {
		this.initialize();
	};
	/**
	 * @memberof OCA.Search
	 */
	Files.prototype = {

		/**
		 * Initialize the file search
		 */
		initialize: function() {
			OC.Plugins.register('OCA.Search', this);
		},
		attach: function(search) {
			var self = this;
			search.setFilter('files', function (query) {
				if (self.fileAppLoaded()) {
					OCA.Files.App.fileList.setFilter(query);

				}
			});

			search.setRenderer('folder', this.renderFolderResult);
			search.setRenderer('file',   this.renderFileResult);
			search.setRenderer('audio',  this.renderAudioResult);
			search.setRenderer('image',  this.renderImageResult);

			search.setHandler('folder',  this.handleFolderClick);
			search.setHandler(['file', 'audio', 'image'], this.handleFileClick);
		},
		renderFolderResult: function($row, result) {
			if (this.inFileList($row, result)) {
				return null;
			}
			/*render folder icon, show path beneath filename,
			 show size and last modified date on the right */
			this.updateLegacyMimetype(result);

			var $pathDiv = $('<div class="path"></div>').text(result.path);
			$row.find('td.info div.name').after($pathDiv).text(result.name);

			$row.find('td.result a').attr('href', result.link);
			$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'filetypes/folder') + ')');
			return $row;
		},
		renderFileResult: function($row, result) {
			if (this.inFileList($row, result)) {
				return null;
			}
			/*render preview icon, show path beneath filename,
			 show size and last modified date on the right */
			this.updateLegacyMimetype(result);

			var $pathDiv = $('<div class="path"></div>').text(result.path);
			$row.find('td.info div.name').after($pathDiv).text(result.name);

			$row.find('td.result a').attr('href', result.link);

			if (this.fileAppLoaded()) {
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
			/*render preview icon, show path beneath filename,
			 show size and last modified date on the right
			 show Artist and Album */
			$row = this.renderFileResult($row, result);
			if ($row) {
				$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'filetypes/audio') + ')');
			}
			return $row;
		},
		renderImageResult: function($row, result) {
			/*render preview icon, show path beneath filename,
			 show size and last modified date on the right
			 show width and height */
			$row = this.renderFileResult($row, result);
			if ($row && !this.fileAppLoaded()) {
				$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'filetypes/image') + ')');
			}
			return $row;
		},
		inFileList: function($row, result){
			return this.fileAppLoaded() && OCA.Files.App.fileList.inList(result.name);
		},
		updateLegacyMimetype: function(result){
			// backward compatibility:
			if (!result.mime && result.mime_type) {
				result.mime = result.mime_type;
			}
		},
		handleFolderClick: function($row, result, event) {
			// open folder
			if (this.fileAppLoaded()) {
				OCA.Files.App.fileList.changeDirectory(result.path);
				return false;
			} else {
				return true;
			}
		},
		handleFileClick: function($row, result, event) {
			if (this.fileAppLoaded()) {
				OCA.Files.App.fileList.changeDirectory(OC.dirname(result.path));
				OCA.Files.App.fileList.scrollTo(result.name);
				return false;
			} else {
				return true;
			}
		},
		fileAppLoaded: function() {
			return !!OCA.Files && !!OCA.Files.App;
		}
	};
	new Files();
})();
