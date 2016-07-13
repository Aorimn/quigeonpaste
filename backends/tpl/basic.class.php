<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Base class for storage. Inherit this class for all storage means.
 */
interface Tpl_basic
{
	/**
	 * Constructor to initialize things
	 */
	function __construct();

	/**
	 * Just give the hand to the template manager
	 */
	public function display();

	/**
	 * Display the title used between \<title\> tag
	 */
	public function displayTitle();

	/**
	 * Display the registered messages
	 */
	public function displayMessages();

	/**
	 * Display the login form or id
	 */
	public function displayLogin();

	/**
	 * Display the last public-visible pastes
	 */
	public function displayLastPastes();

	/**
	 * Display various information when the user is logged-in
	 */
	public function displayInfoWhenLoggedIn();

	/**
	 * Display the paste seen
	 */
	public function displayPaste();

	/**
	 * Display the form used to create a new paste
	 */
	public function displayForm();
}

//Setup VIM: ex: ts=4 noet :
