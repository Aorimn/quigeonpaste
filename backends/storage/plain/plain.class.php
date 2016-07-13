<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Default class for storage. Using plain text files.
 */
class Storage_plain implements Storage_basic
{
	protected $lockfh;

	/**
	 * Constructor to initialize things
	 */
	function __construct()
	{
		global $conf, $msg;

		$msg->addMessage('Plain storage constructed.', Msg::DEBUG);

		if(substr($conf['storage_pastes'], -1) !== DIRECTORY_SEPARATOR)
			$conf['storage_pastes'] .= '/';
	}

	/**
	 * Method to store pastes
	 *
	 * @param $id Id of the new paste to store
	 * @param $data Array containing the following members, if that matters:
	 *  - title   => title of the paste
	 *  - content => content of the paste
	 *  - type    => content type, as for the geshi plugin to highlight things
	 *  - delay   => delay before expiration (timestamp in second)
	 *  - acl     => access defined by the auth used
	 *  - once    => true or false, whether the paste is readable only once
	 *  - owner   => the paste owner's identifier, or null
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNSUCCESSFUL
	 */
	public function store_value($id, $ns, $data)
	{
		global $msg;

		$data['id'] = $id;
		$data['ns'] = $ns;

		$content = serialize($data);
		$filename = $this->get_paste_filename($id, $ns);

		if(empty($filename))
		{
			return ERR_UNSUCCESSFUL;
		}

		if(io_saveFile($filename, $content) !== ERR_SUCCESS)
		{
			$msg->addMessage('Cannot save the file "'.$ns.DIRECTORY_SEPARATOR
				.$id.'".', Msg::DEBUG);
			return ERR_UNSUCCESSFUL;
		}

		$this->register_last($id, $ns, $data);

		return ERR_SUCCESS;
	}

	/**
	 * Method to get pastes
	 *
	 * @param $id Id of the paste to get
	 * @param $ns Paste namespace
	 * @param $out_data Array to be filled inside the function if the result
	 * is not an error. Keys are the ones described above, with the same
	 * signification, plus 'id' and 'ns' which respectively represents the ID of
	 * the paste and its namespace.
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_NOT_FOUND
	 *  - ERR_EXPIRED
	 */
	public function get_value($id, $ns, &$out_data)
	{
		global $msg;

		if(empty($id))
		{
			$msg->addMessage('Empty ID given, not looking for a file.',
				Msg::DEBUG);
			return ERR_NOT_FOUND;
		}

		$file = $this->get_paste_filename($id, $ns);
		if(file_exists($file) === FALSE)
		{
			$msg->addMessage('The file "' . $file . '" doesn\'t exist.',
				Msg::DEBUG);
			return ERR_NOT_FOUND;
		}


		$content = file_get_contents($file);
		if(empty($content))
			return ERR_NOT_FOUND;

		$out_data = unserialize($content);

		if(time() >= $out_data['delay'])
		{
			/* When the paste as expired, don't give it */
			$msg->addMessage("The paste of ID \"$ns/$id\" has expired since "
				. secondToText($out_data['delay']),
				Msg::DEBUG);

			io_delFile($file);
			$out_data = array();
			return ERR_EXPIRED;
		}

		return ERR_SUCCESS;
	}

	/**
	 * Method to remove a specific paste, callable from other backends
	 *
	 * @param $id Id of the paste to get
	 * @param $ns Paste namespace
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_NOT_FOUND
	 */
	public function delete_value($id, $ns)
	{
		global $conf, $msg;

		$file = $this->get_paste_filename($id, $ns);
		if(file_exists($file) === FALSE)
		{
			$msg->addMessage('The file "' . $file . '" doesn\'t exist.',
				Msg::DEBUG);
			return ERR_NOT_FOUND;
		}

		/* Remove the paste */
		io_delFile($file);
		/* Then remove the paste from the last pastes list */
		$this->unregister_last($id, $ns);

		return ERR_SUCCESS;
	}

	/**
	 * Method to get last pastes, which can be viewed by *anyone*
	 * (i.e.: ACL are checked)
	 *
	 * @return Array of last pastes (empty if nothing):
	 *  - [0] => link  => direct link to the paste
	 *           name  => name of the paste (= title)
	 *           delay => the date when the paste will expire (in seconds since
	 * Unix Epoch)
	 *  - [1] => ...
	 */
	public function get_lasts()
	{
		global $auth, $conf;

		$ret = array();

		if($this->lock() === ERR_UNSUCCESSFUL)
			return $ret;

		$lastf = $conf['storage_last_file'];
		if(file_exists($lastf))
		{
			$content = file_get_contents($lastf);
			if(!empty($content))
			{
				$lasts = unserialize($content);
				if($lasts !== false && is_array($lasts))
				{
					$max = $conf['storage_nb_last'];
					$cnt = 0;
					$curr_time = time();

					foreach($lasts as $l)
					{
						$id = $l['id'];
						$ns = $l['ns'];
						$acl = $l['acl'];
						$type = 'visible for all';

						/* If the paste has expired, do not show it */
						if($curr_time >= $l['delay'])
							continue;

						if($auth->authorize($acl, $id, $ns, $type) === ERR_SUCCESS)
						{
							$ret[] = array(
								'link'  => getUrl($id, $ns),
								'name'  => $l['title'],
								'delay' => $l['delay']
							);
							$cnt++;

							if($cnt >= $max)
								break;
						}
					}
				}
			}
		}

		$this->unlock();
		return $ret;
	}

