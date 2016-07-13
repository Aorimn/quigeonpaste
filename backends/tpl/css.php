<?php


if(!defined('QUIGON_ABS'))
	define('QUIGON_ABS', dirname(__FILE__) . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);



$matches = array();
preg_match('#^(.*/).*/#', $_SERVER['SCRIPT_NAME'], $matches);
define('QUIGON_REL', $matches[1]);


require(QUIGON_ABS.'inc/init.php');

define('QUIGON_CSS', QUIGON_REL.'tpl/'.$conf['tpl'].'/');


header('Content-Type: text/css; charset=utf-8');

out();


/* ------------------------------------------------------------------------- */

/**
 * Basic function to get and display CSS files
 */
function out()
{
	global $conf;
	
	$css = file_get_contents(QUIGON_TPL.'style.css');
	
	/* replace placeholders */
	$css = preg_replace('#@@QUIGON_CSS@@#', QUIGON_CSS, $css);
	
	if($conf['debug'])
		echo $css;
	else
		echo css_compress($css);
}


/**
 * Very simple CSS optimizer
 * 
 * @param $css The CSS to optimize
 * @return The CSS optimized
 */
function css_compress($css)
{
	/* strip comments */
	$css = preg_replace('#/\*.*?\*/#s', '', $css);
	
	/* replace double (or more) slashes by one */
	$css = preg_replace('#//+#','/',$css);
	
	/* strip whitespaces */
	$css = preg_replace('![\r\n\t ]+!', ' ', $css);
	$css = preg_replace('/ ?([;,{}\/]) ?/', '\\1', $css);
	$css = preg_replace('/ ?: /',':', $css);
	
	/* shorten colors */
	$css = preg_replace("/#([0-9a-fA-F]{1})\\1([0-9a-fA-F]{1})\\2([0-9a-fA-F]{1})\\3/", "#\\1\\2\\3",$css);
	
	return $css;
}



//Setup VIM: ex: ts=4 noet :