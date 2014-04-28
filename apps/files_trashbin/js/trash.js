/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global OC, t, BreadCrumb, FileActions, FileList, Files */
$(document).ready(function() {
	var deletedRegExp = new RegExp(/^(.+)\.d[0-9]+$/);

	/**
	 * Convert a file name in the format filename.d12345 to the real file name.
	 * This will use basename.
	 * The name will not be changed if it has no ".d12345" suffix.
	 * @param name file name
	 * @return converted file name
	 */
	function getDeletedFileName(name) {
		name = OC.basename(name);
		var match = deletedRegExp.exec(name);
		if (match && match.length > 1) {
			name = match[1];
		}
		return name;
	}

	Files.updateStorageStatistics = function() {
		// no op because the trashbin doesn't have
		// storage info like free space / used space
	};

	if (typeof FileActions !== 'undefined') {
		FileActions.register('all', 'Restore', OC.PERMISSION_READ, OC.imagePath('core', 'actions/history'), function(filename) {
			var tr = FileList.findFileEl(filename);
			var deleteAction = tr.children("td.date").children(".action.delete");
			deleteAction.removeClass('delete-icon').addClass('progress-icon');
			disableActions();
			$.post(OC.filePath('files_trashbin', 'ajax', 'undelete.php'), {
					files: JSON.stringify([filename]),
					dir: FileList.getCurrentDirectory()
				},
			    FileList._removeCallback
			);
		}, t('files_trashbin', 'Restore'));
	};

	FileActions.register('all', 'Delete', OC.PERMISSION_READ, function() {
		return OC.imagePath('core', 'actions/delete');
	}, function(filename) {
		$('.tipsy').remove();
		var tr = FileList.findFileEl(filename);
		var deleteAction = tr.children("td.date").children(".action.delete");
		deleteAction.removeClass('delete-icon').addClass('progress-icon');
		disableActions();
		$.post(OC.filePath('files_trashbin', 'ajax', 'delete.php'), {
				files: JSON.stringify([filename]),
				dir: FileList.getCurrentDirectory()
			},
			FileList._removeCallback
		);
	});

	/**
	 * Override crumb URL maker (hacky!)
	 */
	FileList.breadcrumb.getCrumbUrl = function(part, index) {
		if (index === 0) {
			return OC.linkTo('files', 'index.php');
		}
		return OC.linkTo('files_trashbin', 'index.php')+"?dir=" + encodeURIComponent(part.dir);
	};

	Files.generatePreviewUrl = function(urlSpec) {
		return OC.generateUrl('/apps/files_trashbin/ajax/preview.php?') + $.param(urlSpec);
	};

	Files.getDownloadUrl = function(action, params) {
		// no downloads
		return '#';
	};

	Files.getAjaxUrl = function(action, params) {
		var q = '';
		if (params) {
			q = '?' + OC.buildQueryString(params);
		}
		return OC.filePath('files_trashbin', 'ajax', action + '.php') + q;
	};


	/**
	 * Override crumb making to add "Deleted Files" entry
	 * and convert files with ".d" extensions to a more
	 * user friendly name.
	 */
	var oldMakeCrumbs = BreadCrumb.prototype._makeCrumbs;
	BreadCrumb.prototype._makeCrumbs = function() {
		var parts = oldMakeCrumbs.apply(this, arguments);
		// duplicate first part
		parts.unshift(parts[0]);
		parts[1] = {
			dir: '/',
			name: t('files_trashbin', 'Deleted Files')
		};
		for (var i = 2; i < parts.length; i++) {
			parts[i].name = getDeletedFileName(parts[i].name);
		}
		return parts;
	};

	FileActions.actions.dir = {
		// only keep 'Open' action for navigation
		'Open': FileActions.actions.dir.Open
	};
});

function enableActions() {
	$(".action").css("display", "inline");
	$(":input:checkbox").css("display", "inline");
}

function disableActions() {
	$(".action").css("display", "none");
	$(":input:checkbox").css("display", "none");
}

