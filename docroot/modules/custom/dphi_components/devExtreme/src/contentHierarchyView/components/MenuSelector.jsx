import { SelectBox } from 'devextreme-react'
import { useCallback, useEffect } from 'react'

export default function MenuSelector({
  currentMenu,
  menuNames,
  setCurrentMenu,
}) {
  useEffect(() => {
    const storedMenu = localStorage.getItem('contentHierarchyMenuChoice')
    console.log(menuNames)
    if (storedMenu && menuNames.some(menu => menu.id === storedMenu)) {
      return setCurrentMenu(storedMenu)
    }

    const defaultMenu = 'main'
    if (menuNames.includes(defaultMenu)) {
      return setCurrentMenu(defaultMenu)
    }
  }, [])

  const onValueChanged = useCallback(e => {
    setCurrentMenu(e.value)
    localStorage.setItem('contentHierarchyMenuChoice', e.value)
  }, [])

  return (
    <SelectBox
      displayExpr={'label'}
      items={menuNames}
      label={'Choose a menu'}
      onValueChanged={onValueChanged}
      value={currentMenu}
      valueExpr={'id'}
      width={'240px'}
    />
  )
}
