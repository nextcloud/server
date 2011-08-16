<?php if(is_null($_["kbe"])):?>
	Can't connect to Q&amp;A database
<?php else:?>
	<table class="help">
		<tbody>
			<?php foreach($_["kbe"] as $kb): ?>
				<tr class="entryrow">
					<td width="1"><?php if($kb["preview1"] <> "") { echo('<img class="preview" border="0" src="'.$kb["preview1"].'" />'); } ?> </a></td>
					<td class="entry"><p><strong><?php echo $kb["name"]; ?></strong></p><?php  echo('<span class="type">'.$kb['description'].'</span>'); ?>
					<?php if($kb['answer']<>'') echo('<br /><span class="type"><b>Answer:</b></span><br /><span class="type">'.$kb['answer'].'</span>');?>
					<?php if($kb['detailpage']<>'') echo('<br /><a target="_blank" href="'.$kb['detailpage'].'"><b>read more</b></a>')?>
					<br /></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
		$url=OC_Helper::linkTo( "settings", "help.php" ).'?page=';
		$pageNavi=OC_Util::getPageNavi($_['pagecount'],$_['page'],$url);
		$pageNavi->printPage();
	?>
	<br /><a target="_blank" class="newquestion" href="http://apps.owncloud.com/knowledgebase/editquestion.php?action=new"><?php echo $l->t( 'Ask a question' ); ?></a>
<?php endif;?>


