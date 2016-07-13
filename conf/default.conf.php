<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/*
 * DO NOT EDIT!!!
 * As this file will be overwritten at each update, it's unnecessary and
 * stupid to change it.
 * Instead, create a file named `local.conf.php' in this folder. This last
 * file won't be overridden by any update so your parameters will be saved.
 */


/*
 * These are defaults values. If you inherit from them, use 'default,yourclass'
 * (for example), otherwise, just your class will be included.
 * Note that the basic.class.php (interfaces) will automatically be included.
 */
$conf['backends'] = QUIGON_ABS.'backends/'; // Place to find the three following backends
                                            // Beware of changing tpl/css.php's and tpl/js.php's abs path
$conf['tpl']      = 'default';              // Use the default template
$conf['storage']  = 'plain';                // Use the plain backend
$conf['auth']     = 'classic';              // Use the classic authentication for pastebins (public/private)


/*
 * Various default configuration options
 */
$conf['debug']       = false;           // Allow debugging or not (true/false)
$conf['lang']        = 'en';            // Language to use for the pastebin
$conf['title']       = 'QUIGEON Paste'; // The page title to use
$conf['dmode']       = 0750;            // Mode of the directories to create
$conf['fmode']       = 0740;            // Mode of the files to create
$conf['date_format'] = '%d %B %Y | %R'; // strftime() format
$conf['timezone']    = 'Europe/Paris';  // See date_default_timezone_set()
$conf['default_delay'] = '1d';          // Default expiration time (1h, 1d, 3d, 1w, 1m, 3m, 1y)

$conf['tabwidth']    = 4;               // Tab width for NOT hilighted text
$conf['hl_sign']     = '+++';           // The sign to put in front of a line for this one to be highlighted (will be removed from display)

$conf['id_length']   = 8;               // Length of generated IDs
$conf['hash']    = array('sha256', 64); // Algorithm to generate hash (see hash_algos()) and digest length
$conf['actions'] = array('show', 'add', 'delete', 'export_raw');
                                        // Allowed actions (there must be a file for each one into the actions/ directory)
$conf['default_act'] = 'show';          // Default action

//Setup VIM: ex: ts=4
