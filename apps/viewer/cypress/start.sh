#! /bin/sh
appname=$1
dockername="cypress_testing_$appname"

docker run -p 8000:80 --rm --name $dockername --env BRANCH=master --detach nextcloudci/server:server-10
docker cp . $dockername:/var/www/html/apps/$appname
sleep 10
docker exec --user www-data $dockername php occ app:enable $appname
docker exec --user www-data $dockername php occ config:system:set force_language --value en
