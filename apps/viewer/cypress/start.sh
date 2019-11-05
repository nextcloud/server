#! /bin/sh
# RUN THIS SCRIPT FROM THE ROOT FOLDER OF YOUR APP
appname=${PWD##*/}

if [[ $appname == "cypress" ]]
then
	echo "Please run this app from your app root folder."
else
	echo "Launching docker server for the $appname app"
	dockername="cypress_testing_$appname"

	docker run -p 8000:80 --rm --name $dockername --volume $(pwd):/var/www/html/apps/$appname --env BRANCH=master --detach nextcloudci/server:server-15
	npm i --no-save wait-on
	npm run wait-on -- http://localhost:8000
	docker exec --user www-data $dockername php occ app:enable $appname
	docker exec --user www-data $dockername php occ config:system:set force_language --value en
fi
