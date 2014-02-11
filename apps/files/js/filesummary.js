/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
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

/* global OC, n, t */

(function() {
	/**
	 * The FileSummary class encapsulates the file summary values and
	 * the logic to render it in the given container
	 * @param $tr table row element
	 * $param summary optional initial summary value
	 */
	var FileSummary = function($tr, summary) {
		this.$el = $tr;
		this.render();
	};

	FileSummary.prototype = {
		summary: {
			totalFiles: 0,
			totalDirs: 0,
			totalSize: 0
		},

		/**
		 * Adds file
		 * @param file file to add
		 * @param update whether to update the display
		 */
		add: function(file, update) {
			if (file.type === 'dir' || file.mime === 'httpd/unix-directory') {
				this.summary.totalDirs++;
			}
			else {
				this.summary.totalFiles++;
			}
			this.summary.totalSize += parseInt(file.size, 10) || 0;
			if (!!update) {
				this.update();
			}
		},
		/**
		 * Removes file
		 * @param file file to remove
		 * @param update whether to update the display
		 */
		remove: function(file, update) {
			if (file.type === 'dir' || file.mime === 'httpd/unix-directory') {
				this.summary.totalDirs--;
			}
			else {
				this.summary.totalFiles--;
			}
			this.summary.totalSize -= parseInt(file.size, 10) || 0;
			if (!!update) {
				this.update();
			}
		},
		/**
		 * Recalculates the summary based on the given files array
		 * @param files array of files
		 */
		calculate: function(files) {
			var file;
			var summary = {
				totalDirs: 0,
				totalFiles: 0,
				totalSize: 0
			};

			for (var i = 0; i < files.length; i++) {
				file = files[i];
				if (file.type === 'dir' || file.mime === 'httpd/unix-directory') {
					summary.totalDirs++;
				}
				else {
					summary.totalFiles++;
				}
				summary.totalSize += parseInt(file.size, 10) || 0;
			}
			this.setSummary(summary);
		},
		/**
		 * Sets the current summary values
		 * @param summary map
		 */
		setSummary: function(summary) {
			this.summary = summary;
			this.update();
		},

		/**
		 * Renders the file summary element
		 */
		update: function() {
			if (!this.summary.totalFiles && !this.summary.totalDirs) {
				this.$el.addClass('hidden');
				return;
			}
			// There's a summary and data -> Update the summary
			this.$el.removeClass('hidden');
			var $dirInfo = this.$el.find('.dirinfo');
			var $fileInfo = this.$el.find('.fileinfo');
			var $connector = this.$el.find('.connector');

			// Substitute old content with new translations
			$dirInfo.html(n('files', '%n folder', '%n folders', this.summary.totalDirs));
			$fileInfo.html(n('files', '%n file', '%n files', this.summary.totalFiles));
			this.$el.find('.filesize').html(OC.Util.humanFileSize(this.summary.totalSize));

			// Show only what's necessary (may be hidden)
			if (this.summary.totalDirs === 0) {
				$dirInfo.addClass('hidden');
				$connector.addClass('hidden');
			} else {
				$dirInfo.removeClass('hidden');
			}
			if (this.summary.totalFiles === 0) {
				$fileInfo.addClass('hidden');
				$connector.addClass('hidden');
			} else {
				$fileInfo.removeClass('hidden');
			}
			if (this.summary.totalDirs > 0 && this.summary.totalFiles > 0) {
				$connector.removeClass('hidden');
			}
		},
		render: function() {
			var summary = this.summary;
			var directoryInfo = n('files', '%n folder', '%n folders', summary.totalDirs);
			var fileInfo = n('files', '%n file', '%n files', summary.totalFiles);
			var fileSize;

			var infoVars = {
				dirs: '<span class="dirinfo">'+directoryInfo+'</span><span class="connector">',
				files: '</span><span class="fileinfo">'+fileInfo+'</span>'
			};

			// don't show the filesize column, if filesize is NaN (e.g. in trashbin)
			var fileSize = '';
			if (!isNaN(summary.totalSize)) {
				fileSize = '<td class="filesize">' + OC.Util.humanFileSize(summary.totalSize) + '</td>';
			}

			var info = t('files', '{dirs} and {files}', infoVars);

			var $summary = $('<td><span class="info">'+info+'</span></td>'+fileSize+'<td></td>');

			if (!this.summary.totalFiles && !this.summary.totalDirs) {
				this.$el.addClass('hidden');
			}

			this.$el.append($summary);
		}
	};
	window.FileSummary = FileSummary;
})();

