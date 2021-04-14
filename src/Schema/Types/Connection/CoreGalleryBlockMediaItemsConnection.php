<?php
namespace WPGraphQLGutenberg\Schema\Types\Connection;

class CoreGalleryBlockMediaItemsConnection {
	function __construct() {
		add_action( 'graphql_register_types', function() {
			register_graphql_connection([
				'fromType' => 'CoreGalleryBlock',
				'toType' => 'MediaItem',
				'fromFieldName' => 'mediaItems',
				'resolve' => function( $source, $args, $context, $info ) {
					// Instantiate a new PostObjectConnectionResolver class
					$resolver = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $source, $args, $context, $info, 'attachment' );

					// Set the argument that will be passed to WP_Query. We want only Posts (of any post type) that are tagged with this Tag's ID
					$resolver->set_query_arg( 'post__in', $source['attributes']['ids'] );

					// Return the connection
					return $resolver->get_connection();
				}
			]);
		} );
	}
}
