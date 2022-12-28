<?php

namespace WPGraphQLGutenberg\Schema;

class Schema {
	public function __construct() {
		new \WPGraphQLGutenberg\Schema\Types\Scalar\Scalar();
		new \WPGraphQLGutenberg\Schema\Types\Object\ReusableBlock();
		new \WPGraphQLGutenberg\Schema\Types\InterfaceType\Block();
		new \WPGraphQLGutenberg\Schema\Types\InterfaceType\BlockEditorContentNode();
		new \WPGraphQLGutenberg\Schema\Types\Connection\BlockEditorContentNodeConnection();
		new \WPGraphQLGutenberg\Schema\Types\Connection\Blocks\CoreImageBlockToMediaItemConnection();
		new \WPGraphQLGutenberg\Schema\Types\Connection\Blocks\CoreCoverBlockToMediaItemConnection();
		new \WPGraphQLGutenberg\Schema\Types\BlockTypes();
	}
}
