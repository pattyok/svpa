<?php

// var_dump($content);
		// this is an array, but we are only accepting single value, in the future that may change.
		$tax_urls = array();
		$taxes    = array();
		$base_url = get_rest_url() . 'wp/v2/';
		// ck_members?per_page=100&_fields=id,link,title,excerpt,ck_business_type,acf.member_address`
		// ${baseUrl}ck_business_type?per_page=100&_fields=id,count,name,slug,parent
if ( ! empty( $attributes['taxonomySelected'] ) ) {
	if ( is_array( $attributes['taxonomySelected'] ) ) {
		foreach ( $attributes['taxonomySelected'] as $tax ) {
			$taxes[]    = $tax;
			$rest_url   = $base_url . $tax . '?per_page=100&_fields=id,project,cat,name,slug,parent,acf';
			$tax_urls[] = $rest_url;
		}
	} else {
		$tax        = $attributes['taxonomySelected'];
		$taxes[]    = $tax;
		$rest_url   = $base_url . $tax . '?per_page=100&_fields=id,project,cat,name,slug,parent,acf';
		$tax_urls[] = $rest_url;
	}
}
$tax_urls      = apply_filters( 'ck_maparchive_taxurl', $tax_urls, $attributes );
$tax_rest_urls = implode( '|', $tax_urls );
$data_taxes    = implode( ',', $taxes );

// Add filtering to the rest url.
$tax_filter = '';
if ( true == $attributes['filterItemsByTaxonomy'] && isset( $attributes['filterItemsTaxonomySelected'] ) && isset( $attributes['filterItemsTaxonomyTermsSelected'] ) ) {
	$tax_filter = '&' . $attributes['filterItemsTaxonomySelected'] . '=' . implode( ',', $attributes['filterItemsTaxonomyTermsSelected'] );
}

// lat|lng
if ( isset( $attributes['addressFieldType'] ) && 'acf' === $attributes['addressFieldType'] ) {
	$address_field = 'acf.' . $attributes['addressFieldSelected'] . '.lat|acf.' . $attributes['addressFieldSelected'] . '.lng';
} else {
	$address_field = $attributes['latFieldSelected'] . '|' . $attributes['lngFieldSelected'];
}

		$data_url = $base_url . $attributes['postTypeSelected'] . '?per_page=100&show_on_map=true&_fields=featured_image,featured_media,id,link,title,project,cat,date,meta,acf,' . $data_taxes . $tax_filter;

		$data_url = apply_filters( 'ck_maparchive_dataurl', $data_url, $attributes );
?>

<div <?php echo get_block_wrapper_attributes(); ?>>
	<div id="mapped-posts-map-intro" style="display: none">
		<?php echo wp_kses_post( $content ); ?>
	</div>
	<div id="mapped-posts-map"
		data-filter-label="<?php echo esc_attr( $attributes['filterLabel'] ); ?>"
		data-post="<?php echo esc_attr( $attributes['postTypeSelected'] ); ?>"
		data-thumb="<?php echo esc_attr( $attributes['popupImage'] ); ?>"
		data-taxonomy="<?php echo esc_attr( $data_taxes ); ?>"
		data-taxurl="<?php echo esc_attr( $tax_rest_urls ); ?>"
		data-items="<?php echo esc_attr( $data_url ); ?>"
		data-address="<?php echo esc_attr( $address_field ); ?>"
		data-cluster="<?php echo esc_attr( $attributes['clusterMarkers'] ); ?>"
		>
	</div>
</div>
