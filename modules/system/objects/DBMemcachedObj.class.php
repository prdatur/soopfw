<?php

/**
 * This object represent a cache key
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.system.objects
 * @category ModelObjects
 */
class DBMemcachedObj extends AbstractDataManagment
{
	/**
	 * Define constances
	 */
	const TABLE = '__memcached';

	/**
	 * Constructor
	 * @param string $key 
	 *   the memcache key (optional, default = "")
	 * @param boolean $force_db 
	 *   if we want to force to load the data from the database (optional, default = false)
	 */
	public function __construct($key = '', $force_db = false) {
		parent::__construct();

		$this->db_struct = new DbStruct(self::TABLE);
		$this->db_struct->set_cache(true);
		$this->db_struct->add_reference_key('key');
		$this->db_struct->set_auto_increment('key');
		$this->db_struct->add_field("key", t('Memcached Key'), PDT_STRING, '');
		$this->db_struct->add_field("value", t('Memcached Value'), PDT_TEXT, '');
		$this->db_struct->add_field("expires", t('Memcached Expires'), PDT_INT, 0, 'UNSIGNED');
	}

}

