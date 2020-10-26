<?php

/**
 * Plugin Name:       Theme Test Helper
 * Plugin URI:        https://github.com/StevenDufresne/wp-theme-validation-action-experiment
 * Description:       Provides a REST API for fetching info about the theme and site, for use in testing.
 * Version:           0.1
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            tellyworth
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// This is of no use as a stand-alone plugin. All it does is provide a REST API endpoint.

add_action( 'rest_api_init', function () {
  register_rest_route( 'theme-test-helper/v1', '/info', array(
    'methods' => 'GET',
    'callback' => 'tw_get_test_info',
  ) );
} );

function tw_get_test_info() {
	$out = array();

	$theme = wp_get_theme();

	// The important bits from WP_Theme.
	$header_fields = [ 'Name', 'ThemeURI', 'Description', 'Author', 'AuthorURI', 'Version', 'Template', 'Status', 'Tags', 'TextDomain', 'DomainPath' ];
	$out[ 'theme' ] = [];
	foreach ( $header_fields as $field ) {
		$out[ 'theme' ][ $field ] = $theme->get( $field );
	}

	// Theme and author URLs, for convenience.
	$out[ 'theme_urls' ] = array();
	if ( $theme->get( 'ThemeURI' ) )
		$out[ 'theme_urls' ][] = $theme->get( 'ThemeURI' );
	if ( $theme->get( 'AuthorURI' ) )
		$out[ 'theme_urls' ][] = $theme->get( 'AuthorURI' );

	// Fun with Sitemaps
	$sitemaps = new WP_Sitemaps();
	$sitemaps->register_sitemaps();

	$urls = [];
	foreach( $sitemaps->registry->get_providers() as $provider ) {
		foreach( array_keys( $provider->get_object_subtypes() ) as $subtype ) {
			// No need to get multiple pages of results here, the first page of each subtype should have plenty of content.
			foreach( $provider->get_url_list( 1, $subtype ) as $url ) {
				$parts = wp_parse_url( $url['loc'] );
				$urls[] = [ 
					$parts['path'],
					$parts['query'] ? '?' . $parts['query'] : '',
					$subtype
				];
			}
		}
	}

	// A list of all (most) of the public pages on the site.
	$out[ 'site_urls' ] = $urls;

	return $out;
}