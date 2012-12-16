<?php

/**
 * Provides a class to lock a user for a specific action directly or if the user did the same action to much.
 * Will place memcached values to check the locks, so this IS NOT permantly stored.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Security
 */
class SecurityLock extends Object
{

	/**
	 * The global lock identifier.
	 * This will be used if within the other methods not lock_identifier is provided.
	 *
	 * @var string
	 */
	private $lock_identifier = '';

	/**
	 * Constructor.
	 *
	 * @param string $lock_identifier
	 *   The lock identifier. (optional, default = NS)
	 */
	public function __construct($lock_identifier = NS) {
		parent::__construct();

		$this->lock_identifier = $lock_identifier;
	}
	/**
	 * Lock a lock identifier directly.
	 *
	 * This lock will NOT expire.
	 * Use SecurityLock->unlock() to unlock it.
	 *
	 * @param string $lock_identifier
	 *   This is the lock identifer for the action which is monitored.
	 *   If not provided the global one which was setup within the constructor is used.
	 *   For example: user_login_count which locks user identified by $user_identifer if the wrong login count exceeds.
	 *   (optional, default = NS)
	 * @param string $user_identifer
	 *   The user identifier.
	 *   (optional, default = NS)
	 *
	 * @return boolean true on success, else false
	 */
	public function lock($lock_identifier = NS, $user_identifer = NS) {

		// Get the user identification.
		if (($user_identifer = $this->get_user_identification($user_identifer)) == false) {
			return false;
		}

		// Set the direct lock entry.
		$this->core->memcache_obj->set($this->generate_memcache_key($lock_identifier, $user_identifer), array('count' => 'DIRECTLOCK'));

		// Return if the set action was successfully.
		return ($this->core->memcache_obj->get_result_code() == CacheProvider::RES_SUCCESS);
	}

	/**
	 * Get the current lock value.
	 *
	 * @param string $lock_identifier
	 *   This is the lock identifer for the action which is monitored.
	 *   If not provided the global one which was setup within the constructor is used.
	 *   For example: user_login_count which locks user identified by $user_identifer if the wrong login count exceeds.
	 *   (optional, default = NS)
	 * @param string $user_identifer
	 *   The user identifier.
	 *   (optional, default = NS)
	 *
	 * @return int the current lock count.
	 */
	public function get_lock_value($lock_identifier = NS, $user_identifer = NS) {
		// Get the user identification.
		if (($user_identifer = $this->get_user_identification($user_identifer)) === false) {
			return false;
		}
		$data = $this->core->memcache_obj->get($this->generate_memcache_key($lock_identifier, $user_identifer));
		if (!isset($data['count'])) {
			return 0;
		}

		return $data['count'];
	}


	/**
	 * Unlock a user.
	 *
	 * @param string $lock_identifier
	 *   This is the lock identifer for the action which is monitored.
	 *   If not provided the global one which was setup within the constructor is used.
	 *   For example: user_login_count which locks user identified by $user_identifer if the wrong login count exceeds.
	 *   (optional, default = NS)
	 * @param string $user_identifer
	 *   The user identifier.
	 *   (optional, default = NS)
	 *
	 * @return boolean on success true , else false
	 */
	public function unlock($lock_identifier = NS, $user_identifer = NS) {

		// Get the user identification.
		if (($user_identifer = $this->get_user_identification($user_identifer)) === false) {
			return false;
		}

		// Delete the memcache entry.
		$this->core->memcache_obj->delete($this->generate_memcache_key($lock_identifier, $user_identifer));

		// Return if the delete action was successfully.
		return ($this->core->memcache_obj->get_result_code() == CacheProvider::RES_SUCCESS);
	}

