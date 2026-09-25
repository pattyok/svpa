<?php
/**
 * Template part for displaying the page header of events
 *
 * @package svpa
 */

namespace WP_Rig\WP_Rig;

use WP_Rig\WP_Rig\Helpers;
$parent_id   = svpa()->get_top_level_parent_term( get_the_ID(), 'topic' );
$parent_term = get_term( $parent_id, 'topic' );
?>
<div class="page-header page-header-resources">
	<div class="entry-title">
			<div class="page-header-resources__title h1">Resources</div>
	</div>
</div>

<div class="resources__header">
	<ul class="breadcrumbs no-bullets list-inline">
		<li><a href="/resources">Resources</a></li>
		<li><strong><?php echo esc_html( $parent_term->name ); ?>:</strong> <?php the_title(); ?></li>
	</ul>
	<h1 class="resources__header__title"><?php the_title(); ?></h1>
</div>
