import { Link, Image, MediaToolbar } from '@10up/block-components';
import { useBlockProps, BlockControls } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

const BlockEdit = (props) => {
    const { attributes, setAttributes } = props;
    const { linkText, linkHoverText, linkUrl, opensInNewTab, imageId, focalPoint } = attributes;


    const blockProps = useBlockProps();

    const handleTextChange = value => setAttributes({linkText: value});
    const handleLinkChange = value => setAttributes({
        linkUrl: value?.url,
        opensInNewTab: value?.opensInNewTab,
        linkText: value?.title ?? linkText
    });
    const handleLinkRemove = () => setAttributes({
        linkUrl: null,
        opensInNewTab: null,
    });

	function handleImageSelect( image ) {
        setAttributes({imageId: image.id});
		if (image.sizes && image.sizes.large) {
			setAttributes({imageUrl: image.sizes.large.url});
		} else {
			setAttributes({imageUrl: image.url});
		}
    }

	function handleImageRemove() {
        setAttributes({imageId: null})
    }

    function handleFocalPointChange( value ) {
        setAttributes({focalPoint: value});
    }

    return (
		<div {...blockProps}>

			<BlockControls>
                <MediaToolbar

                    id={ imageId }
                    onSelect={ handleImageSelect }
                    onRemove={ handleImageRemove }

                />
            </BlockControls>
			<Image
				id={imageId}
				className="ck-link-tile__image_edit"
				size="full"
				onSelect={handleImageSelect}
				focalPoint={focalPoint}
				allowedTypes={['image/*']}
				onChangeFocalPoint={handleFocalPointChange}
				labels={{
					title: 'Select or Upload Image',
				}}
        	/>

            <Link
                value={ linkText }
                url={ linkUrl }
                opensInNewTab={ opensInNewTab }
                onTextChange={ handleTextChange }
                onLinkChange={ handleLinkChange }
                onLinkRemove={ handleLinkRemove }
                className='ck-link-tile__title_edit'
                placeholder='Enter Link Text here...'
                ariaLabel='Read more about our services'
            />
			<span className="ck-link-tile__overlay"></span>
        </div>
    )
}
export default BlockEdit;