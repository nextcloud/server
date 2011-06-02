$(document).ready(function() {
	$( "#expire" ).datepicker({
		dateFormat:'MM d, yy',
		altField: "#expire_time",
		altFormat: "yy-mm-dd"
	});
	$( "#path" ).autocomplete({
		source: "../../files/ajax/autocomplete.php",
		minLength: 1
	});
	$("button.delete").live('click', function() {
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
	$('#newlink').submit(function(){
		event.preventDefault();
		var path=$('#path').val();
		var expire=$('#expire_time').val()||0;
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
					var expire=($('#expire').val())?$('#expire').val():'Never'
					html+="<td class='expire'>"+expire+"</td>"
					html+="<td class='link'><a href='get.php?token="+token+"'>"+$('#baseUrl').val()+"?token="+token+"</a></td>"
					html+="<td><button class='delete fancybutton' data-token='"+token+"'>Delete</button></td>"
					html+="</tr>"
					$(html).insertBefore($('#newlink_row'));
					$('#expire').val('');
					$('#expire_time').val('');
					$('#path').val('');
				}
			}
		});
	})
});