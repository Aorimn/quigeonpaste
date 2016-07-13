<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Basic class to register messages to be displayed in the template
 */
class Msg
{
	/** Default registered levels */
	const DEBUG   = -1;
	const ERROR   = 0;
	const WARNING = 1;
	const INFO    = 2;
	const SUCCESS = 3;
	
	/** Registered levels */
	private $levels   = array();
	
	/** Registered messages */
	private $messages = array();
	
	
	/**
	 * Constructor to initialize things
	 */
	function __construct()
	{
		$this->messages[self::DEBUG]   = array();
		$this->messages[self::ERROR]   = array();
		$this->messages[self::WARNING] = array();
		$this->messages[self::INFO]    = array();
		$this->messages[self::SUCCESS] = array();
		
		$this->levels = array(
			self::DEBUG,
			self::ERROR,
			self::WARNING,
			self::INFO,
			self::SUCCESS
		);
		
		if(!empty($_SESSION['MSG_STATUS'])
				&& is_array($_SESSION['MSG_STATUS']))
		{
			$this->messages = $_SESSION['MSG_STATUS'];
			unset($_SESSION['MSG_STATUS']);
		}
	}
	
	/**
	 * Add a message to a specific level
	 * 
	 * @param $message The message to store
	 * @param $level (optional) The level to push the message into
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_NOT_FOUND
	 */
	public function addMessage($message, $level = self::INFO)
	{
		if($this->levelIsValid($level) === ERR_SUCCESS)
			$this->messages[$level][] = $message;
		else
			return ERR_NOT_FOUND;
		
		return ERR_SUCCESS;
	}
	
	/**
	 * Get the messages for a special level, or for all levels if no level is
	 * given
	 * 
	 * @param $level (optional) The level to get messages
	 * @param $clear (optional) Do we clear messages afterward?
	 * @return Array containing messages for the level, or array of arrays for
	 * all levels
	 */
	public function getMessages($level = null, $clear = null)
	{
		$tab = array();
		
		if(is_null($level))
			$tab = $this->messages;
		else
		{
			if($this->levelIsValid($level) === ERR_SUCCESS)
				$tab = $this->messages[$level];
			else
				$tab = $this->messages;
		}
		
		if(!is_null($clear))
			$this->clear($level);
		
		return $tab;
	}
	
	/**
	 * Add a non already existant level
	 * 
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNSUCCESSFUL
	 */
	public function addLevel($level)
	{
		if(in_array($level, $this->levels))
			return ERR_UNSUCCESSFUL;
		
		$this->levels[$level] = array();
		return ERR_SUCCESS;
	}
	
	/**
	 * Check whether a level is valid, exists into this class
	 * 
	 * @param $level The level which has to be checked
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_NOT_IN_RANGE
	 */
	protected function levelIsValid($level)
	{
		if(in_array($level, $this->levels))
			return ERR_SUCCESS;
		
		return ERR_NOT_IN_RANGE;
	}
	
	/**
	 * Clear all messages, or all of a level if $level is set
	 * 
	 * @param $level (optional) The level to clear
	 */
	public function clear($level = null)
	{
		if(is_null($level))
		{
			foreach($this->messages as &$a)
				$a = array();
		}
		else
		{
			if($this->levelIsValid($level))
				$this->messages[$level] = array();
		}
	}
}

//Setup VIM: ex: ts=4 noet :