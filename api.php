<?php
/**
 * Separate application using only necessary files for api tasks.
 * Processes the query and calls Api object.
 *
 * @author Ireneusz Derbich <ireneusz.derbich at gmail.com>
 */
define( 'IN_FS', true );

require_once dirname( __FILE__ ) . '/includes/class.flyspray.php';
require_once dirname( __FILE__ ) . '/includes/constants.inc.php';
require_once dirname( __FILE__ ) . '/includes/class.api.php';
require_once dirname( __FILE__ ) . '/includes/class.gpc.php';
require_once dirname( __FILE__ ) . '/includes/class.user.php';
require_once dirname( __FILE__ ) . '/includes/class.database.php';
require_once dirname( __FILE__ ) . '/includes/i18n.inc.php';
require_once dirname( __FILE__ ) . '/includes/class.api_flyspray.php';

// global $db
$db = new Database();
$db->dbOpenFast( $conf[ 'database' ] );

// global $fs
$fs = new ApiFlyspray();

$sAction = Post::val( 'action' );

$Api = new Api( $fs );
if( isset( $sAction ) && method_exists( $Api, $sAction ) ){
	return $Api->$sAction();
}
