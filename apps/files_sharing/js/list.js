$(document).ready(function() {
	$( "#source" ).autocomplete({
		source: "../../files/ajax/autocomplete.php",
		minLength: 1
	});
	$( "#uid_shared_with" ).autocomplete({
		source: "ajax/userautocomplete.php",
		minLength: 1
	});
	$("button.delete").live('click', function( event ) {
		event.preventDefault();
// 		var row=$(this);
		var source=$(this).attr('data-source');
		var uid_shared_with=$(this).attr('data-uid_shared_with');
		var data='source='+encodeURIComponent(source)+'&uid_shared_with='+encodeURIComponent(uid_shared_with);
		$.ajax({
			type: 'GET',
			url: 'ajax/unshare.php',
			cache: false,
			data: data
// 			success: function(){
// 				row.remove();
// 			}
		});
	});
	$('#share_item').submit(function( event ){
		event.preventDefault();
		var source=$('#source').val();
		var uid_shared_with=$('#uid_shared_with').val();
		var permissions=$('#permissions').val()||0;
		var data='source='+source+'&uid_shared_with='+uid_shared_with+'&permissions='+permissions;
		$.ajax({
			type: 'GET',
			url: 'ajax/share.php',
			cache: false,
			data: data,
// 			success: function(token){
// 				if(token){
// 					var html="<tr class='link' id='"+token+"'>";
// 					html+="<td class='path'>"+path+"</td>";
// 					var expire=($('#expire').val())?$('#expire').val():'Never'
// 					html+="<td class='expire'>"+expire+"</td>"
// 					html+="<td class='link'><a href='get.php?token="+token+"'>"+$('#baseUrl').val()+"?token="+token+"</a></td>"
// 					html+="<td><button class='delete fancybutton' data-token='"+token+"'>Delete</button></td>"
// 					html+="</tr>"
// 					$(html).insertBefore($('#newlink_row'));
// 					$('#expire').val('');
// 					$('#expire_time').val('');
// 					$('#path').val('');
// 				}
// 			}
		});
	});
});