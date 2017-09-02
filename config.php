<?php

//file paths
define('APP_ROOT', dirname(__FILE__));
define('INCLUDE_ROOT', APP_ROOT . "/func");
define('OBJECT_ROOT', APP_ROOT . "/obj");
define('DBMGR_ROOT', APP_ROOT . "/DataMgr");

//web paths
define('WWW_ROOT', "http://localhost/ufl");
define('IMAGE_ROOT', WWW_ROOT."/images");
define('SOLECISMIC_ROOT', WWW_ROOT."/solecismic_test");

//ui paths
define('UI_ROOT', APP_ROOT . "/ui");


// database access parameters
$GLOBALS['_host'] = 'localhost';
$GLOBALS['_user'] = 'root';
$GLOBALS['_pass'] = '';
$GLOBALS['_db'] = 'test4dev';
//$GLOBALS['_db'] = 'ufl4real';


?> 