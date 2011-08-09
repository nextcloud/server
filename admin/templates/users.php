<div id="controls">
	<form id="newgroup">
		<input id="newgroupname" placeholder="name"></input>
		<input type="submit" value="create"></input>
	</form>
	<form id="newuser">
		<input id="newusername" placeholder="username"></input>
		<input type="password" id="newuserpassword" placeholder="password"></input>
		<select id="newusergroups" data-placeholder="groups" title="groups" multiple="multiple">
			<?php foreach($_["groups"] as $group): ?>
				<option value="<?php echo $group['name'];?>"><?php echo $group['name'];?></option>
			<?php endforeach;?>
		</select>
		<input type="submit" value="Create"></input>
	</form>
</div>
<ul id="leftcontent">
	<?php foreach($_["groups"] as $group): ?>
		<li data-gid="<?php echo $group["name"]; ?>">
			<?php echo $group["name"] ?>
		</li>
	<?php endforeach; ?>
</ul>
<div id="rightcontent">
	<table>
		<?php foreach($_["users"] as $user): ?>
			<tr data-uid="<?php echo $user["name"] ?>">
				<td class="select"><input type="checkbox"></input></td>
				<td class="name"><?php echo $user["name"]; ?></td>
				<td class="groups"><?php if( $user["groups"] ){ echo $user["groups"]; }else{echo "&nbsp";} ?></td>
				<td class="remove">
					<?php if($user['name']!=OC_User::getUser()):?>
						<img alt="Remove" title="Remove" class='svg' src='<?php echo image_path('core','actions/delete.svg') ?>'/>
					<?php endif;?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>
