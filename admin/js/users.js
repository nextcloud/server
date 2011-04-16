$(document).ready(function(){
	// Vars we need
	var uid = "";
	var gid = "";
	// Dialog for adding users
	$( "#adduser-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Create an account": function() {
				var post = $( "#createuserdata" ).serialize();
				$.post( 'ajax/createuser.php', post, function(data){
					if( data.status == "success" ){
						var newrow = '<tr><td>' + data.data.username + '</td>';
						newrow = newrow + '<td>' + data.data.groups + '</td>';
						newrow = newrow + '<td><a href="" class="edituser-button">edit</a> | <a  class="removeuser-button" href="">remove</a></td></tr>';
						$("#userstable").append( newrow  );
					}
					else{
						alert( "Bug By Jakob (c)" );
					}
				});
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			true;
		}
	});

	$( "#adduser-button" )
		.click(function() {
			$( "#adduser-form" ).dialog( "open" );
			return false;
		});

	// Dialog for adding users
	$( "#edituser-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Edit password": function() {
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			true;
		}
	});

	$( ".edituser-button" )
		.click(function(){
			uid = $( this ).parent().attr( 'x-uid' );
			$("#edituserusername").html(uid);
			$("#edituser-form").dialog("open");
			return false;
		});

	// Removing users
	$( "#removeuser-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove user": function() {
				var post = $( "#removeuserdata" ).serialize();
				$.post( 'ajax/removeuser.php', post, function(data){
					if( data.status == "success" ){
						$( "a[x-uid='"+uid+"']" ).parent().remove();
					}
					else{
						alert( "Bug By Jakob (c)" );
					}
				});
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			true;
		}
	});

	$( ".removeuser-button" )
		.click(function() {
			uid = $( this ).parent().attr( 'x-uid' );
			$("#deleteuserusername").html(uid);
			$("#deleteusernamefield").val(uid);
			$( "#removeuser-form" ).dialog( "open" );
			return false;
		});

	// Add a group
	$( "#creategroupbutton" )
		.click(function(){
			var post = $( "#creategroupdata" ).serialize();
			$.post( 'ajax/creategroup.php', post, function(data){
				if( data.status == "success" ){
					var newrow = '<tr><td>' + data.data.groupname + '</td>';
					newrow = newrow + '<td><a class="removegroup-button" href="">remove</a></td></tr>';
					$("#groupstable").append( newrow  );
				}
				else{
					alert( "something went wrong! sorry!" );
				}
			});
			return false;
		});

	// Dialog for adding users
	$( "#removegroup-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove group": function(){
				var post = $( "#removegroupdata" ).serialize();
				$.post( 'ajax/removegroup.php', post, function(data){
					if( data.status == "success" ){
						$( "a[x-gid='"+gid+"']" ).parent().remove();
					}
					else{
						alert( "Bug By Jakob (c)" );
					}
				});
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function(){
			true;
		}
	});

	$( ".removegroup-button" )
		.click(function(){
			gid = $( this ).parent().attr( 'x-gid' );
			$("#deletegroupgroupname").html(gid);
			$("#deletegroupnamefield").val(gid);
			$("#removegroup-form").dialog( "open" );
			return false;
		});
} );
