<?php
/**
 * River Gauge Reading block.
 *
 * Renders the stored reading only; the front end never waits on the API. The
 * view script refreshes the reading-dependent parts from the REST endpoint,
 * since the homepage HTML may be served from a page cache.
 *
 * @package CarkeekSiteBlocks
 *
 * @var array $attributes Block attributes.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$station   = CarkeekSiteBlocks_Gauge_Readings::sanitize_station( isset( $attributes['stationId'] ) ? $attributes['stationId'] : '' );
$is_editor = wp_is_serving_rest_request() && current_user_can( 'edit_posts' );

if ( '' === $station && ! $is_editor ) {
	return;
}

// Only the editor preview may fetch, so a new station ID shows live data.
$reading  = '' === $station ? null : CarkeekSiteBlocks_Gauge_Readings::get_reading( $station, $is_editor );
$parts    = CarkeekSiteBlocks_Gauge_Readings::render_parts( $station, $reading, $is_editor );
$heading  = isset( $attributes['heading'] ) ? $attributes['heading'] : '';
$link_url = isset( $attributes['linkUrl'] ) ? esc_url( $attributes['linkUrl'] ) : '';
$map_id   = isset( $attributes['mapImageId'] ) ? (int) $attributes['mapImageId'] : 0;
$map      = $map_id ? wp_get_attachment_image( $map_id, 'medium_large', false, array( 'class' => 'gauge-reading__map-image' ) ) : '';
$marker_x = isset( $attributes['markerX'] ) ? max( 0, min( 100, (float) $attributes['markerX'] ) ) : 50;
$marker_y = isset( $attributes['markerY'] ) ? max( 0, min( 100, (float) $attributes['markerY'] ) ) : 50;

$wrapper_attributes = array(
	'data-station' => $station,
);
if ( '' !== $station ) {
	$wrapper_attributes['data-endpoint'] = esc_url_raw( CarkeekSiteBlocks_Gauge_Readings::rest_url( $station ) );
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="gauge-reading<?php echo $map ? '' : ' has-no-map'; ?>">
		<div class="gauge-reading__header">
			<img class="gauge-reading__logo" src="<?php echo esc_url( CARKEEKSITEBLOCKS_PLUGIN_URL . 'assets/gauge-reading/floodzilla.svg' ); ?>" width="120" height="125" alt="<?php esc_attr_e( 'Floodzilla Gauge Network', 'carkeek-blocks' ); ?>">
			<div class="gauge-reading__titles">
				<?php if ( '' !== $heading ) : ?>
					<h2 class="gauge-reading__heading">
						<?php if ( $link_url ) : ?>
							<a href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $heading ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $heading ); ?>
						<?php endif; ?>
					</h2>
				<?php endif; ?>
				<?php echo $parts['time']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_parts(). ?>
			</div>
		</div>
		<div class="gauge-reading__body">
			<?php if ( $map ) : ?>
				<div class="gauge-reading__map" style="<?php echo esc_attr( '--marker-x:' . $marker_x . '%;--marker-y:' . $marker_y . '%' ); ?>">
					<?php echo $map; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $parts['marker']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_parts(). ?>
				</div>
			<?php endif; ?>
			<div class="gauge-reading__details">
				<?php echo $parts['values']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_parts(). ?>
				<p class="gauge-reading__credit">
					<?php esc_html_e( 'Live updates provided by', 'carkeek-blocks' ); ?>
					<?php if ( $link_url ) : ?>
						<a href="<?php echo esc_url( $link_url ); ?>">Floodzilla.com</a>
					<?php else : ?>
						Floodzilla.com
					<?php endif; ?>
				</p>
			</div>
		</div>
	</div>
</div>
