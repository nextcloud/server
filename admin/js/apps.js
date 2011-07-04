$("input[x-use='appenablebutton']").live( "click", function(){
	appid = $(this).parent().parent().attr("x-uid");

	//alert("dsfsdfsdf");
	if($(this).val() == "enabled"){
		$(this).attr("value","disabled");
		$(this).removeClass( "enabled" );
		$(this).addClass( "disabled" );
		//$.post( "ajax/disableapp.php", $(appid).serialize(), function(data){} );
		$.post( "ajax/disableapp.php", { appid: appid }, function(data){ alert(data.status);});
	}
	else if($(this).val() == "disabled"){
		$(this).attr("value","enabled");
		$(this).removeClass( "disabled" );
		$(this).addClass( "enabled" );
		$.post( "ajax/enableapp.php", { appid: appid }, function(data){ alert(data.status);} );
	}
});