<?php

$VERSION = '0.1';


/* Define the root of this app */
if(!defined('QUIGON_ABS'))
	define('QUIGON_ABS', dirname(__FILE__) . DIRECTORY_SEPARATOR);


/* Define the root of this app according to the server */
$matches = array();
preg_match('#^.*/#', $_SERVER['SCRIPT_NAME'], $matches);
define('QUIGON_REL', $matches[0]);


/* Include all files, init and (begin) check of user's inputs */
require(QUIGON_ABS . 'inc' . DIRECTORY_SEPARATOR . 'init.php');

/* Define the path to the template folder, regarding the URL */
$urlrel = preg_replace('#^'.QUIGON_ABS.'(.*)$#', '\\1', $conf['backends']);
define('QUIGON_URLTPL', getUrl('', '') . $urlrel . 'tpl/');


/* Construct and check classes */
$auth_c  = "Auth_$auth";
$store_c = "Storage_$store";
$tpl_c   = "Tpl_$tpl";

$auth    = new $auth_c();
$storage = new $store_c();
$tpl     = new $tpl_c();

if(!($auth instanceof Auth_basic))
	die(sprintf($lang['doesnt_extend'], $auth_c, 'Auth_basic'));

if(!($storage instanceof Storage_basic))
	die(sprintf($lang['doesnt_extend'], $storage_c, 'Storage_basic'));

if(!($tpl instanceof Tpl_basic))
	die(sprintf($lang['doesnt_extend'], $tpl_c, 'Tpl_basic'));



/*
 * $ID, $NS and $ACT can be seen as correct, but dispatch need to clean
 * $TITLE, $CONTENT, $TYPE, $DELAY and $ACL according to $ACT
 */


/* Dispatch the action send, to be dealt with */
act_dispatch($ACT);


/* The template should be rendered and all, let's clean things here */
/* Don't waste user time and give it all to him */
flush();

$storage->clean_old();


//Setup VIM: ex: ts=4 noet :
