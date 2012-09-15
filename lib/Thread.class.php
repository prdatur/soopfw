<?php
/**
 * Implements threading in PHP
 *
 * @package <none>
 * @version 1.0.0 - stable
 * @author Tudor Barbu <miau@motane.lu>
 * @copyright MIT
 */
abstract class Thread {
    const FUNCTION_NOT_CALLABLE     = 10;
    const COULD_NOT_FORK            = 15;

    /**
     * possible errors
     *
     * @var array
     */
    private $errors = array(
        Thread::FUNCTION_NOT_CALLABLE   => 'You must specify a valid function name that can be called from the current scope.',
        Thread::COULD_NOT_FORK          => 'pcntl_fork() returned a status of -1. No new process was created',
    );

    /**
     * callback for the function that should
     * run as a separate thread
     *
     * @var callback
     */
    protected $runnable;

    /**
     * holds the current process id
     *
     * @var integer
     */
    private $pid;

    /**
     * checks if threading is supported by the current
     * PHP configuration
     *
     * @return boolean
     */
    public static function available() {
        $required_functions = array(
            'pcntl_fork',
        );

        foreach( $required_functions as $function ) {
            if ( !function_exists( $function ) ) {
                return false;
            }
        }

        return true;
    }

	/**
	 * This method must be implemented, this will called if the Thread is starting.
	 */
	abstract protected function run();

	/**
	 * sets the callback
	 *
	 * @param callback $runnable
	 *
	 * @throws Exception
	 * @return callback
	 */
    public function set_runnable( $runnable ) {
        if( self::runnable_ok( $runnable ) ) {
            $this->runnable = $runnable;
        }
        else {
            throw new Exception( $this->get_error( Thread::FUNCTION_NOT_CALLABLE ), Thread::FUNCTION_NOT_CALLABLE );
        }
    }

    /**
     * gets the callback
     *
     * @return callback
     */
    public function get_runnable() {
        return $this->runnable;
    }

    /**
     * checks if the callback is ok (the function/method
     * actually exists and is runnable from the current
     * context)
     *
     * can be called statically
     *
     * @param callback $runnable
     * @return boolean
     */
    public static function runnable_ok( $runnable ) {
        return ( function_exists( $runnable ) && is_callable( $runnable ) );
    }

    /**
     * returns the process id (pid) of the simulated thread
     *
     * @return int
     */
    public function get_pid() {
        return $this->pid;
    }

    /**
     * checks if the child thread is alive
     *
     * @return boolean
     */
    public function is_alive() {
        $pid = pcntl_waitpid( $this->pid, $status, WNOHANG );
        return ( $pid === 0 );

    }

    /**
     * starts the thread, all the parameters are
     * passed to the callback function
     *
     * @return void
     */
    public function start() {
        $pid = @ pcntl_fork();
        if( $pid == -1 ) {
            throw new Exception( $this->get_error( Thread::COULD_NOT_FORK ), Thread::COULD_NOT_FORK );
        }
        if( $pid ) {
            // parent
            $this->pid = $pid;
        }
        else {
            // child
            pcntl_signal( SIGTERM, array( $this, 'signal_handler' ) );
            $this->run();
            exit( 0 );
        }
    }

    /**
     * attempts to stop the thread
     * returns true on success and false otherwise
     *
     * @param integer $signal - SIGKILL/SIGTERM
     * @param boolean $wait
     */
    public function stop( $signal = SIGKILL, $wait = false ) {
        if( $this->is_alive() ) {
            posix_kill( $this->pid, $signal );
            if( $wait ) {
                pcntl_waitpid( $this->pid, $status = 0 );
            }
        }
    }

    /**
     * alias of stop();
     *
     * @return boolean
     */
    public function kill( $signal = SIGKILL, $wait = false ) {
        return $this->stop( $signal, $wait );
    }

    /**
     * gets the error's message based on
     * its id
     *
     * @param integer $code
     * @return string
     */
    public function get_error( $code ) {
        if ( isset( $this->errors[$code] ) ) {
            return $this->errors[$code];
        }
        else {
            return 'No such error code ' . $code . '! Quit inventing errors!!!';
        }
    }

    /**
     * signal handler
     *
     * @param integer $signal
     */
    protected function signal_handler( $signal ) {
        switch( $signal ) {
            case SIGTERM:
                exit( 0 );
            break;
        }
    }
}