	/**
	 * Check if the given user is locked for the given lock action.
	 *
	 * This will check if the action was executed more than configurated $max_actions WITHIN the configurated $block_range.
	 * After $block_range seconds a new "round" will start, old counters for this $lock_identifier are resetted.
	 *
	 * @param int $max_actions
	 *   How much actions are allowed within $max_check_time.
	 *   (optional, default = 3)
	 * @param int $block_range
	 *   The seconds when the lock will be resetted.
	 *   A timestamp is NOT the correct value, only provide the direct seconds, the current timestamp will be added.
	 *   (optional, default = DateTools::TIME_DAY)
	 * @param int $block_time
	 *   The seconds how long the user will get blocked.
	 * @param string $lock_identifier
	 *   This is the lock identifer for the action which is monitored.
	 *   If not provided the global one which was setup within the constructor is used.
	 *   For example: user_login_count which locks user identified by $user_identifer if the wrong login count exceeds.
	 *   (optional, default = NS)
	 * @param boolean $update_expire_data
	 *   If set to true the expire date will always be updated on call so the user need to wait the FULL expire time else he will locked again for the
	 *   specified $block_time.
	 *   Example value set to true (default max actions, $block_range = 4 (4 seconds), $block_time = 8):
	 *      User try to login 4 times with wrong credentials within 3 seconds => lock set (user is locked)
	 *      User waits 5 seconds and tries again with wrong credentials => lock updated (user is locked)
	 *      User waits 5 seconds and tries again with wrong credentials => lock updated (user is still locked)
	 *      User waits 8 seconds and tries again with wrong credentials => lock is released, a new $max_action counter has started (user is unlocked)
	 *   If set to false the user is only locked for the given $block_time no matter how often he tries the same bad action again within the lock time.
	 *   (optional, default = true)
	 * @param string $user_identifer
	 *   The user identifier.
	 *   (optional, default = NS)
	 *
	 * @return boolean returns true if the user is locked, else false
	 */
	public function check_lock_within_time_range($max_actions = 3, $block_range = DateTools::TIME_DAY, $block_time = DateTools::TIME_MINUTE_15, $lock_identifier = NS, $update_expire_data = true, $user_identifer = NS) {
		// Get the current lock count if available.
		$current_count = $this->core->memcache_obj->get($this->generate_memcache_key($lock_identifier, $user_identifer));

		// Initialize the lock data if needed.
		if (empty($current_count) || !is_array($current_count)) {
			$current_count = array(
				'time' => TIME_NOW,
				'count' => 0,
			);
		}

		// Check if the user is directly locked for this lock identifer.
		if ($current_count['count'] === 'DIRECTLOCK') {
			return false;
		}

		// Incerement the lock count.
		$current_count['count']++;

		// Determine if locked, because we need to do different things for the time entry and expire data.
		$locked = ($current_count['count'] > $max_actions);

		// The value within the 'time' key is our expire date if we are within range mode, we need to define a new variable because in none range mode
		// we have to set the expire time to 0 (unlimited) if we directly change the value on the 'time' key we override the stored value because we set the
		// $current_count variable as the memcached value.
		$expires = $current_count['time'];

		// Check if we are currently locked.
		if ($locked === true) {
			// Update lock time if we want it but only if it is already locked, else we break the block_range rule.
			if ($update_expire_data === true) {

				// We set the time "where the user was blocked" to the current one to increment the lock each time the user call the locked action again.
				$expires = TIME_NOW;
			}

			// Add the block time.
			// If we do not update the expire data, we have the timestamp after the first block stored into $expires, we then need to add
			// the block time, so the memcached key will expire on the time where the user was blocked + the block time we want.
			$expires += $block_time;

			// If we locked the user right now log this lock.
			if ($current_count['count'] == $max_actions + 1) {
				SystemHelper::audit(
					'A user (' . $this->get_user_identification($user_identifer)  . ') was locked (lock action: ' . $this->get_lock_identifier($lock_identifier) . ')' ,
					'security',
					SystemLogObj::LEVEL_ALERT
				);
			}
		}
		else {
			// We are currently not locked.

			// If we do not use the block range, we have an "unlimited" expire time.
			if ($block_range === 0) {
				$expires = 0;
				// We need to set the value for 'time' key to the current time else we would set the expire date to the time where the user first call the action
				// + block time and this is in most cases in the past of the time where we really block the user + block time
				$current_count['time'] = TIME_NOW;
			}
			else {
				// If we are not blocked we need to expire on the block range instead of block time, because we do not want to release a lock instead
				// we want to release the lock counter only.
				$expires += $block_range;
			}
		}

		// Set the check values.
		$this->core->memcache_obj->set($this->generate_memcache_key($lock_identifier, $user_identifer), $current_count, $expires);

		// Return if the user is locked or not.
		return !$locked;
	}

