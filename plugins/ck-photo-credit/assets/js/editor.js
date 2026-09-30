( function( wp ) {
	const __ = wp.i18n.__;
	const addFilter = wp.hooks.addFilter;
	const Fragment = wp.element.Fragment;
	const el = wp.element.createElement;
	const createPortal = wp.element.createPortal;
	const RawHTML = wp.element.RawHTML;
	const useEffect = wp.element.useEffect;
	const useState = wp.element.useState;
	const createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	const useSelect = wp.data.useSelect;
	const InspectorControls = wp.blockEditor.InspectorControls;
	const PanelBody = wp.components.PanelBody;
	const ToggleControl = wp.components.ToggleControl;
	const SelectControl = wp.components.SelectControl;

	const TARGET_BLOCKS = [ 'core/media-text', 'core/cover', 'core/image' ];
	const ATTR_SHOW = 'ckShowPhotoCredit';
	const ATTR_POSITION = 'ckPhotoCreditPosition';

	function isTargetBlock( blockName ) {
		return TARGET_BLOCKS.indexOf( blockName ) !== -1;
	}

	function addPhotoCreditAttributes( settings, name ) {
		if ( ! isTargetBlock( name ) ) {
			return settings;
		}

		settings.attributes = settings.attributes || {};
		settings.attributes[ ATTR_SHOW ] = {
			type: 'boolean',
			default: false,
		};
		settings.attributes[ ATTR_POSITION ] = {
			type: 'string',
			default: 'below',
		};

		return settings;
	}

	function getAttachmentId( blockName, attributes ) {
		if ( blockName === 'core/media-text' && attributes.mediaId ) {
			return parseInt( attributes.mediaId, 10 );
		} else if ( attributes.id ) {
			return parseInt( attributes.id, 10 );
		}
		return 0;
	}

	function EditorFigureCreditPortal( props ) {
		const clientId = props.clientId;
		const credit = props.credit;
		const position = props.position;
		const [ mountNode, setMountNode ] = useState( null );

		useEffect( function() {
			if ( ! clientId || ! credit ) {
				setMountNode( null );
				return undefined;
			}

			const blockRoot = document.querySelector( '[data-block="' + clientId + '"]' );
			const figure = blockRoot ? blockRoot.querySelector( 'figure' ) : null;

			if ( ! figure ) {
				setMountNode( null );
				return undefined;
			}

			const node = document.createElement( 'div' );
			node.className = 'ck-photo-credit-editor-mount';
			figure.appendChild( node );
			setMountNode( node );

			return function() {
				if ( node.parentNode ) {
					node.parentNode.removeChild( node );
				}
			};
		}, [ clientId, credit ] );

		if ( ! mountNode ) {
			return null;
		}

		return createPortal(
			el(
				'div',
				{
					className: 'ck-photo-credit ck-photo-credit--editor ck-photo-credit--' + position,
				},
				el( RawHTML, null, credit )
			),
			mountNode
		);
	}

	const withPhotoCreditControls = createHigherOrderComponent( function( BlockEdit ) {
		return function( props ) {
			const name = props.name;
			const clientId = props.clientId;
			const attributes = props.attributes || {};
			const setAttributes = props.setAttributes;

			if ( ! isTargetBlock( name ) ) {
				return el( BlockEdit, props );
			}

			const showCredit = !! attributes[ ATTR_SHOW ];
			const position = attributes[ ATTR_POSITION ] || 'below';
			const attachmentId = getAttachmentId( name, attributes );

			const media = useSelect(
				function( select ) {
					if ( ! attachmentId ) {
						return null;
					}

					const coreStore = select( 'core' );
					return coreStore ? coreStore.getMedia( attachmentId ) : null;
				},
				[ attachmentId ]
			);

			const credit = media && media.meta ? media.meta.ck_photo_credit : '';

			const controls = [
				el( ToggleControl, {
					key: 'toggle',
					label: __( 'Display photo credit', 'wp-rig' ),
					checked: showCredit,
					onChange: function( value ) {
						setAttributes( { [ ATTR_SHOW ]: value } );
					},
				} ),
			];

			if ( showCredit ) {
				controls.push(
					el( SelectControl, {
						key: 'position',
						label: __( 'Credit position', 'wp-rig' ),
						value: position,
						options: [
							{ label: __( 'Below image', 'wp-rig' ), value: 'below' },
							{ label: __( 'Overlay', 'wp-rig' ), value: 'overlay' },
						],
						onChange: function( value ) {
							setAttributes( { [ ATTR_POSITION ]: value } );
						},
					} )
				);
			}

			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				showCredit && credit ? el( EditorFigureCreditPortal, {
					clientId: clientId,
					credit: credit,
					position: position,
				} ) : null,
				showCredit && credit && name === 'core/cover' ? el(
					'div',
					{
						className: 'ck-photo-credit ck-photo-credit--editor ck-photo-credit--' + position,
					},
					el( RawHTML, null, credit )
				) : null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Photo Credit', 'wp-rig' ),
							initialOpen: false,
						},
						controls
					)
				)
			);
		};
	}, 'withPhotoCreditControls' );

	const addEditorPreviewClass = createHigherOrderComponent( function( BlockListBlock ) {
		return function( props ) {
			const name = props.name;
			const attributes = props.attributes || {};

			if ( ! isTargetBlock( name ) ) {
				return el( BlockListBlock, props );
			}

			if ( attributes[ ATTR_SHOW ] ) {
				const position = attributes[ ATTR_POSITION ] || 'below';
				const className = [
					props.className || '',
					'ck-photo-credit-enabled',
					'ck-photo-credit-position-' + position,
				].join( ' ' ).replace( /\s+/g, ' ' ).trim();

				return el( BlockListBlock, Object.assign( {}, props, { className: className } ) );
			}

			return el( BlockListBlock, props );
		};
	}, 'addEditorPreviewClass' );

	addFilter( 'blocks.registerBlockType', 'ck-photo-credit/add-attributes', addPhotoCreditAttributes );
	addFilter( 'editor.BlockEdit', 'ck-photo-credit/add-controls', withPhotoCreditControls );
	addFilter( 'editor.BlockListBlock', 'ck-photo-credit/add-editor-preview-class', addEditorPreviewClass );
} )( window.wp );
