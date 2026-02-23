import { createContext, useContext, useEffect, useRef, useState } from 'react'
import { disableBodyScroll, enableBodyScroll } from 'body-scroll-lock-upgrade'
import Flowchart from './Flowchart'
import Choose from './choose/Choose'
import './admin.scss'
import './main.scss'
import './confirmationPage.scss'

export const ModalContext = createContext({
  flowchart: false,
  setFlowchart: () => {},
  print: false,
  setPrint: () => {},
  setOpen: () => {},
  setChanged: () => {}
})

export function ModalHeader({title}) {
  const {flowchart, setFlowchart, setPrint, setOpen} = useContext(ModalContext)
  const [orphan, setOrphan] = useState(false)
  useEffect(() => {
    if (flowchart) {
      return
    }

    fetch('/decisionTool/orphan').then(response => response.json()).then(json => {
      setOrphan(json.present)
    })
  }, [flowchart])

  return <div className="nsw-dialog__top">
    <div className="nsw-dialog__title">
      <div className="nsw-display-flex nsw-justify-content-between">
        <h2 id="dialog-title">{title} Tool</h2>
        <div>
          {flowchart ? <button className="nsw-button nsw-button--dark nsw-m-right-md" onClick={event => {
            event.preventDefault()

            setPrint(true)
            setTimeout(() => {
              window.print()
              setTimeout(() => {
                setPrint(false)
              }, 0)
            }, 1)
          }}>Print</button> : (orphan && <a href="/admin/content?questions=orphan" target="_blank" className="nsw-button nsw-button--dark nsw-m-right-md">See orphan questions</a>)}
          <button className="nsw-button nsw-button--dark" onClick={event => {
            event.preventDefault()
            setFlowchart(!flowchart)
          }}>Switch to {flowchart ? 'preview' : 'flowchart'}</button>
        </div>
      </div>
    </div>
    <div className="nsw-dialog__close">
      <button className="nsw-icon-button" onClick={event => {
        event.preventDefault()

        setOpen(false)
      }}>
        <span className="material-icons nsw-material-icons" focusable="false" aria-hidden="true">close</span>
        <span className="sr-only">Close</span>
      </button>
    </div>
  </div>
}

export default function App({data, admin}) {
  const ref = useRef(null)
  const [open, setOpen] = useState(true)
  const [changed, setChanged] = useState(false)
  const [flowchart, setFlowchart] = useState(false)
  const [print, setPrint] = useState(false)
  const [flowchartSelectedId, setFlowchartSelectedId] = useState()

  useEffect(() => {
    if (!open && changed) {
      window.location.reload()
    }
  }, [open, changed])

  useEffect(() => {
    if (!open || !admin) {
      return
    }
    const modal = ref.current
    const keydown = event => {
      if (event.key == 'Escape') {
        setOpen(false)
      }
    }
    document.addEventListener('keydown', keydown)
    disableBodyScroll(modal)
    return () => {
      document.removeEventListener('keydown', keydown)
      enableBodyScroll(modal)
    }
  }, [open])

  const id = data.id
  return admin ? <div
    className={'nsw-dialog'+(open ? ' active' : '')}
    ref={ref}
    role="dialog"
    aria-labelledby="dialog-title"
    onClick={event => {
      if (event.target == ref.current) {
        setOpen(false)
      }
    }}
  >
    <div className={'nsw-dialog__wrapper '+(flowchart ? 'flowchart' : '')}>
      <ModalContext.Provider value={{
        flowchart,
        setFlowchart: newFlowchart => {
          setFlowchartSelectedId(undefined)
          setFlowchart(newFlowchart)
        },
        print,
        setPrint,
        setOpen,
        setChanged
      }}>
        {flowchart ? <Flowchart {...{id}} title={data.title} onSwitch={newId => {
          setFlowchartSelectedId(newId)
          setFlowchart(false)
        }} /> : <Choose
          id={flowchartSelectedId || id}
          title={data.title}
          progressIndicator
          responseTracker
          skipIntro={flowchartSelectedId !== undefined}
          admin
        />}
      </ModalContext.Provider>
    </div>
  </div> : <div className="decisionToolGrid">
    <Choose
      {...{id}}
      entryPageId={data.entryPageId}
      title={data.title}
      nestQuestions={data.nestQuestions !== undefined}
      progressIndicator={data.progressIndicator !== undefined}
      responseTracker={data.responseTracker !== undefined}
      begin={data.begin}
      beginText={data.beginText}
    />
  </div>
}
