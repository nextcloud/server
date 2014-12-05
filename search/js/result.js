/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

//translations for result type ids, can be extended by apps
OC.Search.resultTypes={
	file: t('core','File'),
	folder: t('core','Folder'),
	image: t('core','Image'),
	audio: t('core','Audio')
};
OC.Search.hide=function(){
	$('#searchresults').hide();
	if($('#searchbox').val().length>2){
		$('#searchbox').val('');
		if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
			FileList.unfilter();
		}
	};
	if ($('#searchbox').val().length === 0) {
		if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
			FileList.unfilter();
		}
	}
};
OC.Search.showResults=function(results){
	if(results.length === 0){
		return;
	}
	if(!OC.Search.showResults.loaded){
		var parent=$('<div class="searchresults-wrapper"/>');
		$('#app-content').append(parent);
		parent.load(OC.filePath('search','templates','part.results.php'),function(){
			OC.Search.showResults.loaded=true;
			$('#searchresults').click(function(event){
				OC.Search.hide();
				event.stopPropagation();
			});
			$(document).click(function(event){
				OC.Search.hide();
				if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
					FileList.unfilter();
				}
			});
			OC.Search.lastResults=results;
			OC.Search.showResults(results);
		});
	} else {
		$('#searchresults tr.result').remove();
		$('#searchresults').show();
		jQuery.each(results, function(i, result) {
			var $row = $('#searchresults tr.template').clone();
			$row.removeClass('template');
			$row.addClass('result');

			$row.data('result', result);

			// generic results only have four attributes
			$row.find('td.info div.name').text(result.name);
			$row.find('td.info a').attr('href', result.link);

			$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'places/link') + ')');
			/**
			 * Give plugins the ability to customize the search results. For example:
			 * OC.search.customResults.file = function (row, item){
			 *  if(item.name.search('.json') >= 0) ...
			 * };
			 */
			if(OC.Search.hasFormatter(result.type)){
				OC.Search.getFormatter(result.type)($row, result);
			} else
			{
				// for backward compatibility add text div
				$row.find('td.info div.name').addClass('result')
				$row.find('td.result div.name').after('<div class="text"></div>');
				$row.find('td.result div.text').text(result.name);
				if(OC.search.customResults[result.type]){
					OC.search.customResults[result.type]($row, result);
				}
			}
			$('#searchresults tbody').append($row);
		});

		$('#searchresults').on('click', 'tr.result', function (event) {
			var $row = $(this);
			var result = $row.data('result');
			if(OC.Search.hasHandler(result.type)){
				var result = OC.Search.getHandler(result.type)($row, result, event);
				OC.Search.hide();
				event.stopPropagation();
				return result;
			}
		});
	}
};
OC.Search.showResults.loaded=false;

OC.Search.renderCurrent=function(){
	if($('#searchresults tr.result')[OC.search.currentResult]){
		var result=$('#searchresults tr.result')[OC.search.currentResult];
		$('#searchresults tr.result').removeClass('current');
		$(result).addClass('current');
	}
};

OC.Search.setFormatter('file', function ($row, result) {
	// backward compatibility:
	if (typeof result.mime !== 'undefined') {
		result.mime_type = result.mime;
	} else if (typeof result.mime_type !== 'undefined') {
		result.mime = result.mime_type;
	}

	$pathDiv = $('<div class="path"></div>').text(result.path)
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
		var mimeicon = result.mime.replace('/','-');
		$row.find('td.icon').css('background-image', 'url(' + OC.imagePath('core', 'filetypes/'+mimeicon) + ')');
		var dir = OC.dirname(result.path);
		if (dir === '') {
			dir = '/';
		}
		$row.find('td.info a').attr('href',
			OC.generateUrl('/apps/files/?dir={dir}&scrollto={scrollto}', {dir:dir, scrollto:result.name})
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

OC.Search.setFormatter('folder',  function ($row, result) {
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
OC.Search.setHandler('folder',  function ($row, result, event) {
	if (OCA.Files) {
		OCA.Files.App.fileList.changeDirectory(result.path);
		return false;
	} else {
		return true;
	}
});
