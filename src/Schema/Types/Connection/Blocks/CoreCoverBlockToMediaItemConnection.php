<?php

namespace WPGraphQLGutenberg\Schema\Types\Connection\Blocks;

use \WPGraphQL\Data\Connection\PostObjectConnectionResolver;

class CoreCoverBlockToMediaItemConnection {
	public function __construct() {
		add_action('graphql_register_types', function ( $type_registry ) {
			register_graphql_connection([
				'fromType'           => 'CoreCoverBlock',
				'toType'             => 'MediaItem',
				'fromFieldName'      => 'mediaItem',
				'oneToOne'           => true,
				'connectionTypeName' => 'CoreCoverBlockToMediaItemConnection',
				'resolve'            => function ( $source, $args, $context, $info ) {
					// Instantiate a new PostObjectConnectionResolver class
					$resolver = new PostObjectConnectionResolver( $source, $args, $context, $info, 'attachment' );

					// Set the argument that will be passed to WP_Query. We want only Posts (of any post type) that are tagged with this Tag's ID
					$resolver->set_query_arg( 'p', $source['attributes']['id'] );

					// Return the connection
					return $resolver->get_connection();
				},
			]);
		});
	}
}
