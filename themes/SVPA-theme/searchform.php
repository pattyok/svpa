<?php
/**
 * The template for displaying search forms in
 *
 * @packagesvpa
 */

global $svpa_search_form_counter;
if ( ! isset( $svpa_search_form_counter ) ) {
	$svpa_search_form_counter = 0;
}
++$svpa_search_form_counter;
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="search-form__label screen-reader-text" for="s-<?php echo (int) $svpa_search_form_counter; ?>"><?php esc_html_e( 'Search for:', 'svpa' ); ?>
	</label>
	<input type="search" class="search-form__input" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" id="s-<?php echo (int) $svpa_search_form_counter; ?>" required>
	<button type="submit" class="search-form__submit button">
		<?php
		echo svpa_get_svg( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'search',
			[
				'width'  => '24',
				'height' => '24',
			]
		);
		?>
		<span class="screen-reader-text"><?php echo esc_attr_x( 'Search', 'submit button', 'svpa' ); ?></span>
	</button>
</form>
