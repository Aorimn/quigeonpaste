<?php


if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Default file for authentication. Only mark pastes as public or private.
 */
class Auth_classic implements Auth_basic
{
	/** Policies */
	protected $controls = array();

	/** Lock file */
	protected $lockfh;

	/** Array of IPs=>[visited_time1,visited_time2,...] */
	protected $users_tracker;

	/** Array of blocked IP addresses => when_blocked_timestamp */
	protected $blocked;

	/**
	 * Constructor to initialize things
	 */
	function __construct()
	{
		global $lang, $msg;

		$this->controls['public']  = $lang['auth_public'];
		$this->controls['private'] = $lang['auth_private'];
		$msg->addMessage('Classic auth constructed (' . $lang['auth_public'] .
			'|' . $lang['auth_private'] . ')', Msg::DEBUG);

		$this->authenticate();
	}

	/**
	 * Authenticate user according to IP address here
	 */
	public function authenticate()
	{
		global $conf, $msg;

		/* Say to everybody we're here */
		$this->lock();

		$content = '';
		$file    = $conf['auth_iptime_file'];

		/* Get the previous, the registered array of visits */
		if(is_readable($file))
			$content = file_get_contents($file);

		/* Initialize from the file or not */
		if(!empty($content))
			$this->users_tracker = unserialize($content);
		else
			$this->users_tracker = array();

		$ip   = $_SERVER['REMOTE_ADDR'];
		$time = time();

		/* Add an entry for this visit */
		if(empty($this->users_tracker[$ip]))
			$this->users_tracker[$ip]   = array($time);
		else
			$this->users_tracker[$ip][] = $time;

		/* Save the file */
		if(io_saveFile($file, serialize($this->users_tracker)) !== ERR_SUCCESS)
			$msg->addMessage('Cannot save the file "'.$file.'".', Msg::DEBUG);

		/* Don't forget to unlock */
		$this->unlock();
	}

	/**
	 * Return the fields
	 *
	 * @return Array of input fields (no need to include the submit button):
	 *  - [0] => 'id'    => ID of the first field
	 *           'name'  => name of this field
	 *           'type'  => type of this field
	 *           'value' => value of this field
	 *  - [1] => ...
	 * Return null if nothing is to be displayed
	 */
	public function getFormFields()
	{
		return null;
	}

	/**
	 * Authorize (or not) the access (view or addition) of pastes regarding the
	 * acl and the IP address
	 *
	 * @param $acl The control set when archiving
	 * @param $id The ID of the paste the user want to view
	 * @param $ns The namespace used for this paste
	 * @param $context Visibility for everyone if set - @see storage::get_lasts
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_DENIED
	 */
	public function authorize($acl, $id, $ns, $context = null)
	{
		if(!is_null($context))
			return ($acl === 'private') ? ERR_DENIED : ERR_SUCCESS;

		/*
		 * There's no real authentication mechanism to check here, but prevent
		 * pastes listing
		 */
		global $conf, $msg;

		/* Say to everybody we're here */
		$this->lock();

		$content = '';
		$file    = $conf['auth_block_file'];

		/* Get the users which are blocked */
		if(is_readable($file))
			$content = file_get_contents($file);

		if(empty($content))
			$this->blocked = array();
		else
			$this->blocked = unserialize($content);

		$ip   = $_SERVER['REMOTE_ADDR'];
		$time = time();

		/* If the user come from a whitelisted address */
		if(in_array($ip, $conf['auth_whitelist']))
		{
			$this->unlock();
			return ERR_SUCCESS;
		}

		/* If the user is already blocked */
		if(array_key_exists($ip, $this->blocked))
		{
			if($this->blocked[$ip] + $conf['auth_block_time'] > $time)
			{
				/* If the time isn't elapsed yet */
				$this->unlock();
				return ERR_DENIED;
			}
			else
			{
				/* If the user is no longer banned */
				unset($this->blocked[$ip]);
			}
		}

		$ts   = $this->users_tracker[$ip];
		$time = $time - $conf['auth_mean_time'];
		$max  = $conf['auth_nb_attempt'];
		$cpt  = 0;

		$i        = 0;
		$to_unset = array();

		/* Check whether to block or not the IP */
		foreach($ts as $onets)
		{
			if($time < $onets)
				$cpt++;
			else
				$to_unset[] = $i;

			$i++;
		}


		/* Clean old timestamps */
		foreach($to_unset as $i)
			unset($ts[$i]);

		/* Update the tracker array */
		$this->users_tracker[$ip] = $ts;


		/* If there's more attempts than authorized, block the IP */
		if($cpt >= $max)
			$this->blocked[$ip] = $time + $conf['auth_mean_time'];


		/* Save the timestamps file */
		if(io_saveFile($conf['auth_iptime_file'],
				serialize($this->users_tracker)) !== ERR_SUCCESS)
			$msg->addMessage('Cannot save the file "' .
				$conf['auth_iptime_file'] . '".', Msg::DEBUG);

		/* Save the blocked-IP file */
		if(io_saveFile($file, serialize($this->blocked)) !== ERR_SUCCESS)
			$msg->addMessage('Cannot save the file "'.$file.'".', Msg::DEBUG);


		/* Don't forget to unlock */
		$this->unlock();

		if(array_key_exists($ip, $this->blocked))
			return ERR_DENIED;

		return ERR_SUCCESS;
	}

