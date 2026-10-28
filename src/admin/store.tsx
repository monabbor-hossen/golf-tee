import React, { createContext, useContext, useEffect, useMemo, useState } from 'react'
import {
  type Activity, type BookingEntry, type DayData, type EventT, type Focus, type GridEntry, type Member, type Notif, type Page,
  type Settings, type Tone, type Txn, type WaitEntry,
  NOW_MIN, SLOT_MINS, TEES, defaultSettings, extractCurrencySymbol, fakeNames, fmtDate, fmtMin, isPast, isWeekend, parseKey, rng, seedDay, seedMembers,
} from './shared'

export type DayStats = {
  bookings: [string, BookingEntry][]; active: [string, BookingEntry][]; players: number; revenue: number
  util: number; blocked: number; count: (s: BookingEntry['status']) => number
}
export function dayStats(day: DayData, o: number, price: PriceFn): DayStats {
  const bookings = Object.entries(day).filter((x): x is [string, BookingEntry] => x[1].kind === 'booking')
  const active = bookings.filter(([, e]) => e.status !== 'No-show')
  const blocked = Object.values(day).filter((e) => e.kind === 'block').length
  return {
    bookings, active, blocked,
    players: active.reduce((a, [, e]) => a + e.party, 0),
    revenue: active.reduce((a, [k, e]) => a + e.party * price(e.holes, o, parseKey(k).m), 0),
    util: Math.round((bookings.length / Math.max(1, TEES.length * SLOT_MINS.length - blocked)) * 100),
    count: (s) => bookings.filter(([, e]) => e.status === s).length,
  }
}

type PriceFn = (holes: 18 | 9, o: number, m: number) => number
type Setter<T> = React.Dispatch<React.SetStateAction<T>>

type AdminCtx = {
  now: number
  page: Page; focus: Focus | null; go: (p: Page, f?: Focus) => void
  members: Member[]; setMembers: Setter<Member[]>
  getDay: (o: number) => DayData
  setEntry: (o: number, k: string, e: GridEntry | null) => void
  book: (o: number, k: string, e: BookingEntry) => void
  cancel: (o: number, k: string) => void
  setStatus: (o: number, k: string, s: BookingEntry['status']) => void
  move: (o: number, from: string, to: string) => void
  blockRange: (o: number, tees: number[], mins: number[], reason: string) => { blocked: number; conflicts: number }
  unblockRange: (o: number, start: number, end: number, reason: string) => void
  waitlist: WaitEntry[]; setWaitlist: Setter<WaitEntry[]>
  txns: Txn[]; setTxns: Setter<Txn[]>; addTxn: (t: Omit<Txn, 'id'>) => string
  events: EventT[]; setEvents: Setter<EventT[]>
  settings: Settings; setSettings: Setter<Settings>
  notifs: Notif[]; setNotifs: Setter<Notif[]>; notify: (n: Omit<Notif, 'id' | 'at' | 'read'>) => void
  activity: Activity[]; log: (title: string, desc: string, tone: Tone) => void
  toastMsg: string; toast: (m: string) => void
  price: PriceFn
  currSym: string
  fmtMoney: (n: number) => string
}
const Ctx = createContext<AdminCtx | null>(null)
export const useAdmin = () => { const c = useContext(Ctx); if (!c) throw new Error('useAdmin outside provider'); return c }

let txnSeq = 48210
const nextTxnId = () => `TX-${++txnSeq}`

