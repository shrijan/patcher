import { createContext, useContext, useMemo, useState } from 'react'
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core'
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
  useSortable
} from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import { ModalContext } from '../App'
import Answer from './Answer'
import Autocomplete from './controls/Autocomplete'
import Select from './controls/Select'
import { RichTextWithTooltip, Tooltip } from '../Tooltip'

export const QuestionContext = createContext({
  inputData: {},
  setInputData: () => {},
  setResponse: () => {},
  admin: false,
  data: {},
  save: () => {},
  selectedAnswerId: undefined,
  searchData: {},
  search: () => {},
  setFetched: () => {}
})

export function Input({className, name, placeholder}) {
  const {inputData, setInputData} = useContext(QuestionContext)
  return <input
    type="text"
    className={'nsw-form__input'+(className ? ' '+className : '')}
    value={inputData[name]}
    {...{placeholder}}
    onChange={event => {
      setInputData({
        ...inputData,
        [name]: event.target.value
      })
    }}
  />
}

export function Button({disabled, onClick, icon, text}) {
  return <button className="nsw-icon-button" type="button" {...{disabled, onClick}}>
    <span className="material-icons nsw-material-icons nsw-material-icons--20" focusable="false" aria-hidden="true">{icon}</span>
    <span className="sr-only">{text}</span>
  </button>
}

export const Edit = ({onClick}) => <Button {...{onClick}} icon="edit" text="Edit" />

export function SaveCancel({disabled, onSave, onCancel}) {
  const {setInputData} = useContext(QuestionContext)
  return <>
    <Button
      {...{disabled}}
      onClick={() => {
        onSave()
        setInputData(undefined)
      }}
      icon="check"
      text="Save"
    />
    <Button
      onClick={() => {
        setInputData(undefined)
        if (onCancel) {
          onCancel()
        }
      }}
      icon="close"
      text="Close"
    />
  </>
}

const SortableItemContext = createContext({
  attributes: {},
  listeners: undefined,
  ref() {}
});

function SortableHandle() {
  const { attributes, listeners, ref } = useContext(SortableItemContext)

  return <button className="nsw-icon-button" type="button" {...attributes} {...listeners} ref={ref}>
    <span className="material-icons nsw-material-icons nsw-material-icons--20" focusable="false" aria-hidden="true">drag_handle</span>
    <span className="sr-only">Move</span>
  </button>
}

function SortableItem({id, children}) {
  const {attributes, listeners, setNodeRef, setActivatorNodeRef, transform, transition} = useSortable({id})
  const context = useMemo(
    () => ({
      attributes,
      listeners,
      ref: setActivatorNodeRef
    }),
    [attributes, listeners, setActivatorNodeRef]
  )
  return <SortableItemContext.Provider value={context}>
    <div
      className="nsw-m-top-sm nsw-display-flex nsw-align-items-start"
      ref={setNodeRef}
      style={{
        transform: transform ? 'translate('+transform.x.toString()+'px, '+transform.y.toString()+'px)' : undefined,
        transition
      }}
    >
      <SortableHandle />
      {children}
    </div>
  </SortableItemContext.Provider>
}

function Info({content}) {
  if (!content) {
    return
  }

  const [open, setOpen] = useState(false)
  return <span className="nsw-m-left-xs decisionToolTooltip">
    {open && <Tooltip onClose={() => setOpen(false)}>{content}</Tooltip>}
    <button type="button" className="nsw-icon-button" onClick={() => setOpen(true)}>
      <span className="sr-only">Click enter to open tooltip</span>
      <span className="material-icons nsw-material-icons nsw-material-icons--20" focusable="false" aria-hidden="true">info</span>
    </button>
  </span>
}

