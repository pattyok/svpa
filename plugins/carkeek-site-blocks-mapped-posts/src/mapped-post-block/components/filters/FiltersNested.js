var _ = require('lodash');
var classnames = require('classnames');
import { isArray } from "lodash";
import React, { useState } from "react";
import useCollapse from 'react-collapsed';


const ExpandCollapse = ({ cat, onExpand, onParentClick, onChildClick, selected, myChildren }) => {
	const [isExpanded, setExpanded] = useState(false)
    const { getCollapseProps, getToggleProps } = useCollapse({ isExpanded });
    const id = parseInt(cat.id);
    const activate = () => {
		setExpanded((prevExpanded) => !prevExpanded);
        //onExpand(id, myChildren);
    }
    const btnClass = classnames({
        'btn-archive-map-selector' : true,
        'btn-active' : isExpanded
    })

    return (
        <>
        <button {...getToggleProps({
          onClick: () => activate(),
        })} className={btnClass} dangerouslySetInnerHTML={{__html: cat.name}}></button>

        <section {...getCollapseProps()}><ul>
            <Checkbox
                selected={_.includes(selected, id) && selected.length ==1}
                label= {'All ' + cat.name}
                value={id}
                onChange={onParentClick}
                catChildren={myChildren}
            />
            <CategoryList
            options={cat.children}
            selectedOptions={selected}
            onChange={onChildClick}
            level="1"
            /></ul>
        </section>
        </>
    )
}

const Checkbox = ({ selected, label, value, onChange, catChildren, parent }) => {
    const handleClick = (e) => {
        onChange(e, parent, catChildren);
    }
    return (
        <li><input checked={selected} type="checkbox" onChange={(e) => handleClick(e)} value={value} id={`cat-${value}`}/><label htmlFor={`cat-${value}`} dangerouslySetInnerHTML={{__html: label}}></label></li>
    )
  }

    const CategoryList = ({options, selectedOptions, onChange, level}) => {

        const findAllChildren = (cat, children) => {

            if (isArray(cat.children) && cat.children.length > 0) {
              const myChildren = _.map(cat.children, 'id');
              let myAncestors = [];
              cat.children.forEach( (item) => {
                  myAncestors = _.union(
                    findAllChildren(item, _.union(myChildren, children)), myAncestors
                  )
              })
              return _.union(myAncestors, children);


            } else {
              return children;
            }
        }
        //the parent is the the select all choice it is toggled of when child items are selected
        const handleParentClicked = (e, catChildren) => {
            if (e.target && e.target.type === 'checkbox'){
                const selectedOptionId = parseInt(e.target.value);
                let newSelectedOptions = _.cloneDeep(selectedOptions);
                // is currently selected
                if(_.includes(selectedOptions, selectedOptionId)){
                    // remove selected value from options list
                    _.pull(newSelectedOptions, selectedOptionId);
                    // remove children from selected list
                    _.pullAll(newSelectedOptions, catChildren);

                } else { // is not currently selected
                // this should be the only item in the list
                    newSelectedOptions = [selectedOptionId];
                }
                // call onChange function given by parent
                onChange(newSelectedOptions)
            }
          }
        const handleCheckboxClicked = (e, parent, catChildren) => {
            if (e.target && e.target.type === 'checkbox'){
                const selectedOptionId = parseInt(e.target.value);
                const parentId = parseInt(parent);
                let newSelectedOptions = _.cloneDeep(selectedOptions);
                // is currently selected
                if(_.includes(selectedOptions, selectedOptionId)){
                    // remove selected value from options list
                    _.pull(newSelectedOptions, selectedOptionId);
                    // remove children from selected list
                    _.pullAll(newSelectedOptions, catChildren);
                } else { // is not currently selected
                // Add selected key to optionsList
                    //newSelectedOptions.push(selectedOptionId);
                    newSelectedOptions = _.concat(selectedOptions, selectedOptionId ,catChildren);
                }

                //if parent is currently selected, we remove if
                if( _.includes(selectedOptions, parentId) ) {
                    _.pull(newSelectedOptions, parentId);
                }
                //if the options are empty we add the parent only
                if (newSelectedOptions.length == 0) {
                    newSelectedOptions.push(parentId);
                }
                // call onChange function given by parent
                onChange(newSelectedOptions)
            }
          }
        //on clicking the header we either add the item to the list or not
        const activateHeader = (id, myChildren) => {
            let newSelections = [];
            if (! showChildList(id, myChildren)) {
                newSelections = [parseInt(id)];
            }
            onChange(newSelections);
        }
        //Show child list if parent is selected or if any child is selected
        const showChildList = (myId, myChildren) => {
            let show = false;
            if (_.includes(selectedOptions, myId)) {
                show = true;
            } else {
                //are my children selected?
                show = myChildren.some(r=> selectedOptions.indexOf(r) >= 0);
            }
            return show;

        }

        return (
            <>
                {options.map( (cat) => {
                    const id = parseInt(cat.id);
                    const myChildren = findAllChildren(cat, []);
                    const isActive = showChildList(id, myChildren);
                    return (
                        <>
                        {level == 0 &&
                            <ExpandCollapse
                                cat={cat}
                                isExpanded={isActive}
                                onExpand={activateHeader}
                                onParentClick={handleParentClicked}
                                onChildClick={onChange}
                                selected={selectedOptions}
                                myChildren={myChildren}
                            />
                        }
                        { (level > 0) &&
                            <Checkbox
                                selected={_.includes(selectedOptions, id)}
                                label={cat.name}
                                value={id}
                                parent={cat.parent}
                                onChange={handleCheckboxClicked}
                                catChildren={myChildren}
                            />
                        }

                        {(cat.children && cat.children.length > 0 && isActive && parseInt(level) > 1)&&
                            <ul>
                            <CategoryList
                            options={cat.children}
                            selectedOptions={selectedOptions}
                            onChange={onChange}
                            />
                            </ul>
                        }
                    </>
                )}
                )}
            </>
        )
    }

    const FilterList = (props) => {
        return (
            <div className="archive-filter-list">
                <CategoryList {...props} />
            </div>
        )
    }

export default FilterList;
