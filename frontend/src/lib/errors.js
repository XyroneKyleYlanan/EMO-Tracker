// True when the request never reached Laravel: no connection at all, or the
// frontend server answering on its behalf because the backend isn't running
// (Laravel's own errors always come back as JSON).
export function isServerUnreachable(error) {
  const response = error?.response
  return !response || !String(response.headers?.['content-type'] || '').includes('json')
}
