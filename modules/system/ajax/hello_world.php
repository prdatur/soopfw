<?php
/**
 * Provides an ajax request to print out hello world
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module System
 * @category Ajax
 */
class AjaxSystemHelloWorld extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		echo "hello world";
	}
}

