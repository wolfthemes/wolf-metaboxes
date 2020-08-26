<?php
/**
 * %NAME% admin functions
 *
 * Functions available on admin
 *
 * @author %AUTHOR%
 * @category Core
 * @package %PACKAGENAME%/Core
 * @version %VERSION%
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * remove meta boxes
 */
function wmbox_remove_work_metabox() {
	remove_meta_box( 'work-details_wfolio_meta_box', 'work', 'normal' );
}
add_action( 'add_meta_boxes', 'wmbox_remove_work_metabox', 100 );