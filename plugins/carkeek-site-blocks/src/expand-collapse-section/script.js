if( typeof TabExpander === "undefined")
    {
        var tabExpanders = [];
        class TabExpander {
            static openedEle = null;
            constructor(domNode, multiple ) {
            if (typeof multiple == "undefined") multiple=true;
            if (typeof multiple == "boolean") this.multiple = multiple;


            this.buttonEl = domNode;
			this.buttonLabel = this.buttonEl.querySelector('span');
            if ( this.buttonEl == null)
            {
                console.log("button and/or aria-expanded attribute is missing.");
                return null;
            }

            const controlsId = this.buttonEl.getAttribute('aria-controls');
            this.contentEl = document.getElementById(controlsId);
            this.open = this.buttonEl.getAttribute('aria-expanded') === 'true';
            this.closeActiveTab();

            // add event listeners
            this.buttonEl.addEventListener('click', this.onButtonClick.bind(this));
            }

            onButtonClick() {
            this.toggle(!this.open);
            }

            toggle(open) {
            // don't do anything if the open state doesn't change
            if (open === this.open) {
                return;
            }
            // update the internal state
            this.open = open;
            // handle DOM updates
            this.buttonEl.setAttribute('aria-expanded', `${open}`);
            
            if (open) {
                this.slideDown();
            } else {
                this.slideUp();
            }
            this.closeActiveTab();

            }

            closeActiveTab()
            {
                if (this.multiple ) return;
                if(this.open)
                {
                    if( TabExpander.openedEle != null )
                    {
                        TabExpander.openedEle.slideUp();
                        TabExpander.openedEle.open = false;
                        TabExpander.openedEle.buttonEl.setAttribute('aria-expanded', 'false');
                    }
                    TabExpander.openedEle = this;
                }
                else
                {
                    if(this == TabExpander.openedEle)
                        TabExpander.openedEle = null;
                }
            }



            slideDown() {
                // Remove hidden attribute and prepare for animation
                this.contentEl.setAttribute('aria-expanded', 'true');
                this.contentEl.style.display = 'flex';
                
                // Get the natural height
                const height = this.contentEl.scrollHeight + 'px';

				// Set the label back to the closed state
				this.buttonLabel.textContent = this.buttonEl.getAttribute('data-label-open');
                
                // Set initial state for animation
                this.contentEl.style.height = '0px';
                this.contentEl.style.overflow = 'hidden';
                this.contentEl.style.transition = 'height 0.3s ease-out';
                
                // Force reflow to ensure the initial state is applied
                this.contentEl.offsetHeight;
                
                // Animate to natural height
                this.contentEl.style.height = height;
                
                // Clean up after animation
                setTimeout(() => {
                    this.contentEl.style.height = '';
                    this.contentEl.style.overflow = '';
                    this.contentEl.style.transition = '';
                }, 300);
            }

            slideUp() {
                // Set current height and prepare for animation
                const height = this.contentEl.scrollHeight + 'px';
                this.contentEl.style.height = height;
                this.contentEl.style.overflow = 'hidden';
                this.contentEl.style.transition = 'height 0.3s ease-out';

				// Set the label back to the closed state
				this.buttonLabel.textContent = this.buttonEl.getAttribute('data-label');
                
                // Force reflow
                this.contentEl.offsetHeight;
                
                // Animate to 0 height
                this.contentEl.style.height = '0px';
                
                // Hide completely after animation
                setTimeout(() => {
                    this.contentEl.setAttribute('aria-expanded', 'false');
                    this.contentEl.style.height = '';
                    this.contentEl.style.overflow = '';
                    this.contentEl.style.transition = '';
                    this.contentEl.style.display = '';
                }, 300);
            }

            closeOthers()
            {
                const expanders = document.querySelectorAll('.cks-expand-button');
                tabExpanders.forEach((expanderEl) => {
                    let tab = expanderEl;
                    if (tab.contentEl.id !== this.contentEl.id)
                        tab.close();


                });
            }
            // Add public open and close methods for convenience
            open() {
            this.toggle(true);
            }
            close() {
            this.toggle(false);
            }

        }

        // init expanders
        const expanders = document.querySelectorAll('.cks-expand-button');
        expanders.forEach((expanderEl) => {
            var tab = new TabExpander(expanderEl);
            tabExpanders.push(tab);
        });
	}
