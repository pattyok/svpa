document.addEventListener('DOMContentLoaded', function() {
	function handleProjectDetailsBlocks() {
		const projectDetailsBlocks = document.querySelectorAll('.wp-block-carkeek-site-blocks-project-details');

		const fontSize = 16;
		const lineHeight = fontSize * 1.8; // Typical line height is 1.8x font size
		let multiplier = 0.4; // 40% of the width
		if (window.innerWidth < 783) {
			multiplier = 0.6; // For smaller screens, use 60%
		}
		const baseMaxHeight = Math.round((window.innerWidth * multiplier)) - 32; // Adjust for the size of the button.
		const maxHeight = Math.floor( baseMaxHeight / lineHeight ) * lineHeight + 3; // Want a mutiple of the line height, plus a few pixels for the descenders

		projectDetailsBlocks.forEach(function(block) {
			// Remove existing read more button if it exists
			const existingBtn = block.parentNode.querySelector('.sg-read-more-btn');
			if (existingBtn) {
				existingBtn.remove();
			}
			
			// Reset block styles
			block.style.height = 'auto';
			block.style.overflow = 'visible';
			
			const originalHeight = block.scrollHeight;
			if (originalHeight > maxHeight) {
				// Set initial truncated height
				block.style.height = maxHeight + 'px';
				block.style.overflow = 'hidden';
				block.style.transition = 'height 0.3s ease';
				
				// Create read more button
				const readMoreBtn = document.createElement('button');
				readMoreBtn.textContent = 'Read More';
				readMoreBtn.className = 'sg-read-more-btn';
				
				// Insert button after the block
				block.parentNode.insertBefore(readMoreBtn, block.nextSibling);
				
				// Toggle functionality
				let isExpanded = false;
				readMoreBtn.addEventListener('click', function() {
					if (isExpanded) {
						block.style.height = maxHeight + 'px';
						readMoreBtn.textContent = 'Read More';
						isExpanded = false;
					} else {
						block.style.height = originalHeight + 'px';
						readMoreBtn.textContent = 'Read Less';
						isExpanded = true;
					}
				});
			} else {
				// remove the read more button if the content fits
				const readMoreBtn = block.parentNode.querySelector('.sg-read-more-btn');
				if (readMoreBtn) {
					readMoreBtn.remove();
				}
			}
			block.classList.add('loaded');
		});
	}

	handleProjectDetailsBlocks();
	window.addEventListener('resize', handleProjectDetailsBlocks);
});
