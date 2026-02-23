import { useContext, useEffect, useState } from 'react'
import Autocomplete from './controls/Autocomplete'
import Select from './controls/Select'
import { QuestionContext, Button, Edit, Input, SaveCancel } from './Question'
import { RichTextWithTooltip } from '../Tooltip'

export default function Answer({answer, disabled}) {
  const {inputData, setInputData, setResponse, admin, data, save, selectedAnswerId, setFetched} = useContext(QuestionContext)

  const selectResponse = () => {
    const plainText = document.createElement('div')
    plainText.innerHTML = answer.text
    plainText.querySelectorAll('.nsw-toggletip__element, nsw-tooltip__element').forEach(tooltip => tooltip.remove())
    const text = plainText.innerText
    setResponse({
      answerId: answer.id,
      linkId: answer.link?.id,
      linkUrl: answer.link?.url,
      sameStage: answer.sameStage,
      confirmationPage: answer.confirmationPage,
      title: data.responseTrackerTitle,
      text
    })
  }
  useEffect(() => {
    if (selectedAnswerId == answer.id) {
      selectResponse()
    }
  }, [answer, selectedAnswerId])

  let leadsTo
  let editing
  if (admin) {
    const [creatingNew, setCreatingNew] = useState(false)
    const [deletePending, setDeletePending] = useState(false)
    editing = inputData?.type == 'answer' && inputData.id == answer.id
    if (editing) {
      const select = <select
        className="nsw-m-xs"
        onChange={event => {
          setCreatingNew(false)
          setInputData({
            ...inputData,
            link: undefined,
            confirmationPage: event.target.value == 'confirmationPage'
          })
        }}
      >
        {Object.entries({
          question: 'Question',
          confirmationPage: 'Confirmation page'
        }).map(([value, text]) => <option {...{value}} selected={value == 'confirmationPage' && inputData.confirmationPage}>{text}</option>)}
      </select>

      const saveCancel = <SaveCancel
        disabled={!inputData.text || (creatingNew && (!inputData.newStep?.id || !inputData.newQuestion || !inputData.newResponseTrackerTitle))}
        onSave={() => {
          setCreatingNew(false)
          const answers = [...data.answers]
          const index = answers.findIndex(findAnswer => findAnswer.id == answer.id)
          answers[index] = inputData
          save({
            ...data,
            answers
          })
        }}
        onCancel={() => {
          setCreatingNew(false)
          if (!inputData.id) {
            save({
              ...data,
              answers: data.answers.filter(filterAnswer => filterAnswer.id != answer.id)
            })
          }
        }}
      />

      leadsTo = creatingNew ? <>
        <div className="nsw-small nsw-display-flex nsw-align-items-center">
          Leads to
          {select}
          <strong>New</strong>
        </div>
        <div className="nsw-small nsw-display-flex nsw-align-items-center">
          <strong className="nsw-m-right-xs">Step</strong>
          <Select name="newStep" type="step" placeholder="Step" />
        </div>
        {Object.entries({
          'newQuestion': 'Question',
          'newResponseTrackerTitle': 'Response Tracker Title'
        }).map(([field, label]) => <div className="nsw-small nsw-display-flex nsw-align-items-center nsw-m-top-xs">
          <strong className="nsw-m-right-xs nsw-text-nowrap">{label}</strong>
          <Input name={field} className="small" />
        </div>)}
        {saveCancel}
      </> : <div className="nsw-small nsw-display-flex nsw-align-items-center">
        Leads to
        {select}
        <div className="nsw-m-right-xs">
          <Autocomplete name="link" type={inputData.confirmationPage ? 'confirmationPage' : 'question'} />
        </div>
        {inputData.confirmationPage ? <a
          href="/node/add/page"
          target="_blank"
          onClick={() => {
            setFetched(previousFetched => previousFetched.filter(item => item != inputData.type))
          }}
        >Create new</a> : <a
          href="#"
          onClick={event => {
            event.preventDefault()
            setCreatingNew(true)
            setInputData({
              ...inputData,
              link: undefined
            })
          }}
        >Create new</a>}
        {saveCancel}
      </div>
    } else {
      leadsTo = <div className="nsw-small nsw-display-flex nsw-align-items-center">
        Leads to
        <div className="nsw-m-x-xs">
          {answer.link ? <a href={'/node/'+answer.link.id+'/edit'} target="_blank">{answer.link.title}</a> : <em>None</em>}
        </div>
        <Edit onClick={() => {
          setInputData({
            ...answer,
            type: 'answer'
          })
        }} />
        {deletePending && <span className="nsw-m-x-xs">Are you sure you would like to remove this answer?</span>}
        <Button onClick={() => {
          if (deletePending) {
            setDeletePending(false)
            if (selectedAnswerId == answer.id) {
              setResponse()
            }
            save({
              ...data,
              answers: data.answers.filter(filterAnswer => filterAnswer.id != answer.id)
            })
          } else {
            setDeletePending(true)
          }
        }} icon="delete" text="Delete" />
      </div>
    }
  }
  return <div className="nsw-position-relative answer">
    <input
      className="nsw-form__radio-input"
      type="radio"
      name={'form-radio-'+answer.id}
      id={'form-radio-'+data.id+'-'+answer.id}
      checked={selectedAnswerId == answer.id}
      {...{disabled}}
      onChange={selectResponse}
    />
    {(editing && answer.text?.[0] != '<') ? <Input className="small" name="text" /> : <label
      className="nsw-form__radio-label"
      htmlFor={'form-radio-'+data.id+'-'+answer.id}
      onClick={editing ? (() => {
        const index = data.answers.findIndex(dataAnswer => dataAnswer.id == answer.id)
        window.open('/node/'+data.id+'/edit#field-answers-'+index.toString()+'-item-wrapper', '_blank')
      }) : undefined}
    >
      <RichTextWithTooltip html={answer.text} />
    </label>}
    {leadsTo}
  </div>
}
