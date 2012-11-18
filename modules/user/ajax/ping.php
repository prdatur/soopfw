<?php
/**
 * Provides an ajax request to return a "ping".
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module User
 * @category Ajax
 */
class AjaxUserPing extends AjaxModul {

	/**
	 * This function will be executed after ajax file initializing
	 */
	public function run() {
		AjaxModul::return_code(AjaxModul::SUCCESS);
	}
}
