<?php
$action=$WEBROOT.'/settings/#plugin_management';
if(isset($_POST['plugin_disable_id'])){
	$id=$_POST['plugin_disable_id'];
	$disable=$_POST['plugin_disable'];
	if($disable=='true'){
		OC_PLUGIN::addToBlacklist($id);
	}else{
		OC_PLUGIN::removeFromBlacklist($id);
	}
	header('location: '.$action);
	die();
}

if(isset($_POST['install_plugin']) and $_POST['install_plugin']=='true'){
	$file=$_FILES['plugin_file']['tmp_name'];
	OC_PLUGIN::installPlugin($file);
	header('location: '.$action);
	die();
}
$plugins=OC_PLUGIN::listPlugins();
$blacklist=OC_PLUGIN::loadBlackList();
?>
<script type="text/javascript">
<?php
	echo('var plugins='.json_encode($plugins).";\n");
	echo('var blacklist='.json_encode($blacklist).";\n");
?>

disablePlugin=function(id,disable){
	var form=document.getElementById('disableForm');
	var input=document.getElementById('plugin_disable_name');
	input.value=id;
	var input=document.getElementById('plugin_disable');
	input.value=disable;
	form.submit();
}
</script>
<p class='description'>Plugin List</p>
<form id='disableForm' action='<?php echo($action);?>' method="post" enctype="multipart/form-data">
<input id='plugin_disable_name' type='hidden' name='plugin_disable_id' value=''/>
<input id='plugin_disable' type='hidden' name='plugin_disable' value=''/>
</form>
<table class='pluginlist'>
	<thead>
		<tr>
			<td colspan='2'>Id</td>
			<td>Version</td>
			<td>Description</td>
			<td>Author</td>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($plugins as $plugin){
			$pluginData=OC_PLUGIN::getPluginData($plugin);
			$enabled=(array_search($plugin,$blacklist)===false);
			$enabledString=($enabled)?'enabled':'disabled';
			$enabledStringOther=(!$enabled)?'enable':'disable';
			$enabled=($enabled)?'true':'false';
			echo("<tr class='$enabledString'>\n");
			echo("<td class='name'>$plugin</td>");
			echo("<td class='disable'>(<a href='$action' onclick='disablePlugin(\"$plugin\",$enabled)'>$enabledStringOther</a>)</td>");
			echo("<td class='version'>{$pluginData['info']['version']}</td>");
			echo("<td>{$pluginData['info']['name']}</td>");
			echo("<td>{$pluginData['info']['author']}</td>");
			echo("</tr>\n");
		}
		?>
	</tbody>
</table>
<p class='description'>Install Plugin</p>
<form action='<?php echo($action);?>' method="post" enctype="multipart/form-data">
	<input class='formstyle' type='file' name='plugin_file'/>
	<input type='hidden' name='install_plugin' value='true'/>
	<input class='formstyle' type='submit'/>
</form>