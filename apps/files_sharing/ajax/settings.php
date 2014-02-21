<?php

if(!\OCP\App::isEnabled('files_sharing')){
	\OCP\Response::setStatus(410); // GONE
}
