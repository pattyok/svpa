import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p>[ Event Shift Schedule and signup button — displays on front end ]</p>
		</div>
	);
}
