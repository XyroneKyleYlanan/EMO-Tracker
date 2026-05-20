import api from './api'

export async function downloadFile(url, fallbackName = 'download') {
  const response = await api.get(url, { responseType: 'blob' })

  const disposition = response.headers['content-disposition'] || ''
  const match = /filename="?([^"]+)"?/.exec(disposition)
  const filename = match ? match[1] : fallbackName

  const blobUrl = window.URL.createObjectURL(response.data)
  const a = document.createElement('a')
  a.href = blobUrl
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(blobUrl)
}
