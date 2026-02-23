import { useContext, useEffect, useMemo } from 'react'
import { QuestionContext } from '../Question'

export default function Autocomplete({name, type, className, placeholder}) {
  const {inputData, setInputData, search, searchData} = useContext(QuestionContext)

  const typeSearchData = searchData[type]
  const autocomplete = useMemo(() => {
    if (!inputData) {
      return []
    }
    return (typeSearchData || []).filter(item => !inputData[name] || item[1].title.toLowerCase().includes(inputData[name].title.toLowerCase())).sort((a, b) => b[1].title.localeCompare(a[1].title)).slice(0, 10)
  }, [typeSearchData, inputData])

  const selectItem = item => {
    setInputData({
      ...inputData,
      [name]: {
        id: item[0],
        title: item[1].title
      }
    })
  }

  useEffect(() => {
    if (inputData?.[name]?.title && inputData[name].id === undefined) {
      const link = autocomplete.find(item => item[1].title == inputData[name].title)
      if (link) {
        selectItem(link)
      }
    }
  }, [autocomplete, inputData])

  return <div className="autocomplete">
    <input
      type="text"
      value={inputData[name] ? inputData[name].title : ''}
      {...{className, placeholder}}
      onFocus={() => search(type)}
      onChange={event => {
        const title = event.target.value
        setInputData({
          ...inputData,
          [name]: title ? {title} : undefined
        })
      }}
    />
    <ul className="ui-autocomplete">
      {autocomplete.map(item => <li className="ui-menu-item" onMouseDown={() => {
        selectItem(item)
      }}>{item[1].title}</li>)}
    </ul>
  </div>
}
