OC.search.catagorizeResults=function(results){
	var types={};
	for(var i=0;i<results.length;i++){
		var type=results[i].type;
		if(!types[type]){
			types[type]=[];
		}
		types[type].push(results[i]);
	}
	return types;
};
OC.search.hide=function(){
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
OC.search.showResults=function(results){
	if(results.length === 0){
		return;
	}
	if(!OC.search.showResults.loaded){
		var parent=$('<div/>');
		$('body').append(parent);
		parent.load(OC.filePath('search','templates','part.results.php'),function(){
			OC.search.showResults.loaded=true;
			$('#searchresults').click(function(event){
				OC.search.hide();
				event.stopPropagation();
			});
			$(document).click(function(event){
				OC.search.hide();
				if (FileList && typeof FileList.unfilter === 'function') { //TODO add hook system
					FileList.unfilter();
				}
			});
			OC.search.lastResults=results;
			OC.search.showResults(results);
		});
	}else{
		var types=OC.search.catagorizeResults(results);
		$('#searchresults').show();
		$('#searchresults tr.result').remove();
		var index=0;
		for(var typeid in types){
			var type=types[typeid];
			if(type.length>0){
				for(var i=0;i<type.length;i++){
					var row=$('#searchresults tr.template').clone();
					row.removeClass('template');
					row.addClass('result');
					
					row.data('type', typeid);
					row.data('name', type[i].name);
					row.data('text', type[i].text);
					row.data('index',index);
					
					if (i === 0){
						var typeName = OC.search.resultTypes[typeid];
						row.children('td.type').text(t('lib', typeName));
					}
					row.find('td.result div.name').text(type[i].name);
					row.find('td.result div.text').text(type[i].text);
					
					if (type[i].path) {
						var parent = OC.dirname(type[i].path);
						var containerName = OC.basename(parent);
						if (containerName === '') {
							containerName = '/';
						}
						var containerLink = OC.linkTo('files', 'index.php')
							+'?dir='+encodeURIComponent(parent)
							+'&scrollto='+encodeURIComponent(type[i].name);
						row.find('td.result a')
							.attr('href', containerLink)
							.attr('title', t('core', 'Show in {folder}', {folder: containerName}));
					} else {
						row.find('td.result a').attr('href', type[i].link);
					}
					
					index++;
					/** 
					 * Give plugins the ability to customize the search results. For example:
					 * OC.search.customResults.file = function (row, item){
				 	 *  if(item.name.search('.json') >= 0) ...
					 * };
					 */
					if(OC.search.customResults[typeid]){
						OC.search.customResults[typeid](row, type[i]);
					}
					$('#searchresults tbody').append(row);
				}
			}
		}
		$('#searchresults').on('click', 'result', function () {
			if ($(this).data('type') === 'Files') {
				//FIXME use ajax to navigate to folder & highlight file
			}
		});
	}
};
OC.search.showResults.loaded=false;

OC.search.renderCurrent=function(){
	if($('#searchresults tr.result')[OC.search.currentResult]){
		var result=$('#searchresults tr.result')[OC.search.currentResult];
		$('#searchresults tr.result').removeClass('current');
		$(result).addClass('current');
	}
};