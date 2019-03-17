#!/bin/bash

displayUsage()
{
    echo "---------------------------------------------------------------------------"
    echo "Build image: "
    echo "  ./docker-cli.sh build"
    echo ""
    echo "Deploy service: "
    echo "  ./docker-cli.sh deploy"
    echo ""
    echo "Undeploy service: "
    echo "  ./docker-cli.sh undeploy"
    echo ""
    echo "Start container: "
    echo "  ./docker-cli.sh start"
    echo ""
    echo "Stop container: "
    echo "  ./docker-cli.sh stop"
    echo ""
    echo "Connect to dev container: "
    echo "  ./docker-cli.sh exec"
    echo "---------------------------------------------------------------------------"
}


build()
{   
    docker-compose -f docker/docker-compose.yml build
}

deploy()
{
    build
    docker-compose -f docker/docker-compose.yml up -d
}

undeploy()
{
    read -p "Are you sure that you want to undeploy the service [y/n]? " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]
    then
	exit 0
    fi
    
    echo "Undeploying NextCloud"
    docker-compose -f docker/docker-compose.yml down
}

start()
{
    docker-compose -f docker/docker-compose.yml start
}

stop()
{
    docker-compose -f docker/docker-compose.yml stop
}

containerExec()
{
    docker-compose -f docker/docker-compose.yml exec nextcloud /bin/bash
}

case "$1" in
    build)
        build
    ;;
    deploy)
        deploy
    ;;
    undeploy)
        undeploy
    ;;
    start)
        start
    ;;
    stop)
        stop
    ;;
    exec)
        containerExec
    ;;
    *) displayUsage
    ;;
esac

