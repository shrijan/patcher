import { useContext, useEffect } from 'react'
import { QuestionContext } from '../Question'

export default function Select({name, type, placeholder}) {
  const {inputData, setInputData, search, searchData} = useContext(QuestionContext)

  useEffect(() => {
    search(type)
  }, [type])

  const typeSearchData = searchData[type]

  const update = item => {
    const newInputData = {
      ...inputData,
      [name]: {
        id: item[0],
        title: item[1].title
      }
    }
    if (inputData.type == 'question') {
      newInputData.description = item[1].description
    }
    setInputData(newInputData)
  }
  useEffect(() => {
    if (!inputData[name] && typeSearchData?.length) {
      update(typeSearchData[0])
    }
  }, [inputData, name, typeSearchData])

  return <select
    onChange={event => {
      const item = typeSearchData.find(item => item[0] == event.target.value)
      update(item)
    }}
  >
    {typeSearchData?.length ? typeSearchData.map(item => <option value={item[0]} selected={item[0] == inputData[name]?.id}>
      {item[1].title}
    </option>) : <option selected disabled>Step</option>}
  </select>
}
