<?php
/**
 * Template part for displaying a post's categories as a drop down list
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>

$c_terms = get_terms( 'category', array( 'hide_empty' => 1 ) );
$current = get_query_var( 'cat' );
$active  = empty( $current ) ? 'active' : '';
?>
<div class="select-list-wrapper">
	<a class="btn btn-select btn-category-select toggle-slide" href="#" data-target="cat_list"><span class="label">Select Category</a>
	<ul class="toggle-target select-list no-list" id="cat_list">
		<?php $page_for_posts = get_option( 'page_for_posts' ); ?>

		<li class="category-select-item <?php echo esc_attr( $active ); ?>"><a href="<?php the_permalink( $page_for_posts ); ?>">All Posts</a></li>
	<?php

	foreach ( $c_terms as $c_term ) {
		if ( 'blog' !== $c_term->slug ) {
			$active = $current == $c_term->term_id ? 'active' : '';
			?>
			<li class="category-select-item <?php echo esc_attr( $active ); ?>">
				<a href="<?php echo esc_url( get_category_link( $c_term ) ); ?>">
					<?php echo esc_html( $c_term->name ); ?>
				</a>
			</li>
	<?php } ?>
	<?php } ?>
	</ul>
</div>
