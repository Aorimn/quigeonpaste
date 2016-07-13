<?php

if(!defined('QUIGON_ABS'))
	die('meh.');


/*
 * DO NOT EDIT!!!
 * As this file will be overwritten at each update, it's unnecessary and
 * stupid to change it.
 * Instead, create a file named `conf/local.conf.php'. This last
 * file won't be overridden by any update so your parameters will be saved.
 */

$conf['auth_block_time']  = 600;                // Block a user 10 minutes
$conf['auth_nb_attempt']  = 10;                 // If he made 10 attempts to see pastes
$conf['auth_mean_time']   = 20;                 // Within 20 seconds
$conf['auth_whitelist']   = array('127.0.0.1'); // And is not on the white list

/* Various files... */
$data_base = QUIGON_ABS . 'data' . DIRECTORY_SEPARATOR;
$conf['auth_iptime_file'] = $data_base . 'users.auth'; // Log users views into this file
$conf['auth_block_file']  = $data_base . 'block.auth'; // Log blocked users into this one
$conf['auth_lockfile']    = $data_base . 'lock.auth';  // Lock file to know when we can put content into the two others


//Setup VIM: ex: ts=4 noet :
