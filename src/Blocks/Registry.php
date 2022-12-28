<?php

namespace WPGraphQLGutenberg\Blocks;

if ( ! defined( 'WP_GRAPHQL_GUTENBERG_REGISTRY_OPTION_NAME' ) ) {
	define( 'WP_GRAPHQL_GUTENBERG_REGISTRY_OPTION_NAME', 'wp_graphql_gutenberg_block_types' );
}

use GraphQL\Error\ClientAware;

class Registry {
	public static function normalize( $block_types ) {
		return array_reduce(
			$block_types,
			function ( $arr, $block_type ) {
				$arr[ $block_type['name'] ] = $block_type;
				return $arr;
			},
			array()
		);
	}

	public static function update_registry( $registry ) {
		return update_option( WP_GRAPHQL_GUTENBERG_REGISTRY_OPTION_NAME, $registry, false );
	}

	/**
	 * @throws RegistryNotSourcedException
	 */
	public static function get_registry() {
		$registry = get_option( WP_GRAPHQL_GUTENBERG_REGISTRY_OPTION_NAME ) ?? null;

		if ( empty( $registry ) ) {
			throw new RegistryNotSourcedException(
				__(
					'Client side block registry is missing. You need to open up gutenberg or load it from WPGraphQLGutenberg Admin page.',
					'wp-graphql-gutenberg'
				)
			);
		}

		return apply_filters( 'ggb_get_registry', $registry );
	}

	public static function delete_registry() {
		return delete_option( WP_GRAPHQL_GUTENBERG_REGISTRY_OPTION_NAME );
	}
}
