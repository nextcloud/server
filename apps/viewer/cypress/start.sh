#!/usr/bin/env bash
# RUN THIS SCRIPT FROM THE ROOT FOLDER OF YOUR APP
APP_NAME=${PWD##*/}

if [[ $APP_NAME == "cypress" ]]
then
	echo "Please run this app from your app root folder."
else
	echo "Launching docker server for the $APP_NAME app"
	cd cypress
	docker-compose up -d
	docker-compose exec --env APP_NAME=$APP_NAME -T nextcloud bash /initserver.sh
fi
