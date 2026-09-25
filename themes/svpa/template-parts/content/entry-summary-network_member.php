<?php
/**
 * Template part for displaying a post's content
 *
 * @package svpa
 */	

namespace WP_Rig\WP_Rig;

if (! function_exists( 'get_field' ) ) {
	return;
}
?>

	<div class="entry-summary network-member-summary">
		<div class="network-member-summary--title">
			
			<?php
			if ( get_field( 'network_website' ) ) {
				echo sprintf(
					'<a href="%s" target="_blank" rel="noopener">%s</a>',
					esc_url( get_field( 'network_website' ) ),
					esc_html( get_the_title() )
				);
			} else {
				echo esc_html( get_the_title() );
			}

			?>
		</div>
		<div class="network-member-summary--content">
			<?php
			$areas = get_the_terms( $post->ID, 'network_area' );
			if ( $areas && ! is_wp_error( $areas ) ) {
				$area_names = wp_list_pluck( $areas, 'name' );
				echo '<p>' . esc_html( implode( ', ', $area_names ) ) . '</p>';
			}
			?>
			<?php
			$partners = get_field( 'network_district_partners' );
			if ( $partners ) {
				echo '<p><strong>District Partners:</strong> ' . esc_html( $partners ) . '</p>';
			}
			?>
		</div>
	</div><!-- .entry-summary -->