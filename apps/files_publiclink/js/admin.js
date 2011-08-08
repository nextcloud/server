$(document).ready(function() {
	$( "#path" ).autocomplete({
		source: "../../files/ajax/autocomplete.php",
		minLength: 1
	});
	$(".delete").live('click', function( event ) {
		event.preventDefault();
		var token=$(this).attr('data-token');
		var data="token="+token;
		$.ajax({
			type: 'GET',
			url: 'ajax/deletelink.php',
			cache: false,
			data: data,
			success: function(){
				$('#'+token).remove();
			}
		});
	});
	$('#newlink').submit(function( event ){
		event.preventDefault();
		var path=$('#path').val();
		var expire=0;
		var data='path='+path+'&expire='+expire;
		$.ajax({
			type: 'GET',
			url: 'ajax/makelink.php',
			cache: false,
			data: data,
			success: function(token){
				if(token){
					var html="<tr class='link' id='"+token+"'>";
					html+="<td class='path'>"+path+"</td>";
					html+="<td class='link'><a href='get.php?token="+token+"'>"+$('#baseUrl').val()+"?token="+token+"</a></td>"
					html+="<td><input type='submit' class='delete' data-token='"+token+" value='Delete' /></td>"
					html+="</tr>"
					$(html).insertBefore($('#newlink_row'));
					$('#path').val('');
				}
			}
		});
	});
});
