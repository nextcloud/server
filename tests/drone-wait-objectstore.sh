#!/bin/bash

if [ "$OBJECT_STORE" == "swift" ]; then
    echo "waiting for swift"
    until curl -I http://dockswift:5000/v3
    do
        sleep 2
    done
    sleep 30
fi
