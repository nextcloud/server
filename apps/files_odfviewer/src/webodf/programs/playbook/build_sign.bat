@echo on
set WWSDK=M:\blackberrysdk\webworkssdk
set BBWP=%WWSDK%\bbwp\bbwp
set DEPLOY=%WWSDK%\bbwp\blackberry-tablet-sdk\bin\blackberry-deploy
set JAVA_HOME=%WWSDK%\jre
set PATH=%PATH%;%JAVA_HOME%\bin

mkdir bin
mkdir signed

zip -r webodf.zip config.xml index.html icon.png scripts.js app sencha-touch.js sencha-touch.css webodf.js webodf.css ZoomIn.png ZoomOut.png

rem MAKE A DEBUG VERSION
del bin\webodf.bar
%BBWP% webodf.zip -d -o bin
if %errorlevel% neq 0 exit /b %errorlevel% 

%DEPLOY% -installApp -password ko -device 192.168.1.111 -package bin\webodf.bar
if %errorlevel% neq 0 exit /b %errorlevel% 

rem MAKE A SIGNED VERSION, (can be done only once for each buildId!)
rem %BBWP% webodf.zip -g U9gXpJXbGC -buildId 2 -o signed

