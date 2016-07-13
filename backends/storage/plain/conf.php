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


/* How many last pastes you want to be shown, at max */
$conf['storage_nb_last']   = 10;

/* In which directory we'll store things */
$conf['storage_data']      = QUIGON_ABS . 'data' . DIRECTORY_SEPARATOR;

/* In which directory we'll store pastes */
$conf['storage_pastes'] = $conf['storage_data'] . 'pastes/';

/* Where has to put the file for last pastes */
$conf['storage_last_file'] = $conf['storage_data'] . 'lasts.txt';

/* Needed for locking $conf['storage_last_file'] */
$conf['storage_lock_file'] = $conf['storage_data'] . 'lock.storage';


//Setup VIM: ex: ts=4 noet :
