#!/usr/bin/env bash

set -euxo pipefail

for target in tests/lib/Sharing tests/Core/Sharing apps/sharing apps/files/tests/Sharing apps/files_sharing/tests/Listener/RestrictInteractionListenerTest.php apps/files_sharing/tests/Sharing; do
	if [ -d "$target" ] || [ -f "$target" ]; then
		./autotest.sh sqlite "$target"
	fi
done
