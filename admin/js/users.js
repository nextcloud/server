$(document).ready(function(){
	// Vars we need
	var uid = "";
	var gid = "";

	//#########################################################################
	// Functions for editing the dom after user manipulation
	//#########################################################################

	// Manipulating the page after crteating a user
	function userCreated( username, groups ){
		// Add user to table
		var newrow = '<tr><td>'+username+'</td>';
		newrow = newrow+'<td>'+groups+'</td>';
		newrow = newrow+'<td x-uid="'+username+'"><a href="" class="edituserbutton">edit</a> | <a  class="removeuserbutton" href="">remove</a></td></tr>';
		$("#usertable").append( newrow  );

		// Clear forms
		$("input[x-use='createuserfield']").val( "" );
		$("input[x-use='createusercheckbox']").attr( "checked", false );
	}

	// Manipulating the page after crteating a user
	function userEdited( username, groups ){
		// Edit table
		var newrow = '<tr><td>'+username+'</td>';
		newrow = newrow+'<td>'+groups+'</td>';
		newrow = newrow+'<td x-uid="'+username+'"><a href="" class="edituserbutton">edit</a> | <a  class="removeuserbutton" href="">remove</a></td></tr>';
		$("td[x-uid='"+username+"']").replace( newrow  );
	}

	function userRemoved( username ){
		$( "td[x-uid='"+username+"']" ).parent().remove();
	}

	function groupCreated( groupname ){
		var newrow = '<tr><td x-gid="'+groupname+'">' + groupname + '</td>';
		newrow = newrow + '<td><a class="removegroupbutton" href="">remove</a></td></tr>';
		$("#grouptable").append( newrow  );

		// Delete form content
		$("input[x-use='creategroupfield']").val( "" );

		// Add group option to Create User and Edit User
		createuser = '<input x-gid="'+groupname+'" type="checkbox" name="groups[]" value="'+groupname+'" /><span  x-gid="'+groupname+'">'+groupname+'<br /></span>';
		$("#createusergroups").append( createuser );
		$("#editusergroups").append( createuser );
	}

	function groupRemoved( groupname ){
		// Delete the options
		$( "td[x-gid='"+groupname+"']" ).parent().remove();
		$( "span[x-gid='"+groupname+"']" ).remove();
		$( "input[x-gid='"+groupname+"']" ).remove();

		// remove it from user list
		$( "td[x-use='usergroups']" ).each(function(index){
			var content = $(this).text();
			var list = content.split( ", " );
			var newlist = [];
			for( var i = 0; i < list.length; i++ ){
				var temp = list[i];
				if( temp != groupname ){
					newlist.push( temp );
				}
			}
			var newstring = newlist.join( ", " );
			$(this).html( newstring )
		});

	}

	//#########################################################################
	// Clicking on buttons
	//#########################################################################

	// Show the create user form
	$( "#createuseroptionbutton" )
		.click(function(){
			$( "#createuserform" ).toggle();
			return false;
		});

	// Create a new user
	$( "#createuserbutton" )
		.click(function(){
			// Create the post data
			var post = $( "#createuserdata" ).serialize();

			// Ajax call
			$.post( 'ajax/createuser.php', post, function(data){
				// If it says "success" then we are happy
				if( data.status == "success" ){
					userCreated( data.data.username, data.data.groups );
				}
				else{
					alert( "Bug By Jakob (c)" );
				}
			});
			return false;
		});

	$( ".edituserbutton" ).live( 'click',  function(){
			uid = $( this ).parent().attr( 'x-uid' );
			$("#edituserusername").html(uid);
			$("#edituserform").dialog("open");
			return false;
		});

	$( ".removeuserbutton" ).live( 'click', function() {
			uid = $( this ).parent().attr( 'x-uid' );
			$("#deleteuserusername").html(uid);
			$("#deleteusernamefield").val(uid);
			$("#removeuserform").dialog( "open" );
			return false;
		});

	$( "#creategroupbutton" )
		.click(function(){
			// Serialize the data
			var post = $( "#creategroupdata" ).serialize();
			// Ajax foo
			$.post( 'ajax/creategroup.php', post, function(data){
				if( data.status == "success" ){
					groupCreated( data.data.groupname );
				}
				else{
					alert( "something went wrong! sorry!" );
				}
			});
			return false;
		});

	$( ".removegroupbutton" ).live( 'click', function(){
			gid = $( this ).parent().attr( 'x-gid' );
			$("#removegroupgroupname").html(gid);
			$("#removegroupnamefield").val(gid);
			$("#removegroupform").dialog( "open" );
			return false;
		});

	//#########################################################################
	// Dialogs
	//#########################################################################

	// Edit user dialog
	$( "#edituserform" ).dialog({
		autoOpen: false,
		height: 500,
		width: 500,
		modal: true,
		buttons: {
			"Edit user": function() {
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

	// Removing users
	$( "#removeuserform" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove user": function() {
				var post = $( "#removeuserdata" ).serialize();
				$.post( 'ajax/removeuser.php', post, function(data){
					if( data.status == "success" ){
						userRemoved( uid );
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


	// Dialog for adding users
	$( "#removegroupform" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove group": function(){
				var post = $( "#removegroupdata" ).serialize();
				$.post( 'ajax/removegroup.php', post, function(data){
					if( data.status == "success" ){
						groupRemoved( gid );
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

} );

