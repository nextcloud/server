<?php if(is_null($_["kbe"])):?>
	<div class="personalblock">
		<p><?php echo $l->t('Problems connecting to help database.');?>
		<a href="http://apps.owncloud.com/kb"><?php echo $l->t('Go there manually.');?></a>
	</div>
<?php else:?>
	<?php foreach($_["kbe"] as $kb): ?>
	<div class="personalblock">
		<?php if($kb["preview1"] <> "") { echo('<img class="preview" src="'.$kb["preview1"].'" />'); } ?>
		<p><strong><?php if($kb['detailpage']<>'') echo('<p><a target="_blank" href="'.$kb['detailpage'].'"><strong>'.$kb["name"].'</strong></a></p>');?></strong></p>
		<p><?php echo $kb['description'];?></p>
		<?php if($kb['answer']<>'') echo('<p><strong>'.$l->t('Answer').':</strong><p>'.$kb['answer'].'</p>');?>
	</div>
	<?php endforeach; ?>

	<a class="button newquestion" href="http://apps.owncloud.com/knowledgebase/editquestion.php?action=new" target="_blank"><?php echo $l->t( 'Ask a question' ); ?></a>
	<?php
		$url=OC_Helper::linkTo( "settings", "help.php" ).'?page=';
		$pageNavi=OC_Util::getPageNavi($_['pagecount'],$_['page'],$url);
		$pageNavi->printPage();
endif?>
