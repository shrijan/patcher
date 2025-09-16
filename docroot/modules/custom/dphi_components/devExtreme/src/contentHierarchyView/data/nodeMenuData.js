import CustomStore from 'devextreme/data/custom_store'

function handleErrors(response) {
  if (!response.ok) {
    throw Error(response.statusText)
  }
  return response
}

export default function nodeMenuData(menuName) {
  return new CustomStore({
    key: 'menuItem.id',

    loadMode: 'raw',

    load: () => {
      let endpoint = 'content-tree/data'
      if (menuName) {
        endpoint += '?menuName=' + menuName
      }
      return fetch(endpoint)
        .then(handleErrors)
        .then(response => {
          return response.json()
        })
        .catch(() => {
          throw 'Network error'
        })
    },

    update: (key, values) => {
      return fetch('content-tree/update', {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(values),
      })
        .then(handleErrors)
        .then(response => {
          return response.json()
        })
        .catch(() => {
          throw 'Network error'
        })
    },
  })
}
