<?php
$activities = get_the_terms( get_the_ID(), 'skgt_location_cat' );
$access = get_field('loc_public_access');

?>
<div <?php echo get_block_wrapper_attributes( ); ?>>

<ul class="location-details-list no-bullets">
	<?php $loc_type = get_field( 'loc_type' ); ?>
	<?php if ( ! empty( $loc_type )  ) :
		$loc_type = implode( ', ', $loc_type );
		?>
		<li><strong>Property Type:</strong> <?php echo esc_html( $loc_type ); ?></li>
	<?php endif; ?>
	<?php if ( ! empty( get_field( 'loc_date_added_notes' ) ) ) : ?>
		<li><strong>Date Added:</strong> <?php echo esc_html( get_field( 'loc_date_added_notes' ) ); ?></li>
	<?php elseif ( ! empty( get_field( 'loc_date_added' ) ) ) : ?>
		<li><strong>Date Added:</strong> <?php echo esc_html( get_field( 'loc_date_added' ) ); ?></li>
	<?php endif; ?>
</ul>
<ul class="location-data-list no-bullets">
	<?php if ( ! empty( get_field( 'loc_location' ) ) ) : ?>
		<li class="location-data-item"><span class="icon-location" title="Location"></span> <?php echo esc_html( get_field( 'loc_location' ) ); ?></li>
	<?php endif; ?>
	<?php if ( ! empty( get_field( 'loc_acreage' ) ) ) : ?>
		<li class="location-data-item"><span class="icon-acreage" title="Acreage"></span> <?php echo esc_html( get_field( 'loc_acreage' ) ); ?> acres</li>
	<?php endif; ?>

	<li class="location-data-item"><span class="icon-access" title="Access"></span>
	<?php the_field('loc_public_access') ?></li>
	<?php if ( $activities && ! is_wp_error( $activities ) ) :
		foreach ( $activities as $activity ) { ?>
		<li class="location-data-item"><span class="icon-<?php echo esc_attr( $activity->slug ); ?>"></span>
			<?php echo esc_html( $activity->name ); ?>
		</li>

	<?php }
	endif; ?>
	</ul>
	<?php if ( is_array( $access ) && in_array( 'Open to the Public', $access ) ) :

		$link_settings = get_field( 'loc_get_directions' );
		$link_type = isset( $link_settings['link_settings'] ) ? $link_settings['link_settings'] : 'lat_lng';
		if ( 'hide' === $link_type ) :
			$link = '';
		elseif ( 'custom' === $link_type && ! empty( $link_settings['custom_link'] ) ) :
			$link = $link_settings['custom_link'];
		else :
			$link = 'https://www.google.com/maps/search/?api=1&query=' . urlencode( get_field( 'loc_lat' ) . ',' . get_field( 'loc_long' ) );
		endif;
		if ( ! empty( $link ) ) :
		?>
		<ul class="location-data-list no-bullets">
		<li class="location-data-link"><a class="arrow-link" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer">Get Directions</a></li>
		</ul>
		<?php endif; ?>
	<?php endif; ?>

</div>
