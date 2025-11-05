export const setUrlSearchParams = (key, value) => {
  if (!window.history || !window.history.pushState) {
    return
  }

  key = key.replace(/ /g, '-')
  const url = new URL(window.location.href)
  const searchParams = url.searchParams

  if (value) {
    searchParams.set(key, value)
  } else {
    searchParams.delete(key)
  }

  url.search = searchParams.toString()

  history.replaceState(null, '', url.href)
}

export const getUrlSearchParam = key => {
  key = key.replace(/ /g, '-')
  const urlParams = new URLSearchParams(window.location.search)
  return urlParams.get(key)
}
