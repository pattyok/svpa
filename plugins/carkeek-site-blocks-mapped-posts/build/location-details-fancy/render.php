<?php
/** 
 * Render the project details
 */
$fields = array(
    'group' => array(
        'label' => 'Square Footage',
        'type' => 'text',
        'fields' => array('sg_square_feet','sg_buildings'),
    ),
    'project_services' => array(
        'label' => 'Services Provided',
        'type' => 'taxonomy',
    ),
    'project_industries' => array(
        'label' => 'Industry',
        'type' => 'taxonomy'
    ),
    'sg_client' => array(
        'label' => 'Client',
        'type' => 'text',
    ),
    'sg_location' => array(
        'label' => 'Location',
        'type' => 'text',
    ),
    'sg_construction_cost' => array(
        'label' => 'Construction Cost',
        'type' => 'text',
    ),
    'sg_awards' => array(
        'label' => 'Awards',
        'type' => 'repeater',
        'sub' => 'sg_award',
    ),

);
?>
<div <?php echo get_block_wrapper_attributes( ); ?>>
<ul class="project-details-list">

<?php
foreach ($fields as $key => $field ) {
    if ( 'group' == $key ) {
        $val = array_map(function($f){
            $n = get_field($f);
            if (!empty($n)) {
				if ('sg_buildings' == $f) {
					$n = str_replace('|', '<br/>', $n);
				}
                return $n;
            }
        }, $field['fields']);
	} else if ('taxonomy' == $field['type']) {
		$val = get_the_terms( get_the_ID(), $key );
		$vals_arr = array();
		if (is_array($val)) {
			foreach ($val as $v) {
				$vals_arr[] = $v->name;
			}
		}
        $val = $vals_arr;
    } else {
        $val = get_field($key);
    }
    if ('repeater' == $field['type'] && is_array($val)) {
        $vals_arr = array();
        foreach ($val as $v) {
            $vals_arr[] = $v[$field['sub']];
        }
        $val = $vals_arr;
    }
    if (!empty($val)) {
		if (isset($field['hide_label']) && $field['hide_label']) {
			$label_class = 'screen-reader-text';
		} else {
			$label_class = '';
		}
?>
	<li><span class="<?php echo $label_class; ?> project-label"><?php echo $field['label']; ?></span>
	<?php
		$display = '<ul class="' . $key . '">';

		?>
		<?php
		if (is_array($val)) {
			if ('group' != $key) {
			asort($val);
			}
			$val = implode('</li><li>', $val);
		}

		$display .= '<li>' . $val . '</li>';
		$display .= '</ul>';
		echo wp_kses_post($display);

?>
</li>
<?php
    }
}
?>
</ul>
<div class="project-details-text">
	<?php
		echo do_blocks( $content );
	?>

</div>
</div>