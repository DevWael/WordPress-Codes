<?php
/**
 * @param $username --> instagram username
 *
 * @return mixed|WP_Error
 */
function prefix_scrape_instagram( $username ) {
	$username = strtolower( $username );
	$username = str_replace( '@', '', $username );
	if ( false === ( $instagram = get_transient( 'wink-instagram-' . sanitize_title_with_dashes( $username ) ) ) ) {
		$remote = wp_remote_get( 'http://instagram.com/' . trim( $username ) );
		if ( is_wp_error( $remote ) ) {
			return new WP_Error( 'site_down', esc_html__( 'Unable to communicate with Instagram.', 'wink' ) );
		}
		if ( 200 != wp_remote_retrieve_response_code( $remote ) ) {
			return new WP_Error( 'invalid_response', esc_html__( 'Instagram did not return a 200.', 'wink' ) );
		}
		$shards      = explode( 'window._sharedData = ', $remote['body'] );
		$insta_json  = explode( ';</script>', $shards[1] );
		$insta_array = json_decode( $insta_json[0], true );
		if ( ! $insta_array ) {
			return new WP_Error( 'bad_json', esc_html__( 'Instagram has returned invalid data.', 'wink' ) );
		}
		if ( isset( $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'] ) ) {
			$images = $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'];
		} else {
			return new WP_Error( 'bad_json_2', esc_html__( 'Instagram has returned invalid data.', 'wink' ) );
		}
		if ( ! is_array( $images ) ) {
			return new WP_Error( 'bad_array', esc_html__( 'Instagram has returned invalid data.', 'wink' ) );
		}
		$instagram = array();
		foreach ( $images as $image ) {
			$image['thumbnail_src'] = preg_replace( '/^https?\:/i', '', $image['thumbnail_src'] );
			$image['display_src']   = preg_replace( '/^https?\:/i', '', $image['display_src'] );
			// handle both types of CDN url
			if ( ( strpos( $image['thumbnail_src'], 's640x640' ) !== false ) ) {
				$image['thumbnail'] = str_replace( 's640x640', 's160x160', $image['thumbnail_src'] );
				$image['small']     = str_replace( 's640x640', 's320x320', $image['thumbnail_src'] );
			} else {
				$urlparts  = wp_parse_url( $image['thumbnail_src'] );
				$pathparts = explode( '/', $urlparts['path'] );
				array_splice( $pathparts, 3, 0, array( 's160x160' ) );
				$image['thumbnail'] = '//' . $urlparts['host'] . implode( '/', $pathparts );
				$pathparts[3]       = 's320x320';
				$image['small']     = '//' . $urlparts['host'] . implode( '/', $pathparts );
			}
			$image['large'] = $image['thumbnail_src'];
			if ( $image['is_video'] == true ) {
				$type = 'video';
			} else {
				$type = 'image';
			}
			$caption = esc_html__( 'Instagram Image', 'wink' );
			if ( ! empty( $image['caption'] ) ) {
				$caption = $image['caption'];
			}
			$instagram[] = array(
				'description' => $caption,
				'link'        => trailingslashit( '//instagram.com/p/' . $image['code'] ),
				'time'        => $image['date'],
				'comments'    => $image['comments']['count'],
				'likes'       => $image['likes']['count'],
				'thumbnail'   => $image['thumbnail'],
				'small'       => $image['small'],
				'large'       => $image['large'],
				'original'    => $image['display_src'],
				'type'        => $type
			);
		}
		// do not set an empty transient - should help catch private or empty accounts
		if ( ! empty( $instagram ) ) {
			$instagram = serialize( $instagram );
			set_transient( 'wink-instagram-' . sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'null_instagram_cache_time', HOUR_IN_SECONDS * 2 ) );
		}
	}
	if ( ! empty( $instagram ) ) {
		return unserialize( $instagram );
	} else {
		return new WP_Error( 'no_images', esc_html__( 'Instagram did not return any images.', 'wink' ) );
	}
}

/**
 * Display instagram content returned by wink_scrape_instagram()
 *
 * @param $username
 * @param int $photos_count
 *
 * @return array|mixed|WP_Error
 */
if ( ! function_exists( 'prefix_instagram_photos_views' ) ) {
	function prefix_instagram_photos_views( $username, $photos_count = 6, $before = '', $after = '' ) {
		if ( $username ) {
			$instagram_media = prefix_scrape_instagram( $username );
			if ( is_wp_error( $instagram_media ) ) {
				echo '<div class="insta-error">' . wp_kses_post( $instagram_media->get_error_message() ) . '</div>';
			} else {
				echo $before;
				$instagram_media = array_slice( $instagram_media, 0, $photos_count );
				foreach ( $instagram_media as $photo ) {
					?>
					<div class="wink-instagram-photo instagram-item">
						<img src="<?php echo esc_url( $photo['small'] ); ?>"
						     alt="<?php echo esc_attr( $photo['description'] ) ?>"
						     class="img-responsive">
						<a href="<?php echo esc_url( $photo['link'] ) ?>"
						   title="<?php esc_attr_e( 'Go to the post', 'wink' ); ?>"
						   class="to-insta-photo" target="_blank"><i class="fa fa-instagram" aria-hidden="true"></i></a>
						<a href="<?php echo esc_url( $photo['original'] ) ?>"
						   title="<?php echo esc_attr( $photo['description'] ) ?>"
						   class="wink-insta-gallery"><i class="fa fa-arrows-alt" aria-hidden="true"></i></a>
					</div>
					<?php
				}
				echo $after;
			}
		}
	}
}
