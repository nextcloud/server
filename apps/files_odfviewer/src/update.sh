rm -rf webodf
git clone https://git.gitorious.org/odfkit/webodf.git
rm -Rf build
mkdir build
cd build
cmake ../webodf
make webodf.js webodf-debug.js
cp webodf/webodf*.js ../../js/
cd ..
rm -rf webodf
rm -rf build