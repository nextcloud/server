#!/usr/bin/env bash

function recursive_optimize_images() {
cd $1;
optipng -o6 -strip all *.png;
jpegoptim --strip-all *.jpg;
for svg in `ls *.svg`;
do
    mv $svg $svg.opttmp;
    scour --create-groups --enable-id-stripping --enable-comment-stripping --shorten-ids --remove-metadata --strip-xml-prolog --no-line-breaks  -i $svg.opttmp -o $svg;
done;
rm *.opttmp
for dir in `ls -d */`;
do
    recursive_optimize_images $dir;
    cd ..;
done;
}

recursive_optimize_images ../
