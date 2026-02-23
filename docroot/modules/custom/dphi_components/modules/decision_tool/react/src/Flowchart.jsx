import { useContext, useEffect, useRef, useState } from 'react'
import { ModalContext, ModalHeader } from './App'
import { RichTextWithTooltip } from './Tooltip'

function arrange(json, ref, initialId) {
  const elements = Array.from(ref.current.querySelectorAll('.question, .confirmationPage'))
  const getElement = id => elements.find(element => element.dataset.id == id)
  const element = getElement(initialId)
  const height = element.clientHeight
  const columns = [json.items[initialId].step]
  const positions = {
    [initialId]: {
      height,
      y: 0
    }
  }

  const arrangeAnswers = (drill, id) => {
    const answersWithLinksToArrange = [];
    (json.items[id].answers || []).forEach(answer => {
      if (answer.link && !positions[answer.link.id] && !answersWithLinksToArrange.some(item => item.link.id == answer.link.id)) {
        answersWithLinksToArrange.push(answer)
      }
    })
    const items = answersWithLinksToArrange.map(answer => {
      const itemElement = getElement(answer.link.id)
      return {
        id: answer.link.id,
        height: itemElement.clientHeight
      }
    })
    items.forEach((item, i) => {
      const position = {
        height: item.height
      }

      const itemStep = json.items[item.id].step
      if (itemStep) {
        if (itemStep.id == json.items[id].step.id) {
          position.drill = drill + 1

          const column = columns.find(column => column.id == itemStep.id)
          if (!column.drill || drill + 1 > column.drill) {
            column.drill = drill + 1
          }
        } else if (columns.every(column => column.id != itemStep.id)) {
          columns.push(itemStep)
        }
      }
      positions[item.id] = position
    })

    const y = positions[id].y
    let height = 0
    let arrangedPositions = []
    answersWithLinksToArrange.forEach((answer, i) => {
      const linkId = answer.link.id
      const position = positions[linkId]
      arrangedPositions.push(position)
      position.y = y + height
      const [lowestY, subPositions] = arrangeAnswers(position.drill || 0, linkId)
      height += Math.max(position.height, lowestY)
      arrangedPositions = arrangedPositions.concat(subPositions)
      if (i != answersWithLinksToArrange.length - 1) {
        height += 100
      }
    })
    if (height > positions[id].height) {
      positions[id].y += height / 2 - positions[id].height / 2
    }

    let lowestY = y
    arrangedPositions.forEach(position => {
      if (position.y + position.height > lowestY) {
        lowestY = position.y + position.height
      }
    })
    return [
      lowestY - y,
      arrangedPositions
    ]
  }
  arrangeAnswers(0, initialId)

  // Arrange columns
  let stepCol = 0
  json.steps.forEach(stepId => {
    const column = columns.find(column => column.id == stepId)
    if (!column) {
      return
    }
    Object.entries(positions).forEach(([id, position]) => {
      if (json.items[id].step?.id == stepId) {
        position.col = stepCol + (position.drill || 0)
        delete position.drill
      }
    })
    stepCol += (column.drill || 0) + 1
  })

  let confirmationPages
  Object.values(positions).forEach(position => {
    position.top = 80 + position.y
    if (position.col !== undefined) {
      position.className = 'col'+(position.col % 4).toString()
    } else {
      // Place confirmation pages in the last column
      position.col = stepCol
      confirmationPages = true
    }
  })

  const columnNames = []
  json.steps.forEach(stepId => {
    const column = columns.find(column => column.id == stepId)
    if (!column) {
      return
    }
    [...Array((column.drill || 0) + 1)].forEach((_, drill) => {
      let title = column.title
      if (drill) {
        title += ' - Drill '+drill.toString()
      }
      columnNames.push({
        title,
        description: drill ? undefined : column.description
      })
    })
  })
  if (confirmationPages) {
    columnNames.push({
      title: 'Your next steps'
    })
  }

  return {
    columnNames,
    positions
  }
}

function linkAnswers(json, ref, positions) {
  const elements = Array.from(ref.current.querySelectorAll('.question, .confirmationPage'))
  const getElement = id => elements.find(element => element.dataset.id == id)
  const links = []
  Object.entries(json).forEach(([id, item]) => {
    const element = getElement(id)
    const answerElements = element.querySelectorAll('li')
    const answers = item.answers || []
    const answersWithLinks = answers.filter(answer => answer.link)
    answersWithLinks.forEach(answer => {
      const itemElement = getElement(answer.link.id)
      links.push({
        src: {
          id,
          element,
          answer: answerElements[answers.indexOf(answer)]
        },
        dest: {
          id: answer.link.id,
          element: itemElement
        }
      })
    })
  })

  const points = data => {
    const position = positions[data.id]
    let top = position.top
    let left = position.col * 500
    const width = data.element.clientWidth
    let height

    if (data.answer) {
      top += data.answer.offsetTop
      height = data.answer.clientHeight
    } else {
      height = data.element.clientHeight
    }
    const items = [
      {x: left, y: top + height / 2},
      {x: left + width, y: top + height / 2}
    ]
    return data.answer ? items : items.concat([
      {x: left + width / 2, y: top},
      {x: left + width / 2, y: top + height}
    ])
  }
  return links.map(link => {
    let shortestLine
    points(link.src).forEach(src => {
      points(link.dest).forEach(dest => {
        const distance = Math.sqrt((src.x - dest.x) ** 2 + (src.y - dest.y) ** 2)
        if (!shortestLine || distance < shortestLine.distance) {
          shortestLine = {
            distance,
            src,
            dest
          }
        }
      })
    })
    return {
      ids: [link.src.id, link.dest.id],
      style: {
        top: shortestLine.src.y,
        left: shortestLine.src.x,
        width: shortestLine.distance - 4,
        transform: 'rotate('+Math.atan2(shortestLine.dest.y - shortestLine.src.y, shortestLine.dest.x - shortestLine.src.x)+'rad)'
      }
    }
  })
}

