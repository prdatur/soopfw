<?php
/**
 * Memcache dummy class for memcached wrapper if memcache object does not exist. 
 */
if (!class_exists('memcache')) {
	class memcache {}
}

