<?php
/*
Plugin Name: GravityView - Allow Front Page Views
Plugin URI: https://gravityview.co/
Description: Fix a known issue on WordPress core that prevents single entries from being visible when Views are embedded on the front page of a site.
Author: Katz Web Services, Inc.
Version: 1.1
Author URI: http://www.katzwebservices.com

Copyright 2014 Katz Web Services, Inc.  (email: info@katzwebservices.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

add_filter( 'parse_query', 'gv_fix_frontpage_parse_query', 10 );

/**
 * Allow GravityView entry endpoints on the front page of a site
 *
 * @link  https://core.trac.wordpress.org/ticket/23867 Fixes this core issue
 * @link https://wordpress.org/plugins/cpt-on-front-page/ Code is based on this
 *
 * @param WP_Query &$query (passed by reference)
 *
 * @return void
 */
function gv_fix_frontpage_parse_query( $query ) {
	global $wp_rewrite;

	if ( ( $query->is_home || $query->is_page ) && 'page' === get_option('show_on_front') && $page_id = get_option('page_on_front') ) {

		$_query = wp_parse_args( $query->query );

		// pagename can be set and empty depending on matched rewrite rules. Ignore an empty pagename.
		if ( isset( $_query['pagename'] ) && '' == $_query['pagename'] ) {
			unset( $_query['pagename'] );
		}

		// this is where will break from core wordpress
		$ignore = array( 'preview', 'page', 'paged', 'cpage' );
		foreach ( $wp_rewrite->endpoints as $endpoint ) {
			$ignore[] = $endpoint[1];
		}

		// Modify the query if:
		// - We're on the "Page on front" page (which we are), and:
		// - The query is empty OR
		// - The query includes keys that are associated with registered endpoints. `entry`, for example.
		if ( empty( $_query ) || ! array_diff( array_keys( $_query ), $ignore ) ) {

			$qv =& $query->query_vars;

			// Prevent redirect when on the single entry endpoint
			if( gravityview_is_single_entry() ) {
				add_filter( 'redirect_canonical', '__return_false' );
			}

			$query->is_page = true;
			$query->is_home = false;
			$qv['page_id']  = $page_id;

			// Correct <!--nextpage--> for page_on_front
			if ( ! empty( $qv['paged'] ) ) {
				$qv['page'] = $qv['paged'];
				unset( $qv['paged'] );
			}
		}

	}

	// reset the is_singular flag after our updated code above
	$query->is_singular = $query->is_single || $query->is_page || $query->is_attachment;
}