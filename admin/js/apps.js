$("div[x-use='appenableddiv']").live( "click", function(){
	appid = $(this).parent().parent().attr("x-uid");

	if($(this).text() == "enabled"){
		$(this).html( "disabled" );
		$(this).parent().removeClass( "enabled" );
		$(this).parent().addClass( "disabled" );
		//$.post( "ajax/disableapp.php", $(appid).serialize(), function(data){} );
		$.post( "ajax/disableapp.php", { appid: appid }, function(data){ alert(data.status);});
	}
	else if($(this).text() == "disabled"){
		$(this).html( "enabled" );
		$(this).parent().removeClass( "disabled" );
		$(this).parent().addClass( "enabled" );
		$.post( "ajax/enableapp.php", { appid: appid }, function(data){ alert(data.status);} );
	}
});