
<h1>Questions and Answers</h1>

<table cellspacing="0">
	<tbody>
		<?php foreach($_["kbe"] as $kb): ?>
			<tr>
				<td width="1"><?php if($kb["preview1"] <> "") { echo('<img class="preview" border="0" src="'.$kb["preview1"].'" />'); } ?> </a></td>
				<td class="name"><?php echo $kb["name"]; ?><br /><?php  echo('<span class="type">'.$kb['description'].'</span>'); ?><br />
				<?php if($kb['answer']<>'') echo('<br /><span class="type"><b>Answer:</b></span><br /><span class="type">'.$kb['answer'].'</span>');?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<span class="button"><a target="_blank" href="http://apps.owncloud.com/knowledgebase/editquestion.php?action=new">ASK A QUESTION</a></span>


