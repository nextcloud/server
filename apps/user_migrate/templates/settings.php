<form id="export" action="#" method="post">
    <fieldset class="personalblock">
        <legend><strong><?php echo $l->t('Export your user account');?></strong></legend>
        <p><?php echo $l->t('This will create a compressed file that contains your ownCloud account.');?>
        </p>
        <input type="submit" name="user_export" value="Export" />
    </fieldset>
</form>