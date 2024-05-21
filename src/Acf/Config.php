<?php
namespace WPGraphQLGutenberg\Acf;

use WPGraphQLGutenberg\Schema\Types\BlockTypes;

final class Config extends \WPGraphQL\ACF\Config {

	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Config();
		}

		return self::$instance;
	}

	protected function add_acf_fields_to_block( $block_type ) {
		$field_groups = acf_get_field_groups(
			array(
				'block' => $block_type['name'],
			)
		);

		if ( empty( $field_groups ) || ! is_array( $field_groups ) ) {
			return;
		}

		$type_name = BlockTypes::format_block_name( $block_type['name'] );

		foreach ( $field_groups as $field_group ) {
			$field_name = isset( $field_group['graphql_field_name'] )
				? $field_group['graphql_field_name']
				: self::camel_case( $field_group['title'] );

			$field_group['type'] = 'group';
			$field_group['name'] = $field_name;
			$config              = array(
				'name'            => $field_name,
				'description'     => $field_group['description'],
				'acf_field'       => $field_group,
				'acf_field_group' => null,
				'resolve'         => function ( $root ) use ( $field_group ) {
					return isset( $root ) ? $root : null;
				},
			);

			$this->register_graphql_field( $type_name, $field_name, $config );
		}
	}

	public function __construct() {
		add_filter(
			'graphql_gutenberg_block_type_fields',
			function ( $fields, $block_type, $type_registry ) {
				$this->type_registry = $type_registry;
				if ( isset( $block_type['acf'] ) || substr( $block_type['name'], 0, 4 ) === 'acf/' ) {
					$this->add_acf_fields_to_block( $block_type );
				}

				return $fields;
			},
			10,
			3
		);
	}
}
