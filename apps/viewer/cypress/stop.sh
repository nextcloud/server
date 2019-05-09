#! /bin/sh
appname=$1
dockername="cypress_testing_$appname"

docker kill $dockername