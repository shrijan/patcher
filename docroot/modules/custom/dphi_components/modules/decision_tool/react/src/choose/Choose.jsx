import { useEffect, useMemo, useRef, useState } from 'react'
import { ModalHeader } from '../App'
import { Question } from './Question'
import ConfirmationPage from './ConfirmationPage'

export default function Choose({id, entryPageId, title, nestQuestions, progressIndicator, responseTracker, begin, beginText, skipIntro, admin}) {
  const minimumPosition = begin ? 0 : 1
  const [position, setPosition] = useState(0)
  const [hasMoved, setHasMoved] = useState(false)
  const [questions, setQuestions] = useState({})
  const [responses, setResponses] = useState([])
  const [loading, setLoading] = useState(skipIntro || !begin)
  const ref = useRef(null)
  let step

  // Determine which questions are visible
  let questionIds
  [step, questionIds] = useMemo(() => {
    let ids = []
    if (!position) {
      return [1, ids]
    }
    ids.push(id)
    let loopStep = 1
    for (let i=0; i<position-1; i++) {
      const response = responses[i]
      if (!response.sameStage) {
        loopStep += 1
      }
      if (!nestQuestions || !response.sameStage) {
        ids = []
      }
      ids.push(response.linkId)
    }
    return [loopStep, ids]
  }, [id, nestQuestions, position, responses])

  const currentQuestions = useMemo(() => questionIds.map(questionId => questions[questionId]).filter(question => question), [questionIds, questions])

  if (!admin) {
    useEffect(() => {
      const url = currentQuestions[currentQuestions.length-1]?.url
      if (url) {
        history.pushState({}, '', url)
      }
    }, [currentQuestions])
  }

  useEffect(() => {
    if (position > minimumPosition) {
      setHasMoved(true)
    }
  }, [position, minimumPosition])

  const topQuestion = questionIds[0]
  useEffect(() => {
    if (!hasMoved) {
      return
    }
    const firstItem = ref.current.querySelector('button, input')
    if (firstItem) {
      firstItem.focus()
    }
  }, [hasMoved, topQuestion])

  useEffect(() => {
    let offset = 0
    let element = ref.current
    while (element) {
      offset += element.offsetTop
      element = element.offsetParent
    }
    if (window.scrollY > offset) {
      // Page is scrolled further down than the start of the content
      // So scroll up
      window.scrollTo(0, offset)
    }
  }, [topQuestion])

  useEffect(() => {
    if (!loading) {
      return
    }

    const progress = () => {
      setLoading(false)
      setPosition(position + 1)
    }
    const missingId = !questions[id] ? id : responses.find(response => !questions[response.linkId])?.linkId
    if (missingId) {
      fetch('/decisionTool/question?id='+missingId).then(response => response.json()).then(json => {
        setQuestions(previousQuestions => {
          return {
            ...previousQuestions,
            [missingId]: {
              ...json,
              id: missingId
            }
          }
        })
        progress()
      })
    } else {
      progress()
    }
  }, [loading, responses, questions])

  let content
  if (!position && begin) {
    content = <div className="decisionToolContent" {...{ref}} dangerouslySetInnerHTML={{__html: begin}} />
  } else {
    let stepsLeft = 0
    for (let i = currentQuestions.length - 1; i != -1; i--) {
      const questionStepsLeft = currentQuestions[i]?.stepsLeft
      if (questionStepsLeft !== undefined) {
        stepsLeft = questionStepsLeft
        break
      }
    }
    const trackerResponses = responseTracker ? responses.slice(0, position).filter(response => !questionIds.includes(response.questionId)) : []
    content = <>
      {progressIndicator && Object.keys(questions).length != 0 && <div className="decisionToolProgress nsw-progress-indicator">
        <div className="nsw-progress-indicator__count">Step {step} of {step + stepsLeft}</div>
        <div className="nsw-progress-indicator__bar">
          {[...Array(step)].map(() => <div className="active" />)}
          {[...Array(stepsLeft)].map(() => <div />)}
        </div>
      </div>}
      <div className="decisionToolContent" {...{ref}}>
        {currentQuestions[0]?.confirmationPage ? <ConfirmationPage
          {...{step, admin}}
          id={questionIds[0]}
          data={currentQuestions[0]}
        /> : currentQuestions.map((currentQuestion, i) => <Question
          {...{step, admin}}
          key={currentQuestion.id}
          subsequentStage={i != 0}
          disabled={i != currentQuestions.length - 1 || loading}
          selectedAnswerId={responses.find(response => response.questionId == currentQuestion.id)?.answerId}
          data={currentQuestion}
          setData={newData => {
            setQuestions({
              ...questions,
              [currentQuestion.id]: newData
            })
          }}
          setResponse={newAnswer => {
            setResponses(previousResponses => {
              const newResponses = []
              for (let i=0; i<previousResponses.length; i++) {
                const response = previousResponses[i]
                if (response.questionId == currentQuestion.id) {
                  break
                }
                newResponses.push(response)
              }
              return newAnswer === undefined ? newResponses : [
                ...newResponses,
                {
                  ...newAnswer,
                  questionId: currentQuestion.id
                }
              ]
            })
          }}
        />)}
      </div>
      {trackerResponses.length != 0 && <div className="decisionToolTracker nsw-bg--info-light">
        <h3 className="nsw-h6">Response tracker</h3>
        <hr />
        <ul>
          {trackerResponses.map((response, i) => <li>
            <strong className="nsw-display-block">{response.title}</strong>
            <div dangerouslySetInnerHTML={{__html: response.text}} />
          </li>)}
        </ul>
      </div>}
    </>
  }
  let footer
  if (currentQuestions[0]?.confirmationPage) {
    footer = <div className="left">
      <button
        className="nsw-button nsw-button--dark-outline-solid"
        onClick={() => {
          setPosition(minimumPosition)
          setResponses([])
        }}
      >
        Start a new enquiry
      </button>
    </div>
  } else {
    const nextAnswer = step == 0 ? undefined : responses[position-1]

    let finish = nextAnswer?.confirmationPage
    if (!finish) {
      // If every possible answer leads to a confirmation page,
      // then set the text to 'Finish' to begin with
      const lastQuestion = currentQuestions[currentQuestions.length-1]
      finish = lastQuestion?.answers?.length && lastQuestion.answers.some(answer => answer.confirmationPage) && !lastQuestion.answers.some(answer => answer.link && !answer.confirmationPage)
    }
    const text = finish ? 'Finish' : ((!position && beginText) || 'Next')

    let next
    const disabled = (position && !nextAnswer?.linkId) || loading
    if (nextAnswer?.confirmationPage && !admin) {
      next = <form action={nextAnswer?.linkUrl} method="POST" onSubmit={event => {
        setTimeout(() => {
          // If the user goes 'Back', Drupal naturally prevents a form from being submitted twice
          // Allow it instead
          delete event.target.dataset.drupalFormSubmitLast
        }, 0)
      }}>
        <input type="hidden" name="decisionTool" value={JSON.stringify({
          id,
          entryPageId,
          step: step + 1,
          progressIndicator,
          tracker: responseTracker ? responses.map(response => {
            return {
              title: response.title,
              text: response.text
            }
          }) : undefined
        })} />
        <button
          type="submit"
          className="nsw-button nsw-button--dark"
          {...{disabled}}
        >
          {text}
        </button>
      </form>
    } else {
      next = <button
        className="nsw-button nsw-button--dark nsw-display-flex nsw-justify-content-center"
        {...{disabled}}
        onClick={event => {
          event.preventDefault()

          setLoading(true)
        }}
      >
        {loading && <span aria-hidden="true" className="nsw-loader__circle nsw-loader__circle--sm nsw-m-right-sm" />}
        {text}
      </button>
    }
    footer = <div className="buttons">
      {position > minimumPosition && <button
        className="nsw-button nsw-button--dark-outline-solid"
        disabled={loading}
        onClick={event => {
          event.preventDefault()

          setPosition(position - 1)
        }}
      >
        Back
      </button>}
      {next}
    </div>
  }
  footer = <div className="decisionToolFooter">{footer}</div>
  return admin ? <>
    <div className="nsw-dialog__container">
      <ModalHeader {...{title}} />
      <div className="nsw-dialog__content decisionToolGrid">
        {content}
      </div>
    </div>
    <div className="nsw-dialog__bottom">
      {footer}
    </div>
  </> : <>
    {content}
    {footer}
  </>
}
