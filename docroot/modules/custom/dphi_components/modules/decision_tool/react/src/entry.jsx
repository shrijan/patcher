import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'

const render = (rootElement, data, admin) => {
  ReactDOM.createRoot(rootElement).render(
    <React.StrictMode>
      <App {...{data, admin}} />
    </React.StrictMode>
  )
}

if (document.querySelector('body.user-logged-in') !== null) {
  const region = document.querySelector('.layout-region--main .layout-region__content')
  if (region) {
    // Show steps from the collection
    const selectAValueOption = () => {
      const option = document.createElement('option')
      option.value = ''
      option.innerText = '- Select a value -'
      option.selected = true
      option.disabled = true
      return option
    }

    const stepInput = document.querySelector('input[name="field_step[0][value]"]')
    const stepLabel = stepInput.previousElementSibling
    const stepSelect = document.createElement('select')
    stepSelect.name = stepInput.name
    stepSelect.required = true
    stepSelect.classList.add('form-select', 'required', 'form-element', 'form-element--type-select')
    if (stepInput.classList.contains('error')) {
      stepSelect.classList.add('error')
    }
    stepSelect.appendChild(selectAValueOption())
    stepInput.parentElement.appendChild(stepSelect)

    let initialValue = stepInput.value
    stepInput.remove()

    const updateSteps = () => {
      const id = collectionSelect.value
      if (id == '_none') {
        stepSelect.querySelectorAll('option').forEach(option => {
          option.remove()
        })
        stepSelect.appendChild(selectAValueOption())
        return
      }
      fetch('/decisionTool/steps?id='+id).then(response => response.json()).then(json => {
        stepSelect.querySelectorAll('option').forEach(option => {
          option.remove()
        })
        if (json.length) {
          json.forEach(item => {
            const option = document.createElement('option')
            option.value = item.id
            option.innerText = item.text
            if (initialValue == item.id) {
              option.selected = true
              initialValue = undefined
            }
            stepSelect.appendChild(option)
          })
          stepLabel.classList.remove('has-error')
          stepSelect.classList.remove('error')
        } else {
          stepSelect.appendChild(selectAValueOption())
        }
      })
    }
    const updateAutocomplete = () => {
      // Change "Next question" autocomplete so that it only uses this collection
      const id = collectionSelect.value
      document.querySelectorAll('input[name$="][subform][field_next_question][0][target_id]"]').forEach(input => {
        input.dataset.autocompletePath = '/decisionTool/autocomplete?collection='+id
      })
    }
    const collectionSelect = document.querySelector('select[name=field_collection]')
    collectionSelect.addEventListener('change', () => {
      document.querySelectorAll('input[name$="][subform][field_next_question][0][target_id]"]').forEach(input => {
        delete Drupal.autocomplete.cache[input.id]
        input.value = ''
      })
      updateAutocomplete()
      updateSteps()
    })
    updateAutocomplete()
    updateSteps()

    const observer = new MutationObserver(updateAutocomplete)
    observer.observe(document.querySelector('#field-answers-values'), {
      subtree: true,
      childList: true
    })

    const id = window.location.href.split('/node/')[1].split('/')[0]
    if (id != 'add') {
      // Add "Preview the Decision Tool" button
      region.style.position = 'relative'

      const div = document.createElement('div')
      div.classList.add('decisionTool')
      region.appendChild(div)

      const openModal = () => {
        render(div, {
          id,
          title: 'Decision'
        }, true)
      }
      if (window.location.href.endsWith('?decisionToolPreview=true')) {
        openModal()
      }

      const form = region.closest('form')
      form.action = form.action.replace('?decisionToolPreview=true', '')

      let saveFirst
      const button = document.createElement('button')
      button.classList.add('nsw-button', 'nsw-button--dark', 'decisionToolPreviewButton')
      button.setAttribute('aria-haspopup', 'dialog')
      button.addEventListener('click', event => {
        event.preventDefault()
        if (saveFirst) {
          form.action = form.action.split('?destination=')[0]+'?destination='+encodeURIComponent(window.location.pathname+'?decisionToolPreview=true')
          form.querySelector('input[value=Save]').click()
        } else {
          openModal()
        }
      })
      button.innerText = 'Preview the Decision Tool'
      region.appendChild(button)

      const observers = []
      const changed = () => {
        button.innerText = 'Save and preview the Decision Tool'
        saveFirst = true
        observers.forEach(observer => {
          observer.disconnect()
        })
      }
      form.addEventListener('input', changed)
      form.querySelectorAll('textarea').forEach(textarea => {
        const observer = new MutationObserver(changed)
        observer.observe(textarea, {
          childList: true,
          attributeFilter: ['data-editor-value-is-changed']
        })
        observers.push(observer)
      })

      // Questions created by our tool may not have answers,
      // so suggest to the user that they add an answer
      if (!document.querySelector('#field-answers-values tbody')) {
        document.querySelector('#field-answers-answer-add-more').dispatchEvent(new Event('mousedown'))
      }
    }
  }
} else {
  const decisionTools = document.querySelectorAll('.decisionTool')
  if (decisionTools.length) {
    decisionTools.forEach(rootElement => {
      render(rootElement, rootElement.dataset)
    })
  } else {
    const printButton = document.querySelector('.decisionToolFooter .printButton')
    if (printButton) {
      printButton.addEventListener('click', () => window.print())
    }
  }
}
