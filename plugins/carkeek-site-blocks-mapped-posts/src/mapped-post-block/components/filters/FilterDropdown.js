import React, { useRef, useEffect } from "react";
import Select from 'react-select';
import {decodeEntities} from "@wordpress/html-entities";

const FilterDropdown = ({ options, setSelectedOption, selected, title}) => {

	const filterId = title ? title.toLowerCase().replace(/\s+/g, '-') : "search-filter";
    /** convert locations for search */
    let searchOptions = [];
    if (options) {
    searchOptions = options.map(item => {
		let label = item.title.rendered;
		label = decodeEntities(label);
        return {
            value: item.id,
            label: label
        };
    }).sort((a, b) => { return a.label.localeCompare(b.label) });
    }


    const selectStyles = {
        option: (provided, state) => ({
            ...provided,
            color: state.isSelected ? '#fff' : '#3e4650',
            backgroundColor: state.isSelected ? '#406EA5' : '#fff',
            padding: 20,
        }),
        indicatorSeparator: () => ({
        }),
        menu: (provided) => ({
            ...provided,
            borderRadius: 0,
            marginTop: 0,
        }),
        control: (provided) => ({
            ...provided,
            borderRadius: 0,
            padding: 10,
            marginBottom: 20,
        }),
        }
    return (
        <div className="archive-search-list">
        <label for={`dropdown-${filterId}`}><span>{title}</span>
        <Select
            label={title}
			id={`dropdown-${filterId}`}
            value={selected}
            options={searchOptions}
            onChange={setSelectedOption}
            placeholder="Type to search..."
            styles={selectStyles}
			className="react-select-container"
			classNamePrefix="react-select"
        />
        </label>
        </div>
    );
};

export default FilterDropdown;
