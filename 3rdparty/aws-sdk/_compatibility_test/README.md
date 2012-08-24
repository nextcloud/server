# Compatibility Test

## Via your web browser

1. Upload `sdk_compatibility_test.php` to the web-accessible root of your website.
For example, if your website is `www.example.com`, upload it so that you can get
to it at `www.example.com/sdk_compatibility_test.php`

2. Open your web browser and go to the page you just uploaded.


## Via the command line

### Windows

1. Upload `sdk_compatibility_test_cli.php` to your server via SFTP.

2. SSH/RDP into the machine, and find the directory where you uploaded the test.

3. Run the test, and review the results:

	php .\sdk_compatibility_test_cli.php


### Non-Windows (Mac or *nix)

1. Upload `sdk_compatibility_test_cli.php` to your server via SFTP.

2. SSH into the machine, and find the directory where you uploaded the test.

3. Set the executable bit:

	chmod +x ./sdk_compatibility_test_cli.php

4. Run the test, and review the results:

	./sdk_compatibility_test_cli.php
