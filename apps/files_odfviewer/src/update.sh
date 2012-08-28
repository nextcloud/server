cd webodf
git pull
cd ..
rm -Rf build
mkdir build
cd build
cmake ../webodf
make webodf.js webodf-debug.js
cp webodf/webodf*.js ../../js/
