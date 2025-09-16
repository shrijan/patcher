import TreeList, {
  Column,
  FilterRow,
  RowDragging,
  Selection,
  Sorting,
} from 'devextreme-react/tree-list'

export default function FolderList({
  currentFolder,
  dataSource,
  folderFilterExpression,
  folders,
  setCurrentFolder,
  setFolderFilterExpression,
}) {
  const onAdd = e => {
    if (!e.dropInsideItem) {
      return
    }
    const folder = currentFolder
    const mediaId = e.itemData.id
    const targetFolderId = e.component.getKeyByRowIndex(e.toIndex)
    dataSource.update(mediaId, { mediaId, targetFolderId })
    setTimeout(() => {
      setCurrentFolder('')
    }, 300)
    setTimeout(() => {
      setCurrentFolder(folder)
    }, 500)
  }

  const onDragStart = e => {
    // We don't want the folders themselves being dragged, they're just targets for the media items.
    e.cancel = true
  }

  const onFilterValueChange = e => {
    setFolderFilterExpression(e)
  }
  const onSelectionChanged = e => {
    setCurrentFolder(e.selectedRowsData[0].id)
  }

  return (
    <TreeList
      autoExpandAll={true}
      dataSource={folders}
      dataStructure={'plain'}
      filterSyncEnabled={true}
      filterValue={folderFilterExpression}
      onFilterValueChange={onFilterValueChange}
      onSelectionChanged={onSelectionChanged}
    >
      <FilterRow visible={true} />

      <RowDragging
        allowDropInsideItem={true}
        allowReordering={false}
        group={'mediaHierarchy'}
        onDragStart={onDragStart}
        onAdd={onAdd}
        showDragIcons={false}
      />

      <Selection mode={'single'} />

      <Sorting mode={'none'} />

      <Column
        dataField={'weight'}
        dataType={'number'}
        sortOrder={'asc'}
        visible={false}
      />

      <Column dataField={'folder'} />
    </TreeList>
  )
}
