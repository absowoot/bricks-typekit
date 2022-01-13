<?php

namespace Bricks;

final class BricksTypekit {

	static public $typekit_id;
	static public $typekit_fonts;
	static public $typekit_fonts_key;

	static public function init() {
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::add_typekit' );
		add_filter('bricks/theme_styles/controls', __CLASS__ .'::controls');
		add_filter('bricks/builder/standard_fonts', __CLASS__ .'::standard_fonts');
		add_action('wp_footer', __CLASS__ .'::ajax_update');
		add_action('wp_ajax_update_typekit_fonts', __CLASS__ .'::update_typekit_fonts');
	}

	static public function controls( $controls ) {
		$typography = $controls['typography'];

		$typekitControl = [ 'typekitID' => [
				'type'        => 'text',
				'label'       => esc_html__( 'Typekit ID', 'zest' ),
				'group'         => 'typography',
				'inline'    => true,
				'pasteStyles'   => false,
				'inlineEditing' => false,
				'hasDynamicData' => false,
			],
            'typekitRefresh' => [
	            'type'   => 'apply',
	            'reload' => true,
	            'label'  => esc_html__( 'Sync Typekit Now', 'zest' ),
	            'group'         => 'typography',
	            'required' => [ 'typekitID', '!=', '' ],
            ]
		];

		$controls['typography'] = $typekitControl + $typography;

		return $controls;
	}

	static private function get_typekit_id() {
		if( empty( self::$typekit_id ) ) {
			$theme_style_settings = Theme_Styles::$active_style_settings;
			self::$typekit_id = $theme_style_settings['typography']['typekitID'];
		}

		return self::$typekit_id;
	}

	static private function get_typekit_fonts() {
		$active_style_id = Theme_Styles::$active_style_id;
		self::$typekit_fonts_key =  'bricks_typekit_fonts_'. $active_style_id;

		if( empty( self::$typekit_fonts ) ) {

			self::$typekit_fonts = get_option( self::$typekit_fonts_key );

			if( empty( self::$typekit_fonts ) ) {
				$typekit_id = self::get_typekit_id();
				self::$typekit_fonts = self::get_typekit_details( $typekit_id );
				update_option(self::$typekit_fonts_key, self::$typekit_fonts );
			}

		}

		return self::$typekit_fonts;
	}

	static public function add_typekit() {
		$typekit_id = self::get_typekit_id();

		if( $typekit_id ) {
			wp_enqueue_style( 'typekit-' . $typekit_id, '//use.typekit.net/' . $typekit_id . '.css', null );
		}
	}

	static public function ajax_update() {
		if( bricks_is_builder_main() ) {
			echo "<script type=\"text/javascript\">
			(function($){
			    $(function() {
			        $('#bricks-panel').on('click', 'div[controlkey=\"typekitRefresh\"] .button', function () {
			            $.post(window.bricksData.ajaxUrl, {
			                action: 'bricks_update_typekit_fonts'
			            });
			        });
			    });
			})(jQuery);
			</script>";
		}
	}

	static public function standard_fonts( $fonts ) {
		$typekit_fonts = self::get_typekit_fonts();

		if( is_array( $typekit_fonts ) ) {
			foreach ( $typekit_fonts as $font_family_name => $fonts_url ) {
				$font_slug = isset( $fonts_url['slug'] ) ? $fonts_url['slug'] : '';
				$font_css  = isset( $fonts_url['css_names'][0] ) ? $fonts_url['css_names'][0] : $font_slug;
				$fallback = ( strpos( $fonts_url['fallback'], $font_css) !== FALSE ) ? str_replace( $font_css .',', '', $fonts_url['fallback']) : $fonts_url['fallback'];
				$weights = ( $fonts_url['weights'] ) ? $fonts_url['weights'] : '400';

				$fonts[] = $font_slug;
			}
		}

		return $fonts;
	}

	static private function get_typekit_details( $kit_id ) {
		$typekit_info = array();
		$typekit_uri  = 'https://typekit.com/api/v1/json/kits/' . $kit_id . '/published';
		$response     = wp_remote_get(
			$typekit_uri,
			array(
				'timeout' => '30',
			)
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$_POST['custom-typekit-id-notice'] = true;
			return $typekit_info;
		}

		$data     = json_decode( wp_remote_retrieve_body( $response ), true );
		$families = $data['kit']['families'];

		foreach ( $families as $family ) {

			$family_name = str_replace( ' ', '-', $family['name'] );

			$typekit_info[ $family_name ] = array(
				'family'   => $family_name,
				'fallback' => str_replace( '"', '', $family['css_stack'] ),
				'weights'  => array(),
			);

			foreach ( $family['variations'] as $variation ) {

				$variations = str_split( $variation );

				switch ( $variations[0] ) {
					case 'n':
						$style = 'normal';
						break;
					default:
						$style = 'normal';
						break;
				}

				$weight = $variations[1] . '00';

				if ( ! in_array( $weight, $typekit_info[ $family_name ]['weights'] ) ) {
					$typekit_info[ $family_name ]['weights'][] = $weight;
				}
			}

			$typekit_info[ $family_name ]['slug']      = $family['slug'];
			$typekit_info[ $family_name ]['css_names'] = $family['css_names'];
		}

		return $typekit_info;
	}

	static public function update_typekit_fonts() {
		update_option(self::$typekit_fonts_key, '' );
		
		wp_die();
	}

}

BricksTypekit::init();