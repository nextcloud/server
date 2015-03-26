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

(function() {
	/**
	 * The FileSummary class encapsulates the file summary values and
	 * the logic to render it in the given container
	 *
	 * @constructs FileSummary
	 * @memberof OCA.Files
	 *
	 * @param $tr table row element
	 */
	var FileSummary = function($tr) {
		this.$el = $tr;
		this.clear();
		this.render();
	};

	FileSummary.prototype = {
		summary: {
			totalFiles: 0,
			totalDirs: 0,
			totalSize: 0,
			filter:'',
			sumIsPending:false
		},

		/**
		 * Adds file
		 * @param file file to add
		 * @param update whether to update the display
		 */
		add: function(file, update) {
			if (file.name && file.name.toLowerCase().indexOf(this.summary.filter) === -1) {
				return;
			}
			if (file.type === 'dir' || file.mime === 'httpd/unix-directory') {
				this.summary.totalDirs++;
			}
			else {
				this.summary.totalFiles++;
			}
			var size = parseInt(file.size, 10) || 0;
			if (size >=0) {
				this.summary.totalSize += size;
			} else {
				this.summary.sumIsPending = true;
			}
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
			if (file.name && file.name.toLowerCase().indexOf(this.summary.filter) === -1) {
				return;
			}
			if (file.type === 'dir' || file.mime === 'httpd/unix-directory') {
				this.summary.totalDirs--;
			}
			else {
				this.summary.totalFiles--;
			}
			var size = parseInt(file.size, 10) || 0;
			if (size >=0) {
				this.summary.totalSize -= size;
			}
			if (!!update) {
				this.update();
			}
		},
		setFilter: function(filter, files){
			this.summary.filter = filter.toLowerCase();
			this.calculate(files);
		},
		/**
		 * Returns the total of files and directories
		 */
		getTotal: function() {
			return this.summary.totalDirs + this.summary.totalFiles;
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
				totalSize: 0,
				filter: this.summary.filter,
				sumIsPending: false
			};

			for (var i = 0; i < files.length; i++) {
				file = files[i];
				if (file.name && file.name.toLowerCase().indexOf(this.summary.filter) === -1) {
					continue;
				}
				if (file.type === 'dir' || file.mime === 'httpd/unix-directory') {
					summary.totalDirs++;
				}
				else {
					summary.totalFiles++;
				}
				var size = parseInt(file.size, 10) || 0;
				if (size >=0) {
					summary.totalSize += size;
				} else {
					summary.sumIsPending = true;
				}
			}
			this.setSummary(summary);
		},
		/**
		 * Clears the summary
		 */
		clear: function() {
			this.calculate([]);
		},
		/**
		 * Sets the current summary values
		 * @param summary map
		 */
		setSummary: function(summary) {
			this.summary = summary;
			if (typeof this.summary.filter === 'undefined') {
				this.summary.filter = '';
			}
			this.update();
		},

		/**
		 * Renders the file summary element
		 */
		update: function() {
			if (!this.$el) {
				return;
			}
			if (!this.summary.totalFiles && !this.summary.totalDirs) {
				this.$el.addClass('hidden');
				return;
			}
			// There's a summary and data -> Update the summary
			this.$el.removeClass('hidden');
			var $dirInfo = this.$el.find('.dirinfo');
			var $fileInfo = this.$el.find('.fileinfo');
			var $connector = this.$el.find('.connector');
			var $filterInfo = this.$el.find('.filter');

			// Substitute old content with new translations
			$dirInfo.html(n('files', '%n folder', '%n folders', this.summary.totalDirs));
			$fileInfo.html(n('files', '%n file', '%n files', this.summary.totalFiles));
			var fileSize = this.summary.sumIsPending ? t('files', 'Pending') : OC.Util.humanFileSize(this.summary.totalSize);
			this.$el.find('.filesize').html(fileSize);

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
			if (this.summary.filter === '') {
				$filterInfo.html('');
				$filterInfo.addClass('hidden');
			} else {
				$filterInfo.html(' ' + n('files', 'matches \'{filter}\'', 'match \'{filter}\'', this.summary.totalDirs + this.summary.totalFiles, {filter: this.summary.filter}));
				$filterInfo.removeClass('hidden');
			}
		},
		render: function() {
			if (!this.$el) {
				return;
			}
			// TODO: ideally this should be separate to a template or something
			var summary = this.summary;
			var directoryInfo = n('files', '%n folder', '%n folders', summary.totalDirs);
			var fileInfo = n('files', '%n file', '%n files', summary.totalFiles);
			var filterInfo = '';
			if (this.summary.filter !== '') {
				filterInfo = ' ' + n('files', 'matches \'{filter}\'', 'match \'{filter}\'', summary.totalFiles + summary.totalDirs, {filter: summary.filter});
			}

			var infoVars = {
				dirs: '<span class="dirinfo">'+directoryInfo+'</span><span class="connector">',
				files: '</span><span class="fileinfo">'+fileInfo+'</span>'
			};

			// don't show the filesize column, if filesize is NaN (e.g. in trashbin)
			var fileSize = '';
			if (!isNaN(summary.totalSize)) {
				fileSize = summary.sumIsPending ? t('files', 'Pending') : OC.Util.humanFileSize(summary.totalSize);
				fileSize = '<td class="filesize">' + fileSize + '</td>';
			}

			var info = t('files', '{dirs} and {files}', infoVars, null, {'escape': false});

			var $summary = $('<td><span class="info">'+info+'<span class="filter">'+filterInfo+'</span></span></td>'+fileSize+'<td class="date"></td>');

			if (!this.summary.totalFiles && !this.summary.totalDirs) {
				this.$el.addClass('hidden');
			}

			this.$el.append($summary);
		}
	};
	OCA.Files.FileSummary = FileSummary;
})();

