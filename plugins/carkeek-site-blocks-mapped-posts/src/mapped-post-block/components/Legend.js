import iconsvg from "../../resources/marker";

const Legend = (props) => {
	const { items, interactive = false, activeFilters = [], onFilterChange } = props;
	const iconHtml = `${iconsvg.marker}`;

	const handleClick = (slug) => {
		if (!interactive || !onFilterChange) return;
		onFilterChange(slug);
	};

	return (
		<div className="leaflet-top leaflet-left">
			<div className="leaflet-control leaflet-bar map-legend">
			<ul className="map-legend-list no-bullets">
				{items && items.map( (item, index) => {
					const isActive = interactive && activeFilters.includes(item.slug);
					return (
						<li
							key={index}
							className={`map-legend-item${interactive ? ' map-legend-item--interactive' : ''}${isActive ? ' map-legend-item--active' : ''}`}
							onClick={interactive ? () => handleClick(item.slug) : undefined}
							role={interactive ? 'button' : undefined}
							tabIndex={interactive ? 0 : undefined}
							onKeyDown={interactive ? (e) => { if (e.key === 'Enter' || e.key === ' ') handleClick(item.slug); } : undefined}
							aria-pressed={interactive ? isActive : undefined}
						>
							<span className={`map-legend-icon icon-${item.slug}`} aria-hidden="true" dangerouslySetInnerHTML={{ __html: iconHtml }}></span>
							<span className="map-legend-label" dangerouslySetInnerHTML={{ __html: item.label }}></span>
						</li>
					);
				})}
			</ul>
			</div>
		</div>
	)
}
export default Legend;