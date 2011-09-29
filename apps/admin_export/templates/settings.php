<form id="export" action="#" method="post">
    <fieldset class="personalblock">
        <legend><strong>Export this ownCloud instance</strong></legend>
        <p>This will create a compressed file that contains the data of this owncloud instance.
            Please choose which components should be included:
        </p>
        <p><input type="checkbox" id="user_files" name="user_files" value="true"><label for="user_files">User files</label><br/>
            <input type="checkbox" id="owncloud_system" name="owncloud_system" value="true"><label for="owncloud_system">ownCloud system files</label><br/>
            <input type="checkbox" id="owncloud_config" name="owncloud_config" value="true"><label for="owncloud_config">ownCloud configuration</label>
        </p>
        <input type="submit" name="admin_export" value="Export" />
    </fieldset>
</form>
