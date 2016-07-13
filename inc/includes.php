<?php

/**
 * Just to include config files in good order
 */

if(!defined('QUIGON_INC'))
	die('meh.');


$sep = DIRECTORY_SEPARATOR;


/*
 * Include configuration
 */

/* Include the default configuration */
require(QUIGON_CONF . 'default.conf.php');

/* Include the user configuration (for the backends to use, essentially) */
if(is_readable(QUIGON_CONF . 'local.conf.php'))
	require(QUIGON_CONF . 'local.conf.php');

/* Include languages files */
require(QUIGON_INC.'lang'.$sep.'fr'.$sep.'lang.php');
if($conf['lang'] !== 'fr')
	require(QUIGON_INC.'lang'.$sep.$conf['lang'].$sep.'lang.php');



/*
 * Include miscs files
 */
require(QUIGON_INC.'actions.php');
require(QUIGON_INC.'errors.php');
require(QUIGON_ABS.'geshi'.$sep.'geshi.php');
require(QUIGON_INC.'io.php');
require(QUIGON_INC.'utils.php');

require(QUIGON_INC.'msg.class.php');



/*
 * Include classes (also using configuration)
 */
$base  = $conf['backends'].'storage'.$sep;
$store = (!empty($conf['storage']) ? $conf['storage'] : 'plain');
require($base.'basic.class.php');
if(strpos($store, ',') !== false)
{
	$includes = explode(',', $store);
	$store = array_pop($includes);
	foreach($includes as $tmp)
	{
		 require($base.$tmp.$sep.$tmp.'.class.php');
		@include($base.$tmp.$sep.$conf['lang'].$sep.'lang.php');
		@include($base.$tmp.$sep.'conf.php');
	}
}
 require($base.$store.$sep.$store.'.class.php');
@include($base.$store.$sep.$conf['lang'].$sep.'lang.php');
@include($base.$store.$sep.'conf.php');


$base = $conf['backends'].'auth'.$sep;
$auth = (!empty($conf['auth']) ? $conf['auth'] : 'classic');
require($base.'basic.class.php');
if(strpos($auth, ',') !== false)
{
	$includes = explode(',', $auth);
	$auth = array_pop($includes);
	foreach($includes as $tmp)
	{
		 require($base.$tmp.$sep.$tmp.'.class.php');
		@include($base.$tmp.$sep.$conf['lang'].$sep.'lang.php');
		@include($base.$tmp.$sep.'conf.php');
	}
}
 require($base.$auth.$sep.$auth.'.class.php');
@include($base.$auth.$sep.$conf['lang'].$sep.'lang.php');
@include($base.$auth.$sep.'conf.php');


$base = $conf['backends'].'tpl'.$sep;
$tpl  = (!empty($conf['tpl']) ? $conf['tpl'] : 'default');
require($base.'basic.class.php');
if(strpos($tpl, ',') !== false)
{
	$includes = explode(',', $tpl);
	$tpl = array_pop($includes);
	foreach($includes as $tmp)
	{
		 require($base.$tmp.$sep.$tmp.'.class.php');
		@include($base.$tmp.$sep.$conf['lang'].$sep.'lang.php');
		@include($base.$tmp.$sep.'conf.php');
	}
}
 require($base.$tpl.$sep.$tpl.'.class.php');
@include($base.$tpl.$sep.$conf['lang'].$sep.'lang.php');
@include($base.$tpl.$sep.'conf.php');



/* Re-include the user configuration to override module's configuration */
if(is_readable(QUIGON_CONF . 'local.conf.php'))
	require(QUIGON_CONF . 'local.conf.php');



//Setup VIM: ex: ts=4 noet :