	/**
	 * Check if the user is authenticated
	 *
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_ERROR
	 */
	public function isAuthenticated()
	{
		return ERR_ERROR;
	}

	/**
	 * If the user is authenticated, return its identifier
	 *
	 * @return Return the user's identifier if s/he's authenticated, null
	 * otherwise
	 */
	public function identifier()
	{
		return null;
	}

	/**
	 * Get an array describing ACLs to propose to the user
	 *
	 * @return Array composed of:
	 *  - [0] => 'multi' => boolean, set multi choices or not
	 *
	 *  - [1] => 'value' => value to set
	 *           'text'  => text to set
	 *           'title' => basic explication for each entry
	 *  - [2] => ... (same as 1, and other entries also)
	 *
	 * Note that 0 is used for configuration, others are used to display
	 * choices to the user
	 */
	public function getACLs()
	{
		$data = array();

		$data[0] = array('multi' => false);

		$data[1] = array(
			'value' => 'public',
			'text'  => $this->controls['public'],
			'title' => ''
		);
		$data[2] = array(
			'value' => 'private',
			'text'  => $this->controls['private'],
			'title' => ''
		);

		return $data;
	}

	/**
	 * Check if the given choice is within the ACL "range"
	 *
	 * @param $acl The ACL choice to evaluate
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNKNOWN
	 */
	public function checkACL($acl)
	{
		if($acl === 'public' || $acl === 'private')
			return ERR_SUCCESS;

		global $msg;
		$msg->addMessage('"'.$acl.'" is not a valid ACL.', Msg::DEBUG);

		return ERR_UNKNOWN;
	}

	/**
	 * Return an example of a valid namespace for the user
	 *
	 * @return A string containing a valid namespace
	 */
	public function getAuthorizedNS()
	{
		return array('example');
	}

	/**
	 * Ask for locking for operating on a file
	 *
	 * @return ERR_UNSUCCESSFUL or ERR_SUCCESS depending on the lock status
	 */
	protected function lock()
	{
		global $conf, $msg;

		$lockf = $conf['auth_lockfile'];

		$this->lockfh = @fopen($lockf, 'w', false);
		if($this->lockfh === false)
		{
			$this->lockfh = null;
			$msg->addMessage('Cannot open the lockfile.', Msg::DEBUG);
			return ERR_UNSUCCESSFUL;
		}

		if(@flock($this->lockfh, LOCK_EX) === false)
		{
			fclose($this->lockfh);
			$this->lockfh = null;
			$msg->addMessage('Cannot lock the lockfile.', Msg::DEBUG);
			return ERR_UNSUCCESSFUL;
		}

		return ERR_SUCCESS;
	}

	/**
	 * Release the lock
	 */
	protected function unlock()
	{
		if(!is_null($this->lockfh))
		{
			flock($this->lockfh, LOCK_UN);
			fclose($this->lockfh);
			$this->lockfh = null;
		}
	}
}

//Setup VIM: ex: ts=4 noet :
