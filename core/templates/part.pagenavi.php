<center>
	<table class="pager" cellspacing="0" cellpadding="0" border="0">
		<tr><td width="50%"></td>
			<td width="1">
				<?php if($_['page']>0):?>
					<span class="pagerbutton1"><a href="<?php echo $_['url'].($_['page']-1);?>"><?php echo $l->t( 'prev' ); ?></a>&nbsp;&nbsp;</span>
				<?php endif; ?>
			</td>
			<td width="1">
				<?php if ($_['pagestart']>0):?>
					...
				<?php endif;?>
				<?php for ($i=$_['pagestart']; $i < $_['pagestop'];$i++):?>
					<?php if ($_['page']!=$i):?>
						<a href="<?php echo $_['url'].$i;?>"><?php echo $i+1;?>&nbsp;</a>
					<?php else:?>
						<?php echo $i+1;?>&nbsp;
					<?php endif?>
				<?php endfor;?>
				<?php if ($_['pagestop']<$_['pagecount']):?>
					...
				<?php endif;?>
			</td>
			<td width="1">
				<?php if(($_['page']+1)<$_['pagecount']):?>
					<span class="pagerbutton2"><a href="<?php echo $_['url'].($_['page']+1);?>"><?php echo $l->t( 'next' ); ?></a></span>
				<?php endif; ?>
			</td>
		<td width="50%"></td></tr>
	</table>
</center>
