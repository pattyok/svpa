<?php
/**
 * Template part for displaying a post's taxonomy terms
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

?>

	<?php
		$list = get_the_terms( $post->ID, 'project-status' );

	if ( ! empty( $list ) ) {
		?>
			<div class="entry-taxonomies">
			<?php
			// we should have only one term, but just in case, loop through them.
			foreach ( $list as $term ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				?>
			<span class="project-status status-<?php echo esc_attr( $term->slug ); ?>">
				<?php
				printf(
					$term->name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
				?>
			</span>
				<?php
			}
			?>
			</div><!-- .entry-taxonomies -->
			<?php
	}
	?>
