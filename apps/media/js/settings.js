$(document).ready(function() {
	$("button.scan").click(function(event){
		event.preventDefault();
		var parent=$(this).parent().parent();
		var path=parent.children('input').val();
		scan(path);
	});
	$("button.rescan").live('click', function(event) {
		event.preventDefault();
		var parent=$(this).parent().parent();
		var path=parent.contents().filter(function(){ return(this.nodeType == 3); }).text();
		path=path.trim();
		scan(path);
	});
	$("button.delete").live('click', function(event) {
		event.preventDefault();
		var parent=$(this).parent().parent();
		var path=parent.contents().filter(function(){ return(this.nodeType == 3); }).text();
		path=path.trim();
		var data="action=delete&path="+path;
		$.ajax({
			type: 'POST',
			url: 'ajax/api.php',
			cache: false,
			data: data,
			success: function(){
				parent.remove();
			}
		});
	});
	$( "#scanpath" ).autocomplete({
		source: "../../files/ajax/autocomplete.php?dironly=true",
		minLength: 1
	});
	$('#autoupdate').change(function(){
		$.ajax({
			url: 'ajax/autoupdate.php',
			data: "autoupdate="+$(this).attr('checked')
		});
	})
});

function scan(path){
	var data="action=scan&path="+path;
	$.ajax({
		type: 'POST',
		url: 'ajax/api.php',
		cache: false,
		data: data,
		success: function(songCount){
			var found=false;
			$('#folderlist').children('li').each(function(){
				var otherPath=$(this).contents().filter(function(){ return(this.nodeType == 3); }).text();
				otherPath=otherPath.trim();
				if(otherPath==path){
					found=true;
					$(this).children("span").html(songCount+" songs <button class='rescan prettybutton'>Rescan</button></span>");
				}
			})
			if(!found){
				$('#folderlist').children().last().before("<li>"+path+"<span class='right'>"+songCount+" songs <button class='rescan prettybutton'>Rescan</button></span></li>");
			}
		}
	});
}