export function Question({step, admin, disabled, subsequentStage, selectedAnswerId, data, setData, setResponse}) {
  const {setChanged} = useContext(ModalContext)
  const [inputData, setInputData] = useState()
  const [searchData, setSearchData] = useState({})
  const [fetched, setFetched] = useState([])
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  )

  const search = type => {
    if (fetched.includes(type)) {
      return
    }
    setFetched([...fetched, type])

    fetch('/decisionTool/search?id='+data.id+'&type='+type).then(response => response.json()).then(json => {
      setSearchData(previousSearchData => {
        return {
          ...previousSearchData,
          [type]: Object.entries(json)
        }
      })
    })
  }

  const save = json => {
    json = {...json}
    delete json.type

    const newAnswers = (json.answers || []).map(answer => {
      const newAnswer = {...answer}
      if (newAnswer.newQuestion) {
        newAnswer.link = {
          title: newAnswer.newQuestion
        }
        delete newAnswer.newStep
        delete newAnswer.newQuestion
      }
      return newAnswer
    })
    setData({
      ...json,
      answers: newAnswers
    })
    if (json.answers?.some(answer => !answer.text)) {
      /* There is a new answer that needs to be stored in data,
      so that new answers can be sortable, but it doesn't need to be saved to the database */
      return
    }
    setChanged(true)
    const answers = (json.answers || []).filter(answer => answer.text).map(answer => {
      return {
        ...answer,
        type: undefined,
        sameStage: undefined,
        link: answer.link?.id,
        newStep: answer.newStep?.id
      }
    })
    fetch('/decisionTool/question', {
      method: 'POST',
      body: JSON.stringify({
        ...json,
        step: json.step?.id,
        answers: answers.length ? answers : undefined
      })
    }).then(response => response.json()).then(newJson => {
      if (newJson.answerId || newJson.questionId) {
        if (newJson.answerId) {
          const index = newAnswers.findIndex(answer => !answer.id)
          newAnswers[index] = {
            ...newAnswers[index],
            id: newJson.answerId
          }
        }
        if (newJson.questionId) {
          const index = newAnswers.findIndex(answer => answer.link && !answer.link.id)
          newAnswers[index] = {
            ...newAnswers[index],
            link: {
              ...newAnswers[index].link,
              id: newJson.questionId
            }
          }

          setFetched(previousFetched => previousFetched.filter(item => item != 'question'))
        }
        setData({
          ...json,
          answers: newAnswers
        })
      }
    })
  }

  return <QuestionContext.Provider value={{inputData, setInputData, setResponse, admin, data, save, selectedAnswerId, search, searchData, setFetched}}>
    {inputData?.type == 'question' ? <>
      <div className="nsw-display-flex questionInput">
        {!subsequentStage && <h2>{step}.</h2>}
        <Select name="step" type="step" placeholder="Step" />
      </div>
    </> : (!subsequentStage && <>
      <div className="decisionToolTitle">
        <h2>{step}. {data.step?.title}</h2>
      </div>
      {data.step?.description && <div dangerouslySetInnerHTML={{__html: data.step?.description}} />}
    </>)}
    <div className={'nsw-form nsw-m-top-'+(subsequentStage ? 'lg' : 'sm')}>
      <div className="nsw-form__group">
        <fieldset className="nsw-form__fieldset">
          {inputData?.type == 'question' ? <>
            <Input name="title" placeholder="Title" />
            <div className="nsw-m-y-xs">
              <small>Question</small>
              {data.question[0] != '<' ? <Input name="question" placeholder="Question" /> : <div
                onClick={() => {
                  window.open('/node/'+data.id+'/edit', '_blank')
                }}
              >
                <RichTextWithTooltip html={data.question} />
              </div>}
            </div>
            <div className="nsw-m-y-xs">
              <small>Response Tracker Title</small>
              <Input name="responseTrackerTitle" />
            </div>
            <SaveCancel
              disabled={!inputData.step?.id || !inputData.question || !inputData.responseTrackerTitle}
              onSave={() => {
                save(inputData)
              }}
            />
          </> : (admin ? <>
            {data.title && <div className="nsw-text-bold">({data.title})</div>}
            <div className="nsw-display-flex nsw-align-items-center">
              <div>
                <RichTextWithTooltip html={data.question} />
              </div>
              <Edit onClick={() => {
                setInputData({
                  ...data,
                  type: 'question'
                })
              }} />
            </div>
          </> : <legend>
            <span className="nsw-form__legend nsw-display-flex">
              <div>
                <RichTextWithTooltip html={data.question} />
              </div>
              <Info content={data.tooltip} />
            </span>
          </legend>)}
          {(admin && data.answers?.length > 1) ? <DndContext
            {...{sensors}}
            collisionDetection={closestCenter}
            onDragEnd={({active, over}) => {
              if (active.id !== over.id) {
                const oldIndex = data.answers.findIndex(answer => answer.id == active.id)
                const newIndex = data.answers.findIndex(answer => answer.id == over.id)
                save({
                  ...data,
                  answers: arrayMove(data.answers, oldIndex, newIndex)
                })
              }
            }}
          >
            <SortableContext
              items={data.answers}
              strategy={verticalListSortingStrategy}
            >
              {data.answers.map((answer, i) => <SortableItem key={answer.id} id={answer.id}>
                <strong className="number">{i+1}.</strong>
                <Answer {...{answer, disabled}} />
              </SortableItem>)}
            </SortableContext>
          </DndContext> : data.answers?.map((answer, i) => <div className="nsw-display-flex nsw-m-top-sm">
            <strong className="number">{admin && (i+1).toString()+'.'}</strong>
            <Answer key={answer.id} {...{answer, disabled}} />
          </div>)}
          {admin && (!inputData || inputData.id) && <Button onClick={() => {
            const answers = data.answers ? [...data.answers] : []
            const answer = {
              id: '',
              type: 'answer'
            }
            setInputData(answer)
            answers.push(answer)
            save({
              ...data,
              answers
            })
          }} icon="add" text="Add" />}
        </fieldset>
      </div>
    </div>
  </QuestionContext.Provider>
}
