const collections = {}
const loadCollection = collectionSelect => {
  const collection = collectionSelect.value
  const updateOptions = () => {
    const questionSelect = collectionSelect.closest('.paragraphs-subform').querySelector('select[name$="[subform][field_question]"]')
    questionSelect.querySelectorAll('option').forEach(option => {
      option.remove()
    })

    if (collections[collection].length) {
      collections[collection].forEach(item => {
        const option = document.createElement('option')
        option.value = item.id
        option.innerText = item.text
        questionSelect.appendChild(option)
      })
    } else {
      const option = document.createElement('option')
      option.value = ''
      option.innerText = '- Select a value -'
      option.selected = true
      option.disabled = true
      questionSelect.appendChild(option)
    }
  }

  if (collections[collection]) {
    updateOptions()
  } else {
    fetch('/decisionTool/startingQuestions?collection='+collection).then(response => response.json()).then(json => {
      collections[collection] = json
      updateOptions()
    })
  }
}

const collectionSelects = []
const observer = new MutationObserver(() => {
  document.querySelectorAll('select[name$="[subform][field_collection]"').forEach(collectionSelect => {
    if (!collectionSelects.includes(collectionSelect)) {
      collectionSelects.push(collectionSelect)

      if (collectionSelect.value != '_none') {
        loadCollection(collectionSelect)
      }
      collectionSelect.addEventListener('change', () => {
        loadCollection(collectionSelect)
      })
    }
  })
})
observer.observe(document.querySelector('#main-page-sections--horizontal-tabs'), {
  subtree: true,
  childList: true
})
