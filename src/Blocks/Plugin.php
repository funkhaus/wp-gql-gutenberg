<?php
namespace WPGraphQLGutenberg\Blocks;

class Plugin {
    private static $default_colors = [
        'pale-pink'             => '#f78da7',
        'vivid-red'             => '#cf2e2e',
        'luminous-vivid-orange' => '#ff6900',
        'luminous-vivid-amber'  => '#fcb900',
        'light-green-cyan'      => '#7bdcb5',
        'vivid-green-cyan'      => '#00d084',
        'pale-cyan-blue'        => '#8ed1fc',
        'vivid-cyan-blue'       => '#0693e3',
        'vivid-purple'          => '#9b51e0',
        'white'                 => '#fff',
        'very-light-gray'       => '#eee',
        'cyan-bluish-gray'      => '#abb8c3',
        'very-dark-gray'        => '#313131',
        'black'                 => '#000',
    ];

    private static $default_font_sizes = [
        'small'  => 13,
        'normal' => 16,
        'medium' => 20,
        'large'  => 36,
        'huge'   => 42,
    ];

    public static function alter_attributes( $attributes, $blockName ) {
        
		$default_colors = apply_filters( 'ggb_default_colors', self::$default_colors );
		if ( isset( $attributes['textColor'] ) && isset( $default_colors[ $attributes['textColor'] ] ) ) {
			$attributes['textColorCode'] = $default_colors[ $attributes['textColor'] ];
		}

		if ( isset( $attributes['backgroundColor'] ) && isset( $default_colors[ $attributes['backgroundColor'] ] ) ) {
			$attributes['backgroundColorCode'] = $default_colors[ $attributes['backgroundColor'] ];
		}

        $default_font_sizes = apply_filters( 'ggb_default_font_sizes', self::$default_font_sizes );
        if ( isset( $attributes['fontSize'] ) && isset( $default_font_sizes[ $attributes['fontSize'] ] ) ) {
            $attributes['fontSizeValue'] = self::$default_font_sizes[ $attributes['fontSize'] ];
        }

		if ( isset( $attributes['style'] ) ) {

			// read textColor, backgroundColor from style
			if ( isset( $attributes['style']['color'] ) ) {
				if ( isset( $attributes['style']['color']['text'] ) ) {
					$attributes['textColorCode'] = $attributes['style']['color']['text'];
				}
				if ( isset( $attributes['style']['color']['background'] ) ) {
					$attributes['backgroundColorCode'] = $attributes['style']['color']['background'];
				}
			}

			// read fontSize from style
			if ( isset( $attributes['style']['typography'] ) && isset( $attributes['style']['typography']['fontSize'] ) ) {
                $attributes['fontSizeValue'] = $attributes['style']['typography']['fontSize'];
			}

		}

        return $attributes;
    }

    public static function alter_registry( $registry ) {
        foreach ( $registry as &$blockValue) {
            if ( isset( $blockValue['attributes'] ) ) {
                // add textColorCode, backgroundColorCode, and fontSizeValue attributes
                if ( isset( $blockValue['attributes']['textColor'] ) ) {
                    $blockValue['attributes']['textColorCode'] = array( 'type' => 'string' );
                }

                if ( isset( $blockValue['attributes']['backgroundColor'] ) ) {
                    $blockValue['attributes']['backgroundColorCode'] = array( 'type' => 'string' );
                }

                if ( isset( $blockValue['attributes']['fontSize'] ) ) {
                    $blockValue['attributes']['fontSizeValue'] = array( 'type' => 'string' );
                }
            }
        }

        return $registry;
    }

    function __construct() {
		add_filter( 'ggb_attributes', array( __CLASS__, 'alter_attributes' ), 10, 2 );
		add_filter( 'ggb_get_registry', array( __CLASS__, 'alter_registry' ) );
	}
}
