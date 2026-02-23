import { Button } from 'devextreme-react/button'
import { useCallback } from 'react'

export default function ClearFiltersButton({
  setFolderFilterExpression,
  setGridFilterExpression,
}) {
  const onClick = useCallback(() => {
    setFolderFilterExpression([])
    setGridFilterExpression([])
  })
  return (
    <Button onClick={onClick} text={'Clear all filters'} type={'default'} />
  )
}
