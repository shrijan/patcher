import './App.css'
import FolderList from './components/FolderList.jsx'
import MediaGrid from './components/MediaGrid.jsx'
import { useState } from 'react'
import mediaData from './data/mediaData.js'
import ClearFiltersButton from './components/ClearFiltersButton.jsx'

export default function App({ folders }) {
  const [currentFolder, setCurrentFolder] = useState('root')
  const [folderFilterExpression, setFolderFilterExpression] = useState([])
  const [gridFilterExpression, setGridFilterExpression] = useState([])

  const dataSource = mediaData({ folderId: currentFolder })

  return (
    <>
      <ClearFiltersButton
        setFolderFilterExpression={setFolderFilterExpression}
        setGridFilterExpression={setGridFilterExpression}
      />
      <div className={'media-folder-page-wrapper'}>
        <div className={'media-folder-page-folders'}>
          <FolderList
            currentFolder={currentFolder}
            dataSource={dataSource}
            folderFilterExpression={folderFilterExpression}
            folders={folders}
            setCurrentFolder={setCurrentFolder}
            setFolderFilterExpression={setFolderFilterExpression}
          />
        </div>
        <div className={'media-folder-page-grid'}>
          <MediaGrid
            dataSource={dataSource}
            gridFilterExpression={gridFilterExpression}
            setGridFilterExpression={setGridFilterExpression}
          />
        </div>
      </div>
    </>
  )
}
