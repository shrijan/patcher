import ContentTreeList from './components/ContentTreeList.jsx'
import { useState } from 'react'
import MenuSelector from './components/MenuSelector.jsx'

export default function App({ contentTypes, menuNames, moderationStates }) {
  const [currentMenu, setCurrentMenu] = useState('')

  return (
    <>
      <MenuSelector
        currentMenu={currentMenu}
        menuNames={menuNames}
        setCurrentMenu={setCurrentMenu}
      />
      {currentMenu && (
        <ContentTreeList
          contentTypes={contentTypes}
          menuName={currentMenu}
          moderationStates={moderationStates}
        />
      )}
    </>
  )
}
