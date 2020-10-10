#!/usr/bin/env bash
# RUN THIS SCRIPT FROM THE ROOT FOLDER OF YOUR APP
APP_NAME=${PWD##*/}
CYPRESS_baseUrl=http://127.0.0.1:8082/index.php

if [[ $APP_NAME == "cypress" ]]
then
	echo "Please run this app from your app root folder."
else
	echo "Launching docker server for the $APP_NAME app"
	cd cypress
	docker-compose pull
	docker-compose up -d --force-recreate
	npm run wait-on $CYPRESS_baseUrl
	echo "Nextcloud successfully installed"
	docker-compose exec --env APP_NAME=$APP_NAME -T nextcloud bash /initserver.sh
	echo "Nextcloud successfully configured"
fi
