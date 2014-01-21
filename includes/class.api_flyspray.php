<?php

/**
 * Stripped down Flyspray class
 *
 * @author Ireneusz Derbich <ireneusz.derbich at gmail.com>
 */
class ApiFlyspray extends Flyspray{

	/**
	 * Constructor
	 * @global type $db
	 */
	public function __construct(){
		global $db;

		$res = $db->Query( 'SELECT pref_name, pref_value FROM {prefs}' );
		while( $row = $db->FetchRow( $res ) ){
			$this->prefs[ $row[ 'pref_name' ] ] = $row[ 'pref_value' ];
		}

		$this->setDefaultTimezone();
	}
}