	/**
	 * Check if the given user is locked for the given lock action.
	 *
	 * This will check if the action was executed more than configurated $max_actions no matter how long he waits between the actions.
	 * Because of this a good unlock policy is mandatory, else false positive are possible.
	 *
	 * @param int $max_actions
	 *   How much actions are allowed within $max_check_time.
	 *   (optional, default = 3)
	 * @param int $block_time
	 *   The seconds how long the user will get blocked.
	 * @param string $lock_identifier
	 *   This is the lock identifer for the action which is monitored.
	 *   If not provided the global one which was setup within the constructor is used.
	 *   For example: user_login_count which locks user identified by $user_identifer if the wrong login count exceeds.
	 *   (optional, default = NS)
	 * @param boolean $update_expire_data
	 *   If set to true the expire date will always be updated on call so the user need to wait the FULL expire time else he will locked again for the
	 *   specified $block_time.
	 *   Example value set to true (default max actions, $block_time = 8):
	 *      User try to login 4 times with wrong credentials => lock set (user is locked)
	 *      User waits 5 seconds and tries again with wrong credentials => lock updated (user is locked)
	 *      User waits 5 seconds and tries again with wrong credentials => lock updated (user is still locked)
	 *      User waits 8 seconds and tries again with wrong credentials => lock is released, a new $max_action counter has started (user is unlocked)
	 *   If set to false the user is only locked for the given $block_time no matter how often he tries the same bad action again within the lock time.
	 *   (optional, default = true)
	 * @param string $user_identifer
	 *   The user identifier.
	 *   (optional, default = NS)
	 *
	 * @return boolean returns true if the user is locked, else false
	 */
	public function check_lock($max_actions = 3, $block_time = DateTools::TIME_MINUTE_15, $lock_identifier = NS, $update_expire_data = true, $user_identifer = NS) {
		return $this->check_lock_within_time_range($max_actions, 0, $block_time, $lock_identifier, $update_expire_data, $user_identifer);
	}

	/**
	 * Returns the memcached key for given $lock_identifcation.
	 *
	 * @param string $lock_identifier
	 *   This is the lock identifer for the action which is monitored.
	 *   If not provided the global one which was setup within the constructor is used.
	 *   For example: user_login_count which locks user identified by $user_identifer if the wrong login count exceeds.
	 *   (optional, default = NS)
	 * @param string $user_identifer
	 *   The user identifier.
	 *   (optional, default = NS)
	 *
	 * @return string|boolean The memcache key on succees, on error return false.
	 */
	private function generate_memcache_key($lock_identifier = NS, $user_identifer = NS) {

		// Get the user identification, if we can not determine a user this is in most cases a bad person (user wihout an ip address are not good) so directly return
		// that this user is locked.
		if (($user_identifer = $this->get_user_identification($user_identifer)) === false) {
			return false;
		}

		// Return the memcached key.
		return 'securitylockcheck:' . $this->get_lock_identifier($lock_identifier) . ':' . $user_identifer;
	}

	/**
	 * Returns the lock identification.
	 *
	 * @param string $lock_identifier
	 *   If provide this identification will be used.
	 *   If not provided it will use the global configurated one.
	 *   (optional, default = NS)
	 * @return string The lock identification.
	 */
	private function get_lock_identifier($lock_identifier = NS) {
		// If we did not provide a lock identifer use the global one.
		if (empty($lock_identifier) || $lock_identifier === NS) {
			$lock_identifier = $this->lock_identifier;
		}

		// Return the lock identifier.
		return $lock_identifier;
	}

	/**
	 * Returns the lock identification on which the lock belongs (user identifcation).
	 *
	 * @param string $user_identifer
	 *   The user identifier.
	 *   (optional, default = NS)
	 *
	 * @return string|boolean Returns the $lock_identifcation if it is set,
	 *   if $lock_identifcation was NS it will get a user identification value,
	 *   if $lock_identification is empty it returns boolean false.
	 */
	private function get_user_identification($user_identifer = NS) {
		// If we did not provide a direct user identification get the normal one.
		if ($user_identifer == NS) {
			$user_identifer = NetTools::get_user_identification();
		}

		// If the user identification is empty we have a problem so return false.
		if (empty($user_identifer)) {
			return false;
		}

		// Return the user identification
		return $user_identifer;
	}

}