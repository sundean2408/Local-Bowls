export const APP_TIME_ZONE = 'Asia/Jakarta'

const datePartsFormatter = new Intl.DateTimeFormat('en-CA', {
  timeZone: APP_TIME_ZONE,
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
  hourCycle: 'h23',
})

function toAppDate(value) {
  if (value instanceof Date) return value
  if (typeof value !== 'string' || !value.trim()) return null

  const input = value.trim()
  const hasTimeZone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(input)
  const normalized = hasTimeZone
    ? input
    : /^\d{4}-\d{2}-\d{2}$/.test(input)
      ? `${input}T00:00:00Z`
      : `${input.replace(' ', 'T')}Z`
  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? null : date
}

export function getAppDateParts(value) {
  const date = toAppDate(value)
  if (!date) return null

  return Object.fromEntries(
    datePartsFormatter.formatToParts(date)
      .filter(({ type }) => type !== 'literal')
      .map(({ type, value: partValue }) => [type, Number(partValue)])
  )
}

export function getAppTimestamp(value) {
  return toAppDate(value)?.getTime() ?? null
}

export function formatAppTime(value) {
  const date = toAppDate(value)
  if (!date) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    timeZone: APP_TIME_ZONE,
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).format(date)
}

export function formatAppDateTime(value) {
  const date = toAppDate(value)
  if (!date) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    timeZone: APP_TIME_ZONE,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).format(date)
}

export function isAppToday(value, reference = new Date()) {
  const dateParts = getAppDateParts(value)
  const referenceParts = getAppDateParts(reference)
  if (!dateParts || !referenceParts) return false

  return dateParts.year === referenceParts.year
    && dateParts.month === referenceParts.month
    && dateParts.day === referenceParts.day
}

export function getAppWeekday(value) {
  const parts = getAppDateParts(value)
  if (!parts) return null

  return new Date(Date.UTC(parts.year, parts.month - 1, parts.day)).getUTCDay()
}
