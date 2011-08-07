<?php $app=$_['app']; ?>
<h1><?php echo $app["name"]; ?></h1>
<?php  echo('<span class="type">'.$app['typename'].'</span>'); ?><br />
<span class="date"><?php echo $l->l('datetime', $app["changed"]); ?></span><br />


<table cellspacing="6" border="0" width="100%">
	<tr>
		<td width="1" valign="top">
			<?php if($app["preview1"]<>"") { echo('<img class="preview" border="0" src="'.$app["preview1"].'" /><br />'); } ?> 
			<?php if($app["preview2"]<>"") { echo('<img class="preview" border="0" src="'.$app["preview2"].'" /><br />'); } ?> 
			<?php if($app["preview3"]<>"") { echo('<img class="preview" border="0" src="'.$app["preview3"].'" /><br />'); } ?> 
		</td>
		<td class="description" valign="top">
		<?php echo $app["description"]; ?>
		<br />
		<?php  echo('<a class="description" target="_blank" href="'.$app["detailpage"].'">'.$l->t( 'read more' ).'</a><br />');  ?>
		</td>
		<td width="1" valign="top"><a class="prettybutton" href=""><?php echo $l->t( 'Install' ); ?></a></td>
	</tr>
</table>