function seedTxns(price: PriceFn): Txn[] {
  const out: Txn[] = []
  for (const o of [-2, -1, 0]) {
    const day = seedDay(o)
    const r = rng(o + 900)
    Object.entries(day).forEach(([k, e]) => {
      if (e.kind !== 'booking') return
      const { t, m } = parseKey(k)
      const member = seedMembers.some((u) => u.name === e.name)
      out.push({ id: nextTxnId(), offset: o, min: Math.max(420, m - 60 - Math.floor(r() * 600)), name: e.name, desc: `${e.holes} holes · ${e.party} players · ${TEES[t]} ${fmtMin(m)}`, type: 'Green fee', method: member ? 'Member account' : r() < 0.7 ? 'Visa' : 'Apple Pay', amount: e.party * price(e.holes, o, m), status: o === 0 && !isPast(o, m) && r() < 0.12 ? 'Pending' : 'Paid', ref: `${o}|${k}` })
      if (r() < 0.35) out.push({ id: nextTxnId(), offset: o, min: Math.min(1080, m + 250), name: e.name, desc: r() < 0.5 ? 'Clubhouse lunch' : 'Halfway house', type: 'Food & bev', method: 'Visa', amount: 18 + Math.floor(r() * 70), status: 'Paid' })
      if (r() < 0.15) out.push({ id: nextTxnId(), offset: o, min: Math.max(420, m - 20), name: e.name, desc: r() < 0.5 ? 'Titleist Pro V1 · dozen' : 'Glove & tees', type: 'Pro shop', method: 'Mastercard', amount: 22 + Math.floor(r() * 40), status: 'Paid' })
    })
  }
  out.push({ id: nextTxnId(), offset: -1, min: 600, name: 'Thanh Nguyen', desc: 'Cancelled · Fri 10:00 AM', type: 'Green fee', method: 'Visa', amount: 170, status: 'Refunded' })
  out.push({ id: nextTxnId(), offset: 0, min: 540, name: 'Sofia Kim', desc: 'Standard membership · annual', type: 'Membership', method: 'Visa', amount: 1200, status: 'Pending' })
  out.push({ id: nextTxnId(), offset: -2, min: 720, name: 'Priya Shah', desc: 'Autumn Member Classic entry', type: 'Event', method: 'Member account', amount: 95, status: 'Paid' })
  return out.sort((a, b) => b.offset - a.offset || b.min - a.min)
}

const T0 = Date.now()
const minsAgo = (m: number) => T0 - m * 60000

