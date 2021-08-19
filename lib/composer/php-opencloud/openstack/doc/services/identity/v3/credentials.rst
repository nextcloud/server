Credentials
===========

Add credential
--------------

Create a secret/access pair for use with ec2 style auth. This operation will generates a new set of credentials that
map the user/tenant pair.

.. sample:: identity/v3/credentials/add_cred.php
.. refdoc:: OpenStack/Identity/v3/Service.html#method_createCredential

List credentials
----------------

List all credentials for a given user.

.. sample:: identity/v3/credentials/list_creds.php
.. refdoc:: OpenStack/Identity/v3/Service.html#method_listCredentials

Show credential details
-----------------------

Retrieve a user's access/secret pair by the access key.

.. sample:: identity/v3/credentials/get_cred.php
.. refdoc:: OpenStack/Identity/v3/Service.html#method_getCredential

Update credential
-----------------

.. sample:: identity/v3/credentials/update_cred.php
.. refdoc:: OpenStack/Identity/v3/Models/Credential.html#method_update

Delete credential
-----------------

Delete a user's access/secret pair.

.. sample:: identity/v3/credentials/delete_cred.php
.. refdoc:: OpenStack/Identity/v3/Models/Credential.html#method_delete
