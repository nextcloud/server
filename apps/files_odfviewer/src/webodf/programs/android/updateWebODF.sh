# /bin/bash
cd assets
for f in `find . -type f`; do cp ../../webodf/$f $f; done
