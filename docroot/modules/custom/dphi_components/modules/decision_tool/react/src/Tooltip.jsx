import { useEffect, useRef, useState } from 'react'
import HtmlMapper from 'react-html-map'

export function Tooltip({children, onClose}) {
  const [align, setAlign] = useState('center')
  const arrowRef = useRef(null)
  const popupRef = useRef(null)
  useEffect(() => {
    const popupWidth = popupRef.current.clientWidth

    const position = () => {
      const arrow = arrowRef.current
      if (!arrow) {
        return
      }
      const rect = arrow.getBoundingClientRect()
      const arrowWidth = rect.width
      const arrowCenter = rect.left + arrowWidth / 2

      let newAlign
      if (arrowCenter + popupWidth / 2 > window.innerWidth - 15) {
        newAlign = 'right'
      } else if (arrowCenter - popupWidth / 2 < 0) {
        newAlign = 'left'
      } else {
        newAlign = 'center'
      }
      setAlign(newAlign)
    }
    window.addEventListener('resize', position)
    position()
  }, [])
  return <>
    <div
      ref={popupRef}
      className={(onClose ? "nsw-toggletip__element nsw-toggletip__element--dark" : "nsw-tooltip__element nsw-tooltip__element--dark")+" active"}
      aria-labelledby={onClose ? "nsw-toggletip__header" : undefined}
      aria-describedby={onClose ? "nsw-toggletip__content" : undefined}
      aria-expanded="true"
      tabindex={onClose ? "0" : undefined}
      role="dialog"
      data-align={align}
    >
      {onClose ? <>
        <div id="nsw-toggletip__header" className="nsw-toggletip__header">
          <button type="button" className="nsw-icon-button" onClick={onClose}>
            <span className="material-icons nsw-material-icons" focusable="false">close</span>
          </button>
        </div>
        <div id="nsw-toggletip__content" className="nsw-toggletip__content">
          {children}
        </div>
      </> : children}
    </div>
    <div
      ref={arrowRef}
      className={onClose ? "nsw-toggletip__arrow" : "nsw-tooltip__arrow"}
    />
  </>
}

function TooltipText({type, tooltip, children}) {
  const [open, setOpen] = useState(false)
  const inner = type == 'nsw-tooltip' ? <>
    <span
      className={type}
      onMouseEnter={() => setOpen(true)}
      onMouseLeave={() => setOpen(false)}
      tabIndex="0"
      onFocus={() => setOpen(true)}
      onBlur={() => setOpen(false)}
    >{children}</span>
    {open && <Tooltip>
      {tooltip}
    </Tooltip>}
  </> : <>
    <span
      className={type}
      tabIndex="0"
      onClick={() => setOpen(true)}
      onKeyDown={event => {
        if (event.key == 'Enter') {
          setOpen(true)
        }
      }}
    >{children}</span>
    {open && <Tooltip onClose={() => setOpen(false)}>
      {tooltip}
    </Tooltip>}
  </>
  return <span className="decisionToolTooltip" onClick={event => {
    event.preventDefault()
  }}>{inner}</span>
}

export function RichTextWithTooltip({html}) {
  const [tooltips, setTooltips] = useState({})
  return <HtmlMapper html={html}>
    {{
      ul: null,
      li: null,
      p: null,
      strong: null,
      em: null,
      h2: null,
      h3: null,
      h4: null,
      h5: null,
      h6: null,
      a: null,
      span: props => {
        let type
        if (props.class == 'nsw-toggletip js-toggletip') {
          type = 'nsw-toggletip'
        } else if (props.class == 'nsw-tooltip js-tooltip') {
          type = 'nsw-tooltip'
        }
        if (type) {
          const id = props['aria-controls']
          if (tooltips[id] !== undefined) {
            return <TooltipText {...{type}} tooltip={tooltips[id]}>
              {props.children}
            </TooltipText>
          }
        }
        return <span>{props.children}</span>
      },
      div: props => {
        const id = props.id
        if (tooltips[id] === undefined) {
          setTooltips(previousTooltips => {
            return {
              ...previousTooltips,
              [id]: props.children
            }
          })
        }
      }
    }}
  </HtmlMapper>
}
