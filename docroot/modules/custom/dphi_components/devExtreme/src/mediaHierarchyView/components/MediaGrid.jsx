import DataGrid, {
  Button,
  Column,
  FilterRow,
  StateStoring,
  RowDragging,
  Pager,
} from 'devextreme-react/data-grid'

export default function MediaGrid({
  dataSource,
  gridFilterExpression,
  setGridFilterExpression,
}) {
  const onFilterValueChange = e => {
    setGridFilterExpression(e)
  }

  return (
    <DataGrid
      allowColumnResizing={true}
      columnAutoWidth={true}
      columnResizingMode={'widget'}
      dataSource={dataSource}
      filterSyncEnabled={true}
      filterValue={gridFilterExpression}
      onFilterValueChange={onFilterValueChange}
      rowAlternationEnabled={true}
    >
      <FilterRow visible={true} />

      <Pager
        allowedPageSizes={[10, 25, 50, 100]}
        displayMode={'adaptive'}
        showInfo={true}
        showPageSizeSelector={true}
        visible={true}
      />

      <RowDragging
        allowDropInsideItem={true}
        allowReordering={false}
        group={'mediaHierarchy'}
      />

      <StateStoring
        enabled={true}
        storageKey={'mediaHierarchyPageState'}
        type="localStorage"
      />

      <Column
        caption={'Title'}
        dataField={'title'}
        cellRender={row => {
          return (
            // eslint-disable-next-line react/jsx-no-target-blank
            <a href={row.data.view} target={'_blank'}>
              {row.data.title}
            </a>
          )
        }}
      />

      <Column caption={'Type'} dataField={'type'} />

      <Column caption={'Author'} dataField={'author'} />

      <Column
        caption={'Published'}
        dataField={'published'}
        dataType={'boolean'}
        calculateCellValue={rowData => rowData.published ?? false}
      />

      <Column
        caption={'Last Updated'}
        dataField={'lastUpdated'}
        dataType={'datetime'}
        format={'d/M/y, h:m a'}
        calculateCellValue={rowData => {
          const lastUpdated = rowData.lastUpdated
          if (!lastUpdated) {
            return null
          }
          return new Date(lastUpdated * 1000)
        }}
      />

      <Column type={'buttons'} alignment={'left'} caption={'Actions'}>
        <Button
          hint="Edit media"
          icon="edit"
          onClick={e => window.open(e.row.data.edit, '_blank')}
        />
      </Column>
    </DataGrid>
  )
}
