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
}
OC.search.hide=function(){
	$('#searchresults').hide();
	if($('#searchbox').val().length>2){
		$('#searchbox').val('');
	};
}
OC.search.showResults=function(results){
	if(results.length==0){
		return;
	}
	if(!OC.search.showResults.loaded){
		var parent=$('<div/>');
		$('body').append(parent);
		parent.load(OC.filePath('search','templates','part.results.php'),function(){
			OC.search.showResults.loaded=true;
			$('#searchresults').click(function(event){
				event.stopPropagation();
			});
			$(window).click(function(event){
				OC.search.hide();
			});
			OC.search.lastResults=results;
			OC.search.showResults(results);
		});
	}else{
		var types=OC.search.catagorizeResults(results);
		$('#searchresults').show();
		$('#searchresults tr.result').remove();
		var index=0;
		for(var name in types){
			var type=types[name];
			if(type.length>0){
				var row=$('#searchresults tr.template').clone();
				row.removeClass('template');
				row.addClass('result');
				row.children('td.type').text(name);
				row.find('td.result a').attr('href',type[0].link);
				row.find('td.result div.name').text(type[0].name);
				row.find('td.result div.text').text(type[0].text);
				row.data('index',index);
				index++;
				if(OC.search.customResults[name]){//give plugins the ability to customize the entries in here
					OC.search.customResults[name](row,type[0]);
				}
				$('#searchresults tbody').append(row);
				for(var i=1;i<type.length;i++){
					var row=$('#searchresults tr.template').clone();
					row.removeClass('template');
					row.addClass('result');
					row.find('td.result a').attr('href',type[i].link);
					row.find('td.result div.name').text(type[i].name);
					row.find('td.result div.text').text(type[i].text);
					row.data('index',index);
					index++;
					if(OC.search.customResults[name]){//give plugins the ability to customize the entries in here
						OC.search.customResults[name](row,type[i]);
					}
					$('#searchresults tbody').append(row);
				}
			}
		}
	}
}
OC.search.showResults.loaded=false;

OC.search.renderCurrent=function(){
	if($('#searchresults tr.result')[OC.search.currentResult]){
		var result=$('#searchresults tr.result')[OC.search.currentResult];
		$('#searchresults tr.result').removeClass('current');
		$(result).addClass('current');
	}
}
