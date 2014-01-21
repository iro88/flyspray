<?php

/**
 * Api class contains methods to access from outside the main application.
 *
 * @author Ireneusz Derbich <ireneusz.derbich at gmail.com>
 */
class Api{

	/**
	 * Flyspray object
	 * @var ApiFlyspray
	 */
	protected $Flyspray;

	/**
	 * Config
	 * @var array
	 */
	protected $aConf;

	/**
	 * Database object
	 * @var Database
	 */
	protected $DB;

	public function __construct( $fs ){
		$this->Flyspray = $fs;
		$this->DB = new Database();

		$this->init();
	}

	protected function init(){
		$this->aConf = parse_ini_file( Flyspray::get_config_path(), true );
		$this->DB->dbOpenFast( $this->aConf[ 'database' ] );

		$this->sessionStart();
	}

	/**
	 * Starts / regenerate session
	 */
	protected function sessionStart(){
		/**
		 * @todo Wszystkie zabezpieczenia, ustalenie czasu wygaśnięcia itp.
		 */
		session_start();
		if( !isset( $_SESSION[ 'api_userIsLogin' ] ) ){
			session_regenerate_id();
			$_SESSION[ 'api_userIsLogin' ] = false;
			$_SESSION[ 'api_userIp' ] = $_SERVER[ 'REMOTE_ADDR' ];
		}

		// theft attempt the session
		if( $_SESSION[ 'api_userIp' ] !== $_SERVER[ 'REMOTE_ADDR' ] ){
			session_destroy();
			$this->sessionStart();
		}
	}

	/**
	 * Is Account is authenticated
	 * @return boolean
	 */
	protected function isLogin(){
		return $_SESSION[ 'api_userIsLogin' ];
	}

	/**
	 * Render response
	 *
	 * @param string $sStatus
	 * @param array $aResponse
	 * @return string
	 */
	protected function response( $sStatus = '', $aResponse = array() ){
		$sJSON = json_encode( array(
			'status' => $sStatus,
			'data' => $aResponse )
		);

		ob_start();
		echo $sJSON;
		ob_end_flush();
	}

	/**
	 * Login to application
	 * Post val: ['username', 'password']
	 * 
	 * @return string
	 */
	public function login(){
		$aResponse = array();
		$iUserId = Flyspray::checkLogin( Post::val( 'username' ), Post::val( 'password' ) );
		if( $iUserId > 0 ){
			$User = new User( $iUserId );
			$_SESSION[ 'api_userIsLogin' ] = true;
			$_SESSION[ 'api_userId' ] = $User->id;

			// If the user had previously requested a password change, remove the magic url
			$this->DB->Query( "UPDATE {users} SET magic_url = '' WHERE user_id = ?", array( $User->id ) );
			// If active login attempts, reset them
			if( $User->infos[ 'login_attempts' ] > 0 ){
				$this->DB->Query( 'UPDATE {users} SET login_attempts = 0 WHERE user_id = ?', array( $User->id ) );
			}

			$aResponse[ 'message' ] = L( 'loginsuccessful' );
			return $this->response( 'success', $aResponse );
		}

		switch( $iUserId ){
			case 0:
				$this->DB->Query( 'UPDATE {users} SET login_attempts = login_attempts+1 WHERE account_enabled = 1 AND user_name = ?', array( $aData[ 'username' ] ) );
				$this->DB->Query( 'UPDATE {users} SET lock_until = ?, account_enabled = 0 WHERE login_attempts > ? AND user_name = ?', array( time() + 60 * $this->Flyspray->prefs[ 'lock_for' ], LOGIN_ATTEMPTS, $aData[ 'username' ] ) );
				if( $this->DB->AffectedRows() ){
					$aResponse[ 'message' ] = sprintf( L( 'error71' ), $this->Flyspray->prefs[ 'lock_for' ] ); // lock account for
					break;
				}
				$aResponse[ 'message' ] = L( 'error7' ); // invalid password
				break;
			case -1:
				$aResponse[ 'message' ] = L( 'error23' ); // not active
				break;
			case -2:
				$aResponse[ 'message' ] = L( 'usernotexist' ); // not exist
				break;
		}

		return $this->response( 'error', $aResponse );
	}

	/**
	 * Logout
	 *
	 * @return string
	 */
	public function logout(){
		session_destroy();
		return $this->response( 'success' );
	}

	public function listProjects(){
		$aResponse = Flyspray::listProjects( Post::val( 'activeonly', true ) );
		return $this->response( 'success', $aResponse );
	}
}
