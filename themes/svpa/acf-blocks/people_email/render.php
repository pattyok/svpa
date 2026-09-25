<?php
global $post;
$meta = get_field('people_email', $post->ID);

$label = 'Send An Email';

if ( ! empty( $post ) ) {
	if ( get_post_type( $post ) === 'people' ) {
		$name = get_the_title( $post );
		$label = 'Email ' . explode( ' ', $name )[0];
	}
}

if ( ! empty( $meta ) ) {
	echo '<p><a href="mailto:' . antispambot( esc_attr( $meta ) ) . '">' . esc_html( $label ) . '</a></p>';
}
if ( is_admin() ) {
	?>
	<div class="people-email-meta">
		Email Link
	</div>
	<?php
}
