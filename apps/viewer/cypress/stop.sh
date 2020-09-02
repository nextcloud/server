#!/usr/bin/env bash
# RUN THIS SCRIPT FROM THE ROOT FOLDER OF YOUR APP
appname=${PWD##*/}

if [[ $appname == "cypress" ]]
then
	echo "Please run this app from your app root folder."
else
	echo "Killing server for the $appname app"
	cd cypress
	docker-compose stop
fi