export default function Flowchart({id, title, onSwitch}) {
  const {print} = useContext(ModalContext)
  const ref = useRef(null)
  const [data, setData] = useState({})
  const [zoomScale, setZoomScale] = useState(1)
  const [hoverId, setHoverId] = useState()
  const [printScale, setPrintScale] = useState(1)
  useEffect(() => {
    fetch('/decisionTool/flowchart?id='+id).then(response => response.json()).then(json => {
      const items = json.items
      setData({
        items,
        steps: json.steps
      })

      setTimeout(() => {
        const {columnNames, positions} = arrange(json, ref, id)

        const updatedJson = {...items}
        let maxBottom
        Object.entries(positions).forEach(([id, position]) => {
          updatedJson[id] = {
            ...updatedJson[id],
            top: position.top,
            col: position.col,
            className: position.className
          }
          const bottom = position.top + position.height
          if (maxBottom === undefined || bottom > maxBottom) {
            maxBottom = bottom
          }
        })
        setData({
          columnNames,
          items: updatedJson,
          links: linkAnswers(items, ref, positions),
          spacePosition: maxBottom !== undefined ? {
            height: maxBottom,
            col: Math.max(...Object.values(positions).map(item => item.col))
          } : undefined,
        })
      }, 1)
    })
  }, [])
  useEffect(() => {
    if (!print) {
      setPrintScale(1)
      return
    }
    const scale = Math.min(718 / ref.current.scrollWidth, 833 / (ref.current.scrollHeight - 32 - 37 - 32))
    if (scale < 1) {
      setPrintScale(scale)
    }
  }, [print])

  const scale = print ? printScale : zoomScale
  return <div className="nsw-dialog__container">
    <ModalHeader {...{title}} />
    <div className="nsw-dialog__content">
      {data.items && <div className="scroll">
        <div className="items" {...{ref}} style={{
          transform: scale == 1 ? undefined : 'scale('+scale.toString()+')'
        }}>
          {Object.entries(data.items).map(([itemId, item]) => {
            const attrs = {
              'data-id': itemId
            }
            if (item.top !== undefined) {
              attrs.style = {
                top: item.top.toString()+'px',
                '--col': item.col
              }
            }
            if (item.confirmationPage) {
              return <a
                href={'/node/'+itemId+'/edit'}
                target="_blank"
                className="confirmationPage"
                onMouseEnter={() => setHoverId(itemId)}
                onMouseLeave={() => setHoverId(undefined)}
                {...attrs}
              >
                <strong>{item.title}</strong>
              </a>
            }
            return <div
              {...attrs}
              className={'question'+(item.className ? ' '+item.className : '')}
              onClick={() => onSwitch(itemId)}
              onMouseEnter={() => setHoverId(itemId)}
              onMouseLeave={() => setHoverId(undefined)}
            >
              {item.title != item.question && <div className="nsw-text-bold nsw-small">{item.title}</div>}
              <div className="id">{itemId}</div>
              <div className="stepTitle">{item.step.title}</div>
              {item.question}
              {item.answers && <ul>
                {item.answers.map((answer, i) => <li>
                  <div className="answerId">{itemId+'.A'+(i+1).toString()}</div>
                  {answer.text}
                </li>)}
              </ul>}
              <div className="responseTrackerTitle">[RT] {item.responseTrackerTitle}</div>
            </div>
          })}
          {data.spacePosition && <>
            <div className="space" style={{
              top: data.spacePosition.top,
              '--col': data.spacePosition.col
            }} />
            {data.columnNames?.map((columnName, i) => <div className="column" style={{
              '--col': i,
              '--height': (data.spacePosition.height + 32).toString()+'px'
            }}>
              <strong>{columnName.title}</strong>
              {columnName.description && <div dangerouslySetInnerHTML={{__html: columnName.description}} />}
            </div>)}
          </>}
          {data.links?.map(({ids, style}) => <div className={'connection'+(ids.includes(hoverId) ? ' itemHover' : '')} tabIndex="0" {...{style}} />)}
        </div>
      </div>}
      <div className="zoom">
        {[
          ['add_circle', zoomScale != 2, () => {
            setZoomScale(zoomScale * 2)
          }],
          ['do_not_disturb_on', zoomScale != 0.125, () => {
            setZoomScale(zoomScale == 1 ? 0.5 : zoomScale / 2)
          }]
        ].map(([control, enabled, action]) => <button
          className="material-icons nsw-material-icons"
          focusable="false"
          aria-hidden
          disabled={!enabled}
          onClick={event => {
            event.preventDefault()

            action()
          }}
        >{control}</button>)}
      </div>
    </div>
  </div>
}
