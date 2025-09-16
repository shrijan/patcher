import CustomStore from 'devextreme/data/custom_store'
import notify from 'devextreme/ui/notify'

async function handleErrors(response) {
  if (!response.ok) {
    if (response.status === 403) {
      const message = await response.json()
      notify(
        {
          message: message.error,
          position: {
            my: 'top left',
            at: 'top left',
            of: '#media-hierarchy-root',
          },
        },
        'error',
        3000,
      )
    }
    throw Error(response.statusText)
  }
  return response
}

export default function mediaData({ folderId }) {
  return new CustomStore({
    key: 'id',

    loadMode: 'raw',

    load: loadOptions => {
      let endpoint = 'media-tree/data'
      if (folderId) {
        endpoint += '?folderId=' + folderId
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
      return fetch('media-tree/update', {
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
