<?php

//Add this function to your theme functions.php
//call the function where you will output the css code

/**
 * Print typography properties with css structure for unyson framework typography-v2 field
 *
 * @param $selector string css selector
 * @param $option string theme option id
 * @param string $before string wrapper
 * @param string $after string wrapper
 */
function prefix_print_typography( $selector, $option, $before = '', $after = '' ) {
	if ( function_exists( 'fw_get_db_settings_option' ) ) {
		if ( fw_get_db_settings_option( $option ) ) {
			$font_styles  = array( 'normal', 'italic', 'oblique' );
			$font_weights = array( 'bold', 'bolder', 'lighter', 'regular' );
			echo $before;
			if ( fw_get_db_settings_option( $option . '/family' ) ) {
				echo $selector . ' {';
				echo 'font-family: ' . fw_get_db_settings_option( $option . '/family' ) . ';';
				if ( fw_get_db_settings_option( $option . '/size' ) ) {
					echo 'font-size: ' . fw_get_db_settings_option( $option . '/size' ) . 'px;';
				}

				if ( fw_get_db_settings_option( $option . '/variation' ) ) {
					if ( ctype_digit( fw_get_db_settings_option( $option . '/variation' ) ) ) {
						//Numbers only
						echo 'font-weight: ' . fw_get_db_settings_option( $option . '/variation' ) . ';';
					} elseif ( ctype_alpha( fw_get_db_settings_option( $option . '/variation' ) ) ) {
						//Letters only
						if ( in_array( fw_get_db_settings_option( $option . '/variation' ), $font_weights ) ) {
							if ( fw_get_db_settings_option( $option . '/variation' ) == 'regular' ) {
								echo 'font-weight: normal;';
							} else {
								echo 'font-weight: ' . fw_get_db_settings_option( $option . '/variation' ) . ';';
							}

						} elseif ( in_array( fw_get_db_settings_option( $option . '/variation' ), $font_styles ) ) {
							echo 'font-style: ' . fw_get_db_settings_option( $option . '/variation' ) . ';';
						}
					} elseif ( ctype_alnum( fw_get_db_settings_option( $option . '/variation' ) ) ) {
						//Letters and numbers
						echo 'font-weight: ' . substr( fw_get_db_settings_option( $option . '/variation' ), 0, 3 ) . ';';
						echo 'font-style: ' . substr( fw_get_db_settings_option( $option . '/variation' ), 3 ) . ';';
					}
				}

				if ( fw_get_db_settings_option( $option . '/color' ) ) {
					echo 'color: ' . fw_get_db_settings_option( $option . '/color' ) . ';';
				}

				if ( fw_get_db_settings_option( $option . '/letter-spacing' ) ) {
					echo 'letter-spacing: ' . fw_get_db_settings_option( $option . '/letter-spacing' ) . 'px;';
				}
				if ( fw_get_db_settings_option( $option . '/line-height' ) ) {
					echo 'line-height: ' . fw_get_db_settings_option( $option . '/line-height' ) . 'px;';
				}

				echo '}';
			}
			echo $after;
		}
	}
}

?>
