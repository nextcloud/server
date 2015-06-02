<?php
/** @var OC_L10N $l */
/** @var array $_ */
script('files_sharing', 'settings-personal');
style('files_sharing', 'settings-personal');
script('files_sharing', '3rdparty/gs-share/gs-share');
style('files_sharing', '3rdparty/gs-share/style');
?>

<?php if ($_['outgoingServer2serverShareEnabled']): ?>
	<div id="fileSharingSettings" class="section">
		<h2><?php p($l->t('Federated Cloud')); ?></h2>

		<p>
			<?php p($l->t('Your Federated Cloud ID: %s', [$_['cloudId']])); ?>
		</p>

		<p>

		<h3><?php p($l->t('Share your Federated Cloud Id:')); ?></h3>

		<p>

		<div class='gs-share'>
			<button data-url='<?php p($_['reference']); ?>'
					data-title='<?php p(urlencode($_['message_without_URL'])); ?>'
					class='js-gs-share gs-share--icon'>
			</button>
		</div>


		<img src='TODO: Add Diaspora Button' style="border: 0px solid;"
			 alt='Share on Diaspora'
			 data-url='http://sharetodiaspora.github.io/?title=<?php p($_['message_without_URL']); ?>&url=<?php p($_['reference']); ?>'/>
		<img src='TODO: Add Twitter Button' alt=' | Share on Twitter'
			 data-url='https://twitter.com/intent/tweet?text=<?php p(urlencode($_['message_with_URL'])); ?>'/>
		<img src='TODO: Add Facebook Button' alt=' | Share on Facebook'
			 data-url='https://www.facebook.com/sharer/sharer.php?u=<?php p($_['reference']); ?>'/>
		<img src='TODO: Add Google+ Button' alt=' | Share on Google+'
			 data-url='https://plus.google.com/share?url=<?php p($_['reference']); ?>'/>

		</p>

		<br/>

		<h3><?php p($l->t('Add your Federated Cloud Id to your homepage:')); ?></h3>

		<p>

			<a href="<?php p($_['reference']); ?>">
				<img src='TODO: Add Example Banner/Button'
					 alt='<?php p($_['message_with_URL']); ?>'/>
			</a>

			<br/>
			<?php p($l->t('HTML Code:')); ?>
			<br/>

			<xmp>
				<a href="<?php p($_['reference']); ?>">
					<img src='TODO: Add Banner/Button'
						 alt='<?php p($_['message_with_URL']); ?>'/>
				</a>
			</xmp>

		</p>

	</div>
<?php endif; ?>
