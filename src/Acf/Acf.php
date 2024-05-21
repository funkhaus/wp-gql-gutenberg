<?php
namespace WPGraphQLGutenberg\Acf;

use WPGraphQLGutenberg\Blocks\Block;
use WPGraphQLGutenberg\Schema\Types\BlockTypes;

final class ACF {

	/**
	 * WPGraphQL ACF Registry.
	 *
	 * @var \WPGraphQL\Acf\Registry
	 */
	protected $registry;

	public function __construct() {
		add_action( 'acf/init', array( $this, 'init' ) );
		add_action( 'wpgraphql/acf/type_registry/init', array( $this, 'init_registry' ) );
	}

	public function init() {
		/**
		 * If dependencies are missing, do not initialize the code
		 */
		if ( false === self::can_load_plugin() ) {
			// Show the admin notice
			add_action( 'admin_init', array( __CLASS__, 'show_admin_notice' ) );

			// Bail
			return;
		}

		add_filter(
			'graphql_acf_get_root_id',
			function ( $id, $root ) {
				if ( $root instanceof Block ) {
					$id = md5( json_encode( $root['attributes'] ) );
					acf_setup_meta(
						$root['attributes']['data'],
						$id,
						false
					);
				}

				return $id;
			},
			10,
			2
		);

		if ( class_exists( 'WPGraphQL\ACF\Config' ) ) {
			// Old version wp-graphql-acf integration. https://github.com/wp-graphql/wp-graphql-acf/
			\WPGraphQLGutenberg\Acf\Config::instance();
		} elseif ( class_exists( 'WPGraphQLAcf' ) ) {
			// New version wpgraphql-acf integration.  https://github.com/wp-graphql/wpgraphql-acf/
			add_filter( 'graphql_gutenberg_block_type_fields', array( $this, 'register_acf_fields_to_block' ), 10, 3 );
		}
	}

	/**
	 * Register acf fields to block.
	 *
	 * @param array                            $fields                                   Fields value to filter.
	 * @param array                            $block_type                               Block type array.
	 * @param \WPGraphQL\Registry\TypeRegistry $type_registry Type registry.
	 */
	public function register_acf_fields_to_block( $fields, $block_type, $type_registry ) {
		if ( isset( $block_type['acf'] ) || substr( $block_type['name'], 0, 4 ) === 'acf/' ) {
			$field_groups = acf_get_field_groups(
				array(
					'block' => $block_type['name'],
				)
			);

			if ( empty( $field_groups ) || ! is_array( $field_groups ) ) {
				return;
			}

			// Bail if registry is not initialized.
			if ( empty( $this->registry ) ) {
				return;
			}

			$type_name = BlockTypes::format_block_name( $block_type['name'] );

			foreach ( $field_groups as $field_group ) {
				// if a field group is explicitly set to NOT show in GraphQL, we'll leave
				// the field group out of the Schema.
				if ( ! $this->registry->should_field_group_show_in_graphql( $field_group ) || ! isset( $field_group['key'] ) ) {
					continue;
				}

				$field_name = isset( $field_group['graphql_field_name'] )
					? $field_group['graphql_field_name']
					: \WPGraphQL\Utils\Utils::format_field_name( $field_group['title'] );

				$field_group['type'] = 'group';
				$field_group['name'] = $field_name;

				// Register field type.
				$field_type_name = $type_name . '_' . ucfirst( $field_name );
				if ( null === $type_registry->get_type( $field_type_name ) ) {
					$type_registry->register_object_type(
						$field_type_name,
						array(
							'description' => __( 'Field Group', 'wp-graphql-acf' ),
							'interfaces'  => array( 'AcfFieldGroup' ),
							'kind'        => 'object',
							'fields'      => array(
								'fieldGroupName' => array(
									'resolve' => function( $source ) use ( $field_name ) {
										return $field_name;
									},
								),
							),
						)
					);

					$this->add_field_group_fields( $field_group, $field_type_name );
				}

				$config = array(
					'name'            => $field_name,
					'type'            => $field_type_name,
					'description'     => $field_group['description'],
					'acf_field'       => $field_group,
					'acf_field_group' => null,
					'resolve'         => function ( $root ) use ( $field_group ) {
						return isset( $root ) ? $root : null;
					},
				);

				$type_registry->register_field( $type_name, $field_name, $config );
			}
		}

		return $fields;
	}

