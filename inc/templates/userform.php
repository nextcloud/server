<?php
//handle addTo and removeFrom group
if(isset($_POST['groupAddRemove'])){
	$groupName=$_POST['groupname'];
	$users=explode(';',$_POST['users']);
	if($_POST['groupAddRemove']=='add'){
		foreach($users as $user){
			OC_USER::addToGroup($user,$groupName);
		}
	}elseif($_POST['groupAddRemove']=='remove'){
		foreach($users as $user){
			OC_USER::removeFromGroup($user,$groupName);
		}
	}
}
$action=$WEBROOT.'/settings/#user_management';
if(!empty($CONFIG_ERROR)){
	echo "<p class='error'>$CONFIG_ERROR</p>";
}
?>
<script type="text/javascript">
<?php
	$users=OC_USER::getUsers();
	$groups=OC_USER::getGroups();
	echo('var users='.json_encode($users).";\n");
	echo('var groups='.json_encode($groups).";\n");
?>
sellectAllUsers=function(){
	var check=document.getElementById('user_selectall');
	for(i in users){
		if(users[i]){
			document.getElementById('user_select_'+users[i]).checked=check.checked;
		}
	}
	getSellectedUsers();
}

getSellectedUsers=function(){
	sellectedUsers=new Array();
	for(i in users){
		if(users[i]){
			if(document.getElementById('user_select_'+users[i]).checked){
				sellectedUsers.push(users[i]);
			}
		}
	}
	document.getElementById('removeFromGroupUsers').value=sellectedUsers.join(';');
	document.getElementById('addToGroupUsers').value=sellectedUsers.join(';');
}

var sellectedUsers=new Array();

setGroup=function(){
	var select=document.getElementById('groupselect');
	var group=select.options[select.selectedIndex].value;
	document.getElementById('addToGroupName').value=group;
	document.getElementById('removeFromGroupName').value=group;
}

</script>
<p class='description'>All Users</p>
<table class='userlist'>
	<thead>
		<tr>
			<td class='sellect'><input type='checkbox' id='user_selectall' onchange='sellectAllUsers()' class='formstyle'/></td>
			<td class='name'>Name</td>
			<td class='groups'>Groups</td>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($users as $user){
			if($user){
				echo("<tr>\n");
				echo("<td class='sellect'><input type='checkbox' onchange='getSellectedUsers()' id='user_select_$user' class='formstyle'/></td>\n");
				echo("<td class='name'>$user</td>\n");
				$userGroups=OC_USER::getUserGroups($user);
				foreach($userGroups as &$userGroup){
					$userGroup=OC_USER::getGroupName($userGroup);
				}
				$userGroups=join(', ',$userGroups);
				echo("<td class='groups'>$userGroups</td>\n");
				echo("</tr>\n");
			}
		}
		?>
	</tbody>
</table>
<div id='sellectedUsersActions'>
Groups <select id='groupselect' onchange='setGroup()'>
<?php
foreach($groups as $group){
	echo("<option value='$group'>$group</option>");
}
?>
</select>
<form id='addToGroupForm' method="post" enctype="multipart/form-data" action="<?php echo($action);?>">
<input type='hidden' name='groupAddRemove' value='add'></input>
<input id='addToGroupName' type='hidden' name='groupname' value='<?php echo($groups[0]);?>'></input>
<input id='addToGroupUsers' type='hidden' name='users' value=''></input>
<input type='submit' value='Add'></input>
</form>
<form id='removeFromGroupForm' method="post" enctype="multipart/form-data" action="<?php echo($action);?>">
<input type='hidden' name='groupAddRemove' value='remove'></input>
<input id='removeFromGroupName' type='hidden' name='groupname' value='<?php echo($groups[0]);?>'></input>
<input id='removeFromGroupUsers' type='hidden' name='users' value=''></input>
<input type='submit' value='Remove'></input>
</form>
</div>
<p class='description'>Add User</p>
<?php
$newuserpassword=OC_USER::generatepassword();
?>
<form id='newUserForm' method="post" enctype="multipart/form-data" action="<?php echo($action);?>">
user name: <input type='text' name='new_username' class="formstyle"></input>
password <input type='text' name='new_password' class="formstyle" autocomplete="off" value='<?php echo($newuserpassword);?>'></input>
&nbsp;&nbsp;<input type='submit' value='create' class="formstyle"></input>
</form>
<p class='description'>Add Group</p>
<form id='newGroupForm'  method="post" enctype="multipart/form-data" action="<?php echo($action);?>">
<input type='hidden' name='creategroup' value='1' />
<input type='text' name='groupname' class="formstyle"></input>
<input type='submit' value='create' class="formstyle"></input>
</form>