export function AdminProvider({ children }: { children: React.ReactNode }) {
  const [now, setNow] = useState(T0)
  const [page, setPage] = useState<Page>('Dashboard')
  const [focus, setFocus] = useState<Focus | null>(null)
  const [members, setMembers] = useState(seedMembers)
  const [grid, setGrid] = useState<Record<number, DayData>>({})
  const [settings, setSettings] = useState(defaultSettings)
  const [toastMsg, setToastMsg] = useState('')

  const price: PriceFn = useMemo(() => (holes, o, m) => {
    const p = settings.pricing, i = holes === 18 ? 0 : 1
    if (settings.flags.twilight && m >= settings.twilightStart) return p.twilight[i]
    return isWeekend(o) ? p.weekend[i] : p.weekday[i]
  }, [settings])

  const [txns, setTxns] = useState<Txn[]>(() => seedTxns(price))
  const [waitlist, setWaitlist] = useState<WaitEntry[]>([
    { id: 1, name: 'Kenji Ito', offset: 0, min: 880, party: 2, holes: 18, addedMin: 25, status: 'Waiting' },
    { id: 2, name: 'Olivia Chen', offset: 0, min: 760, party: 4, holes: 18, addedMin: 70, status: 'Waiting' },
    { id: 3, name: 'Raj Patel', offset: 1, min: 480, party: 2, holes: 18, addedMin: 140, status: 'Waiting' },
    { id: 4, name: 'Marcus Reed', offset: 1, min: 450, party: 3, holes: 9, addedMin: 190, status: 'Waiting' },
    { id: 5, name: 'James Carter', offset: 4, min: 430, party: 4, holes: 18, addedMin: 300, status: 'Waiting' },
    { id: 6, name: 'Hannah Fox', offset: 5, min: 920, party: 2, holes: 9, addedMin: 610, status: 'Waiting' },
  ])
  const [events, setEvents] = useState<EventT[]>([
    { id: 1, name: 'Junior Golf Clinic', offset: 3, start: 900, hours: 2, format: 'Clinic', capacity: 16, fee: 40, registered: fakeNames(11, 3), open: true, blocked: false },
    { id: 2, name: 'Autumn Member Classic', offset: 9, start: 480, hours: 5, format: 'Scramble', capacity: 72, fee: 95, registered: [...seedMembers.slice(0, 6).map((m) => m.name), ...fakeNames(46, 9)], open: true, blocked: false },
    { id: 3, name: 'Couples Twilight Scramble', offset: 12, start: 960, hours: 2, format: 'Best ball', capacity: 32, fee: 60, registered: fakeNames(30, 12), open: true, blocked: false },
    { id: 4, name: 'Club Championship', offset: -5, start: 450, hours: 6, format: 'Stroke play', capacity: 64, fee: 120, registered: fakeNames(64, 5), open: false, blocked: false },
  ])
  const [notifs, setNotifs] = useState<Notif[]>([
    { id: 1, title: 'Members awaiting approval', desc: 'Sofia Kim and Kenji Ito requested membership', at: minsAgo(60), page: 'Users', focus: { memberId: 4 }, read: false, tone: 'wait' },
    { id: 2, title: 'Waitlist demand', desc: '2 players waiting for today after 12 PM', at: minsAgo(25), page: 'Waitlist', read: false, tone: 'wait' },
    { id: 3, title: 'Event almost full', desc: 'Couples Twilight Scramble · 30/32 registered', at: minsAgo(180), page: 'Events', focus: { eventId: 3 }, read: false, tone: 'forest' },
    { id: 4, title: 'Payment pending', desc: 'Membership fee for Sofia Kim — $1,200', at: minsAgo(300), page: 'Payments', read: true, tone: 'gray' },
  ])
  const [activity, setActivity] = useState<Activity[]>([
    { id: 1, title: 'New booking', desc: 'Alejandro Gomez · Sat 8:30 AM · 4 players', at: minsAgo(2), tone: 'forest' },
    { id: 2, title: 'Waitlist joined', desc: 'Kenji Ito · Today 2:40 PM', at: minsAgo(9), tone: 'wait' },
    { id: 3, title: 'Cancellation', desc: 'Thanh Nguyen · Fri 10:00 AM · refunded $170', at: minsAgo(24), tone: 'danger' },
    { id: 4, title: 'Member pending', desc: 'Sofia Kim requested Standard membership', at: minsAgo(62), tone: 'sand' },
    { id: 5, title: 'Payment received', desc: '$340 · James Carter', at: minsAgo(75), tone: 'forest' },
  ])

  useEffect(() => { const t = window.setInterval(() => setNow(Date.now()), 30000); return () => window.clearInterval(t) }, [])
  useEffect(() => { if (!toastMsg) return; const t = window.setTimeout(() => setToastMsg(''), 2600); return () => window.clearTimeout(t) }, [toastMsg])

  const getDay = (o: number) => grid[o] ?? seedDay(o)
  const updateDay = (o: number, fn: (d: DayData) => void) => setGrid((all) => { const d = { ...(all[o] ?? seedDay(o)) }; fn(d); return { ...all, [o]: d } })
  const setEntry = (o: number, k: string, e: GridEntry | null) => updateDay(o, (d) => { if (e) d[k] = e; else delete d[k] })
  const log = (title: string, desc: string, tone: Tone) => setActivity((a) => [{ id: Date.now() + Math.random(), title, desc, at: Date.now(), tone }, ...a].slice(0, 40))
  const notify = (n: Omit<Notif, 'id' | 'at' | 'read'>) => setNotifs((l) => [{ ...n, id: Date.now() + Math.random(), at: Date.now(), read: false }, ...l])
  const addTxn = (t: Omit<Txn, 'id'>) => { const id = nextTxnId(); setTxns((l) => [{ ...t, id }, ...l]); return id }

  const book = (o: number, k: string, e: BookingEntry) => {
    const { t, m } = parseKey(k)
    setEntry(o, k, e)
    const member = members.some((u) => u.name === e.name)
    addTxn({ offset: 0, min: NOW_MIN, name: e.name, desc: `${e.holes} holes · ${e.party} players · ${fmtDate(o)} ${fmtMin(m)} · ${TEES[t]}`, type: 'Green fee', method: member ? 'Member account' : 'Visa', amount: e.party * price(e.holes, o, m), status: settings.flags.deposit ? 'Pending' : 'Paid', ref: `${o}|${k}` })
    log('New booking', `${e.name} · ${fmtDate(o)} ${fmtMin(m)} · ${e.party} players`, 'forest')
    setWaitlist((w) => w.filter((x) => !(x.name === e.name && x.offset === o)))
  }

  const cancel = (o: number, k: string) => {
    const e = getDay(o)[k]
    if (!e || e.kind !== 'booking') return
    const { m } = parseKey(k)
    setEntry(o, k, null)
    const amount = e.party * price(e.holes, o, m)
    const ref = `${o}|${k}`
    if (txns.some((x) => x.ref === ref && x.status !== 'Refunded')) setTxns((l) => l.map((x) => (x.ref === ref ? { ...x, status: 'Refunded' } : x)))
    else addTxn({ offset: 0, min: NOW_MIN, name: e.name, desc: `Cancelled · ${fmtDate(o)} ${fmtMin(m)}`, type: 'Green fee', method: 'Visa', amount, status: 'Refunded' })
    log('Cancellation', `${e.name} · ${fmtDate(o)} ${fmtMin(m)} · refunded $${amount}`, 'danger')
    // Offer the freed slot to the closest waiting player on that day
    const match = waitlist.filter((w) => w.offset === o && w.status === 'Waiting' && Math.abs(w.min - m) <= 60).sort((a, b) => Math.abs(a.min - m) - Math.abs(b.min - m))[0]
    if (match && settings.flags.waitlist) {
      if (settings.flags.autoOffer) {
        setWaitlist((l) => l.map((w) => (w.id === match.id ? { ...w, status: 'Offered', offeredMin: m } : w)))
        notify({ title: 'Waitlist offer sent', desc: `${match.name} offered ${fmtDate(o)} ${fmtMin(m)}`, page: 'Waitlist', tone: 'wait' })
      } else notify({ title: 'Waitlist match', desc: `${match.name} wants ${fmtDate(o)} near ${fmtMin(m)}`, page: 'Waitlist', tone: 'wait' })
    }
  }

  const setStatus = (o: number, k: string, s: BookingEntry['status']) => {
    const e = getDay(o)[k]
    if (!e || e.kind !== 'booking') return
    setEntry(o, k, { ...e, status: s })
    if (s === 'Checked in') log('Checked in', `${e.name} · ${fmtMin(parseKey(k).m)} · ${e.party} players`, 'forest')
    if (s === 'No-show') log('No-show', `${e.name} · ${fmtMin(parseKey(k).m)}`, 'danger')
  }

  const move = (o: number, from: string, to: string) => {
    const e = getDay(o)[from]
    if (!e || getDay(o)[to]) return
    updateDay(o, (d) => { d[to] = e; delete d[from] })
    setTxns((l) => l.map((x) => (x.ref === `${o}|${from}` ? { ...x, ref: `${o}|${to}` } : x)))
    if (e.kind === 'booking') log('Booking moved', `${e.name} · ${fmtMin(parseKey(from).m)} → ${fmtMin(parseKey(to).m)}`, 'sand')
  }

  const blockRange = (o: number, tees: number[], mins: number[], reason: string) => {
    const day = getDay(o)
    let blocked = 0, conflicts = 0
    tees.forEach((t) => mins.forEach((m) => { const e = day[`${t}-${m}`]; if (!e) blocked++; else if (e.kind === 'booking') conflicts++ }))
    updateDay(o, (d) => tees.forEach((t) => mins.forEach((m) => { if (!d[`${t}-${m}`]) d[`${t}-${m}`] = { kind: 'block', reason } })))
    if (blocked) log('Time blocked', `${fmtDate(o)} ${fmtMin(mins[0])}–${fmtMin(mins[mins.length - 1] + 10)} · ${reason}`, 'gray')
    return { blocked, conflicts }
  }
  const unblockRange = (o: number, start: number, end: number, reason: string) =>
    updateDay(o, (d) => Object.entries(d).forEach(([k, e]) => { const { m } = parseKey(k); if (e.kind === 'block' && e.reason === reason && m >= start && m < end) delete d[k] }))

  const currSym = extractCurrencySymbol(settings.currency)
  const fmtMoney = (n: number) => `${n < 0 ? '−' : ''}${currSym}${Math.abs(n).toLocaleString()}`

  const value: AdminCtx = {
    now, page, focus, go, members, setMembers, getDay, setEntry, book, cancel, setStatus, move, blockRange, unblockRange,
    waitlist, setWaitlist, txns, setTxns, addTxn, events, setEvents, settings, setSettings, notifs, setNotifs, notify,
    activity, log, toastMsg, toast: setToastMsg, price, currSym, fmtMoney,
  }
  return <Ctx.Provider value={value}>{children}</Ctx.Provider>
}