	/**
	 * Init registry member variable.
	 *
	 * @param \WPGraphQL\Acf\Registry $registry
	 */
	public function init_registry( $registry ) {
		$this->registry = $registry;
	}
	/**
	 * Show admin notice to admins if this plugin is active but dependencies are missing
	 * are not active
	 *
	 * @return bool
	 */
	public static function show_admin_notice() {

		/**
		 * For users with lower capabilities, don't show the notice
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		add_action(
			'admin_notices',
			function() {
				?>
				<div class="error notice">
					<p><?php esc_html_e( 'WPGraphQL, Advanced Custom Fields, WPGraphQL for Advanced Custom Fields and  WPGraphQL Gutenberg must be active for "wp-graphql-gutenberg-acf" to work.', 'wp-graphql-gutenberg-acf' ); ?></p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Check whether ACF, WPGraphQL, WPGraphQlACF and WPGraphQLGutenberg are active
	 *
	 * @return bool
	 * @since 0.3
	 */
	public static function can_load_plugin() {
		// Is ACF active?
		if ( ! class_exists( 'ACF' ) ) {
			return false;
		}

		// Is WPGraphQL active?
		if ( ! class_exists( 'WPGraphQL' ) ) {
			return false;
		}

		// is not WPGraphQLGutenbergACF active?
		if ( class_exists( 'WPGraphQLGutenbergACF' ) ) {
			return false;
		}

		// WPGraphQLACF or WPGraphQL\ACF\ACF should be activated?
		if ( ! class_exists( 'WPGraphQLAcf' ) && ! class_exists( 'WPGraphQL\ACF\ACF' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type]  $root Undocumented.
	 * @param [type]  $acf_field Undocumented.
	 * @param boolean $format Whether ACF should apply formatting to the field. Default false.
	 *
	 * @return mixed
	 */
	protected function get_acf_field_value( $root, $acf_field, $format = false ) {

		$value = null;
		$id    = null;

		if ( is_array( $root ) && isset( $root['node'] ) ) {
			$id = $root['node']->ID;
		}

		if ( is_array( $root ) && ! ( ! empty( $root['type'] ) && 'options_page' === $root['type'] ) ) {

			if ( isset( $root[ $acf_field['key'] ] ) ) {
				$value = $root[ $acf_field['key'] ];

				if ( 'wysiwyg' === $acf_field['type'] ) {
					$value = apply_filters( 'the_content', $value );
				}
			}
		} else {

			switch ( true ) {
				case is_array( $root ) && ! empty( $root['type'] ) && 'options_page' === $root['type']:
					$id = $root['post_id'];
					break;
				default:
					$id = null;
					break;
			}
		}

		if ( empty( $value ) ) {

			/**
			 * Filters the root ID, allowing additional Models the ability to provide a way to resolve their ID
			 *
			 * @param int   $id   The ID of the object. Default null
			 * @param mixed $root The Root object being resolved. The ID is typically a property of this object.
			 */
			$id = apply_filters( 'graphql_acf_get_root_id', $id, $root );

			if ( empty( $id ) ) {
				return null;
			}

			$format = false;

			if ( 'wysiwyg' === $acf_field['type'] ) {
				$format = true;
			}

			if ( 'select' === $acf_field['type'] ) {
				$format = true;
			}

			/**
			 * Check if cloned field and retrieve the key accordingly.
			 */
			if ( ! empty( $acf_field['_clone'] ) ) {
				$key = $acf_field['__key'];
			} else {
				$key = $acf_field['key'];
			}

			$field_value = get_field( $key, $id, $format );

			$value = ! empty( $field_value ) ? $field_value : null;

		}

		/**
		 * Filters the returned ACF field value
		 *
		 * @param mixed $value     The resolved ACF field value
		 * @param array $acf_field The ACF field config
		 * @param mixed $root      The Root object being resolved. The ID is typically a property of this object.
		 * @param int   $id        The ID of the object
		 */
		return apply_filters( 'graphql_acf_field_value', $value, $acf_field, $root, $id );

	}

	/**
	 * Given a field group array, this adds the fields to the specified Type in the Schema
	 *
	 * @param array  $field_group The group to add to the Schema.
	 * @param string $type_name   The Type name in the GraphQL Schema to add fields to.
	 * @param bool   $layout      Whether or not these fields are part of a Flex Content layout.
	 */
	protected function add_field_group_fields( array $field_group, string $type_name, $layout = false ) {

		/**
		 * If the field group has the show_in_graphql setting configured, respect it's setting
		 * otherwise default to true (for nested fields)
		 */
		$field_group['show_in_graphql'] = isset( $field_group['show_in_graphql'] ) ? (bool) $field_group['show_in_graphql'] : true;

		/**
		 * Determine if the field group should be exposed
		 * to graphql
		 */
		if ( ! $this->should_field_group_show_in_graphql( $field_group ) ) {
			return;
		}

		/**
		 * Get the fields in the group.
		 */
		$acf_fields = ! empty( $field_group['sub_fields'] ) || $layout ? $field_group['sub_fields'] : acf_get_fields( $field_group );

		/**
		 * If there are no fields, bail
		 */
		if ( empty( $acf_fields ) || ! is_array( $acf_fields ) ) {
			return;
		}

		/**
		 * Stores field keys to prevent duplicate field registration for cloned fields
		 */
		$processed_keys = array();

		/**
		 * Loop over the fields and register them to the Schema
		 */
		foreach ( $acf_fields as $acf_field ) {
			if ( in_array( $acf_field['key'], $processed_keys, true ) ) {
				continue;
			} else {
				$processed_keys[] = $acf_field['key'];
			}

			/**
			 * Setup data for register_graphql_field
			 */
			$explicit_name   = ! empty( $acf_field['graphql_field_name'] ) ? $acf_field['graphql_field_name'] : null;
			$name            = empty( $explicit_name ) && ! empty( $acf_field['name'] ) ? self::camel_case( $acf_field['name'] ) : $explicit_name;
			$show_in_graphql = isset( $acf_field['show_in_graphql'] ) ? (bool) $acf_field['show_in_graphql'] : true;
			$description     = isset( $acf_field['instructions'] ) ? $acf_field['instructions'] : __( 'ACF Field added to the Schema by WPGraphQL ACF' );

			/**
			 * If the field is missing a name or a type,
			 * we can't add it to the Schema.
			 */
			if (
				empty( $name ) ||
				true != $show_in_graphql
			) {

				/**
				 * Uncomment line below to determine what fields are not going to be output
				 * in the Schema.
				 */
				continue;
			}

			$config = array(
				'name'            => $name,
				'description'     => $description,
				'acf_field'       => $acf_field,
				'acf_field_group' => $field_group,
			);

			$this->register_graphql_field( $type_name, $name, $config );

		}

	}
}
