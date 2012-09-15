<?php
/**
 * Provide the an abstract class for creating cli commands (./clifs)
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 ** @category Cli
 */
abstract class CLICommand extends Object {

	/**
	 * Define all possible console message types
	 */
	const MESSAGE_TYPE_WARNING		= 'warning';
	const MESSAGE_TYPE_CANCEL		= 'cancel';
	const MESSAGE_TYPE_FAILED		= 'failed';
	const MESSAGE_TYPE_ERROR		= 'error';
	const MESSAGE_TYPE_OK			= 'ok';
	const MESSAGE_TYPE_COMPLETED	= 'completed';
	const MESSAGE_TYPE_SUCCESS		= 'success';
	const MESSAGE_TYPE_STATUS		= 'status';
	const MESSAGE_TYPE_NOTICE		= 'notice';
	const MESSAGE_TYPE_MESSAGE		= 'message';
	const MESSAGE_TYPE_INFO			= 'info';

    /**
     * The description
     * @var string
     */
    protected $description = "";

	/**
	 * The errors
	 * @var array
	 */
    private $errors = array();

	/**
     * This function must be set on extended class, this will be called if cli
     * command is executed
	 *
     * @return boolean should return true if no errors occured, else false
     */
    abstract public function execute();

	/**
	 * Starts the cli command (clifs)
	 */
    public function start() {
		ini_set('display_errors', 'on');
		error_reporting(E_ALL);
		restore_error_handler();
		//Run the command, check if the command returns true, if succeed call the on_success method, else display all errors and call on_error method
        if($this->execute()) {
            $this->on_success();
        }
        else {
			//Display all added errors.
            foreach($this->errors AS $array) {
                console_log($array['message'], $array['type']);
            }
            $this->on_error();
        }
    }

    /**
     * Get the command description for the help list
	 *
     * @return string the description
     */
    public function get_description() {
        return $this->description;
    }

    /*
     * Can be overriden, this will be called if the script returns with no errors
     */
    public function on_success() {}

    /*
     * Can be overriden, this will be called if the script returns with errors
     */
    public function on_error() {}

	/**
	 * Add a message
	 *
	 * @param string $message
	 *   The message to be displayed
	 * @param string $type
	 *   The message type, use one of CLICommand::MESSAGE_TYPE_* (optional, default = CLICommand::MESSAGE_TYPE_ERROR)
	 */
    public function error($message, $type = self::MESSAGE_TYPE_ERROR) {
        $this->errors[] = array(
            'message' => $message,
            'type' => $type
        );
    }
}
?>