	/**
	 * Method to register the last pastes into a file
	 *
	 * @param $id The paste ID
	 * @param $ns The paste namespace (possibly blank)
	 * @param $data Data passed to store_value()
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNSUCCESSFUL
	 */
	protected function register_last($id, $ns, $data)
	{
		global $conf, $msg;

		$lastf = $conf['storage_last_file'];
		$last_data = array(
			'id'       => $id,
			'ns'       => $ns,
			'delay'    => $data['delay'],
			'acl'      => $data['acl'],
			'title'    => $data['title']
		);

		/*
		 * As we're going to read-then-write, lock the file so that no other job
		 * is going to do this read-then-write between our read and write.
		 */
		if($this->lock() === ERR_UNSUCCESSFUL)
			return ERR_UNSUCCESSFUL;

		$content = '';
		if(is_readable($lastf))
			$content = file_get_contents($lastf);

		$lasts = array();
		if(!empty($content))
			$lasts = unserialize($content);

		$lasts[] = $last_data;

		usort($lasts, function($a, $b) {
			return ($a['delay'] < $b['delay']) ? -1 : 1;
		});

		$ret = ERR_SUCCESS;
		if(io_saveFile($lastf, serialize($lasts)) !== ERR_SUCCESS)
		{
			$msg->addMessage('Cannot save the lastfile "'.$lastf.'".',
				Msg::DEBUG);
			$ret = ERR_UNSUCCESSFUL;
		}

		$this->unlock();
		return $ret;
	}

	/**
	 * Method to unregister one of the last pastes
	 *
	 * @param $id The paste ID
	 * @param $ns The paste namespace (possibly blank)
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNSUCCESSFUL
	 */
	protected function unregister_last($id, $ns)
	{
		global $conf, $msg;

		$lastf = $conf['storage_last_file'];


		/*
		 * As we're going to read-then-write, lock the file so that no other job
		 * is going to do this read-then-write between our read and write.
		 */
		if($this->lock() === ERR_UNSUCCESSFUL)
			return ERR_UNSUCCESSFUL;

		$content = '';
		if(is_readable($lastf))
			$content = file_get_contents($lastf);

		$lasts = array();
		if(!empty($content))
			$lasts = unserialize($content);

		foreach($lasts as $i => $l)
		{
			if($l['id'] === $id && $l['ns'] === $ns)
			{
				unset($lasts[$i]);
			}
		}

		$ret = ERR_SUCCESS;
		if(io_saveFile($lastf, serialize($lasts)) !== ERR_SUCCESS)
		{
			$msg->addMessage('Cannot save the lastfile "'.$lastf.'".',
				Msg::DEBUG);
			$ret = ERR_UNSUCCESSFUL;
		}

		$this->unlock();
		return $ret;
	}


	/**
	 * Ask for locking for operating on a file
	 *
	 * @return ERR_UNSUCCESSFUL or ERR_SUCCESS depending on the lock status
	 */
	protected function lock()
	{
		global $conf, $msg;

		$lockf = $conf['storage_lock_file'];

		$this->lockfh = fopen($lockf, 'w', false);
		if($this->lockfh === false)
		{
			$msg->addMessage('Cannot open the lockfile.', Msg::DEBUG);
			return ERR_UNSUCCESSFUL;
		}

		if(flock($this->lockfh, LOCK_EX) === false)
		{
			fclose($this->lockfh);
			$msg->addMessage('Cannot lock the lockfile.', Msg::DEBUG);
			return ERR_UNSUCCESSFUL;
		}

		return ERR_SUCCESS;
	}

	/**
	 * Get the paste's filename
	 */
	protected function get_paste_filename($id, $ns)
	{
		global $conf, $msg;

		$dir = $conf['storage_pastes'] . $ns . DIRECTORY_SEPARATOR;

		if(io_createDir($dir) !== ERR_SUCCESS)
		{
			$msg->addMessage('Cannot create the directory for the given '
				. 'namespace: "' . $ns . '".', Msg::DEBUG);
			return '';
		}

		return $dir . $id . '.txt';
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


	/**
	 * Method to clean storage from old pastes
	 */
	public function clean_old()
	{
		global $conf;

		if($this->lock() === ERR_UNSUCCESSFUL)
			return;

		$lastf = $conf['storage_last_file'];
		if(file_exists($lastf))
		{
			$content = file_get_contents($lastf);
			if(!empty($content))
			{
				$lasts = unserialize($content);
				if($lasts !== false && is_array($lasts))
				{
					foreach($lasts as $i => $l)
					{
						$id = $l['id'];
						$ns = $l['ns'];

						if(time() >= $l['delay'])
						{
							/* When the paste as expired, remove the file */
							$file = $this->get_paste_filename($id, $ns);

							if(file_exists($file))
								io_delFile($file);

							/* And remove it from the last pastes list */
							unset($lasts[$i]);
						}
					}
				}
				else
				{
					$lasts = array();
				}

				if(io_saveFile($lastf, serialize($lasts)) !== ERR_SUCCESS)
				{
					$msg->addMessage('Cannot save the lastfile "'.$lastf.'".',
						Msg::DEBUG);
				}
			}
		}

		$this->unlock();
		return;
	}
}

//Setup VIM: ex: ts=4 noet :
