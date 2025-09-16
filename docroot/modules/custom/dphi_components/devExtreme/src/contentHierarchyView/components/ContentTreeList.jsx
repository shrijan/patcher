import { useCallback, useRef } from 'react'
import TreeList, {
  Button,
  Column,
  FilterRow,
  Lookup,
  RowDragging,
  Sorting,
  StateStoring,
} from 'devextreme-react/tree-list'
import nodeMenuData from '../data/nodeMenuData.js'
import 'devextreme-react/sortable'

export default function ContentTreeList({
  contentTypes,
  menuName,
  moderationStates,
}) {
  const treeList = useRef()
  const data = nodeMenuData(menuName)

  // Prevent drop outlines in inappropriate locations.
  const onDragChange = useCallback(e => {
    const visibleRows = e.component.getVisibleRows()
    const downDirection = e.fromIndex > e.toIndex
    const toIndex =
      downDirection || e.dropInsideItem ? e.toIndex : e.toIndex + 1
    if (toIndex >= visibleRows.length) {
      e.cancel = true
      return
    }

    const sourceNode = e.component.getNodeByKey(e.itemData.menuItem.id)
    const targetNode = toIndex >= 0 ? visibleRows[toIndex].node : (null ?? null)

    if (targetNode.data.menuItem.id === sourceNode.data.menuItem.id) {
      e.cancel = true
      return
    }
    if (
      e.dropInsideItem &&
      targetNode.data.menuItem.id === sourceNode.data.menuItem.parent
    ) {
      e.cancel = true
    }
  }, [])

  // Main drag-drop logic.
  const onReorder = useCallback(
    e => {
      const visibleRows = e.component.getVisibleRows()
      const downDirection = e.fromIndex > e.toIndex
      const toIndex =
        downDirection || e.dropInsideItem ? e.toIndex : e.toIndex + 1
      const targetData = toIndex >= 0 ? visibleRows[toIndex].node.data : null
      const parentId = e.dropInsideItem
        ? targetData.menuItem.id
        : targetData.menuItem.parent

      // Narrow down our working set to the level to be edited.
      let thisLevel = data.__rawData
        .filter(x => x.menuItem.parent === parentId)
        .sort((a, b) => a.menuItem.weight - b.menuItem.weight)
      const targetIndex = thisLevel.indexOf(targetData)
      let sourceData = e.itemData
      const sourceIndex = thisLevel.indexOf(sourceData)

      // If dropping on the same parent there's no change, abort.
      if (e.dropInsideItem && sourceIndex !== -1) {
        return
      }

      // Overwrite parent ID of moved item.
      sourceData.menuItem.parent = parentId

      // Build the array of menu items to submit.
      if (e.dropInsideItem) {
        thisLevel = [...thisLevel, sourceData]
      } else if (sourceIndex !== -1) {
        thisLevel.splice(sourceIndex, 1)
        const insertIndex = downDirection ? targetIndex : targetIndex - 1
        thisLevel.splice(insertIndex, 0, sourceData)
      } else {
        thisLevel.splice(targetIndex, 0, sourceData)
      }

      // Set the new menu item weights.
      let toProcess = []
      let weight =
        thisLevel[0].menuItem.weight <= -50 ? thisLevel[0].menuItem.weight : -50
      thisLevel.forEach(item => {
        item.menuItem.weight = weight
        toProcess.push(item.menuItem)
        weight++
      })

      // Send to backend.
      data
        .update(null, toProcess)
        .then(() => treeList.current.instance().refresh())
    },
    [data],
  )

  return (
    <TreeList
      allowColumnResizing={true}
      columnAutoWidth={true}
      columnResizingMode={'widget'}
      dataSource={data}
      dataStructure={'plain'}
      itemsExpr={'children'}
      parentIdExpr={'menuItem.parent'}
      ref={treeList}
      rootValue={0}
      rowAlternationEnabled={true}
    >
      <FilterRow visible={true}></FilterRow>

      <RowDragging
        onDragChange={onDragChange}
        onReorder={onReorder}
        allowDropInsideItem={true}
        allowReordering={true}
      />

      <Sorting mode={'none'} />

      <StateStoring
        enabled={true}
        storageKey={'contentHierarchyPageState'}
        type="localStorage"
      />

      <Column
        dataField={'menuItem.weight'}
        dataType={'number'}
        sortOrder={'asc'}
        visible={false}
      />

      <Column
        caption={'Menu Title'}
        dataField={'menuItem.title'}
        cellRender={row => {
          return (
            // eslint-disable-next-line react/jsx-no-target-blank
            <a href={row.data.menuItem.url} target={'_blank'}>
              {row.data.menuItem.title}
            </a>
          )
        }}
      />

      <Column caption={'Moderation State'} dataField={'node.moderationState'}>
        <Lookup
          dataSource={moderationStates}
          displayExpr={'label'}
          valueExpr={'id'}
        />
      </Column>

      <Column
        caption={'Menu Item Enabled'}
        dataField={'menuItem.enabled'}
        dataType={'boolean'}
        calculateCellValue={rowData => rowData.menuItem.enabled ?? false}
      />

      <Column caption={'Node Title'} dataField={'node.title'} />

      <Column caption={'Content Type'} dataField={'node.contentType'}>
        <Lookup
          dataSource={contentTypes}
          displayExpr={'label'}
          valueExpr={'id'}
        />
      </Column>

      <Column caption={'Author'} dataField={'node.author'} />

      <Column
        caption={'Last Updated'}
        dataField={'node.lastUpdated'}
        dataType={'datetime'}
        format={'d/M/y, h:m a'}
        calculateCellValue={rowData => {
          const lastUpdated = rowData.node.lastUpdated
          if (!lastUpdated) {
            return null
          }
          return new Date(lastUpdated * 1000)
        }}
      />

      <Column type={'buttons'} alignment={'left'} caption={'Actions'}>
        <Button
          hint="Edit menu item"
          icon="menu"
          onClick={e => window.open(e.row.data.menuItem.edit, '_blank')}
        />

        <Button
          hint="Edit content"
          icon="edit"
          onClick={e => window.open(e.row.data.node.edit, '_blank')}
          visible={e => Object.keys(e.row.data.node).length}
        />
      </Column>
    </TreeList>
  )
}
