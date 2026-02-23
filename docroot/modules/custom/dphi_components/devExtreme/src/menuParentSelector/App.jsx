import React, { useCallback, useRef, useState } from 'react'
import DropDownBox from 'devextreme-react/drop-down-box'
import TreeView from 'devextreme-react/tree-view'
import './App.css'

const externalDropdown = document.getElementById('edit-menu-menu-parent')

export default function App({ menuLinks, selectedParent }) {
  const dropDownRef = useRef(null)
  const [treeBoxValue, setTreeBoxValue] = useState(selectedParent)
  const [isBoxOpen, setIsBoxOpen] = useState(false)

  const treeViewItemSelectionChanged = useCallback(e => {
    const selectedNodes = e.component.getSelectedNodeKeys()
    setTreeBoxValue(selectedNodes)
    externalDropdown.value = selectedNodes
  }, [])

  const treeViewOnContentReady = useCallback(
    e => {
      e.component.selectItem(treeBoxValue)
    },
    [treeBoxValue],
  )

  const onTreeItemClick = useCallback(() => {
    setIsBoxOpen(false)
  }, [])

  const treeViewRender = useCallback(
    () => (
      <TreeView
        dataStructure={'plain'}
        items={menuLinks}
        searchEnabled={true}
        searchMode="contains"
        selectByClick={true}
        selectionMode="single"
        onContentReady={treeViewOnContentReady}
        onItemClick={onTreeItemClick}
        onItemSelectionChanged={treeViewItemSelectionChanged}
      />
    ),
    [
      dropDownRef,
      treeViewOnContentReady,
      onTreeItemClick,
      treeViewItemSelectionChanged,
    ],
  )

  const syncTreeViewSelection = useCallback(e => {
    setTreeBoxValue(e.value)
    if (!dropDownRef.current) {
      return
    }
    if (!e.value) {
      dropDownRef.current.instance.unselectAll()
    } else {
      dropDownRef.current.instance.selectItem(e.value)
    }
  }, [])

  const onTreeBoxOpened = useCallback(e => {
    if (e.name === 'opened') {
      setIsBoxOpen(e.value)
    }
  }, [])

  return (
    <>
      <DropDownBox
        contentRender={treeViewRender}
        displayExpr="text"
        dropDownOptions={{
          wrapperAttr: { class: 'menu-parent-selector-dropdown-wrapper' },
        }}
        items={menuLinks}
        onValueChanged={syncTreeViewSelection}
        onOptionChanged={onTreeBoxOpened}
        opened={isBoxOpen}
        placeholder="Select a menu parent"
        ref={dropDownRef}
        showClearButton={false}
        valueExpr="id"
        value={treeBoxValue}
      />
      <div
        id="edit-menu-hierarchy--description"
        className="form-item__description"
      >
        This widget can be used to easily search for and select the desired menu
        parent from the Parent link dropdown. Changing the Parent link dropdown
        will not affect this widget. The Parent link dropdown is the one that
        gets submitted.
      </div>
    </>
  )
}
