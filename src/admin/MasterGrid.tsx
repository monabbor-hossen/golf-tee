import { useEffect, useRef, useState } from 'react'
import {
  type BookingEntry, type DayData, Field, Icon, ModalShell, NOW_MIN, Pill, SLOT_MINS, Segmented, SidePanel, Stat, TEES,
  btnGhost, btnPrimary, btnSecondary, downloadCSV, fmtDate, fmtLong, fmtMin, initials, inputCls, isPast, isWeekend, parseKey, stripes, tierCls,
} from './shared'
import { dayStats, useAdmin } from './store'

const ROW_H = 44
const bookingCls: Record<BookingEntry['status'], string> = {
  Booked: 'bg-forest/10 border-l-4 border-forest text-forest',
  'Checked in': 'bg-forest text-white',
  'No-show': 'bg-danger/10 border-l-4 border-danger text-danger line-through',
}

export function BookingModal({ day, offset, init, onClose, onSave }: { day: DayData; offset: number; init: { tee: number; min: number }; onClose: () => void; onSave: (key: string, e: BookingEntry) => void }) {
  const { members, price, settings, currSym } = useAdmin()
  const [tee, setTee] = useState(init.tee)
  const [min, setMin] = useState(init.min)
  const [name, setName] = useState('')
  const [party, setParty] = useState(Math.min(4, settings.maxParty))
  const [holes, setHoles] = useState<18 | 9>(18)
  const open = SLOT_MINS.filter((m) => !day[`${tee}-${m}`] && !isPast(offset, m))
  const m = open.includes(min) ? min : open[0]
  const unit = m !== undefined ? price(holes, offset, m) : 0
  const member = members.find((u) => u.name.toLowerCase() === name.trim().toLowerCase())
  const suspended = member?.status === 'Suspended'
  const valid = name.trim() && m !== undefined && !suspended
  return (
    <ModalShell title="New booking" desc={fmtLong(offset)} onClose={onClose}
      footer={<>
        <button onClick={onClose} className={btnGhost}>Cancel</button>
        <button disabled={!valid} onClick={() => onSave(`${tee}-${m}`, { kind: 'booking', name: member?.name ?? name.trim(), party, holes, status: 'Booked' })} className={btnPrimary}>Confirm booking</button>
      </>}>
      <Field label="Player" hint={member ? undefined : 'Pick a member or type a guest name'}>
        <input autoFocus list="member-names" className={inputCls} value={name} onChange={(e) => setName(e.target.value)} placeholder="Search members" />
        <datalist id="member-names">{members.map((u) => <option key={u.id} value={u.name} />)}</datalist>
      </Field>
      {member && (
        <div className={`flex items-center gap-2 text-xs font-semibold rounded-xl px-3 py-2 ${suspended ? 'bg-danger/10 text-danger' : 'bg-forest/5 text-forest'}`}>
          {suspended ? <><Icon name="alert" className="w-4 h-4" />This member is suspended and can't be booked.</> : <><Pill cls={tierCls[member.tier]}>{member.tier}</Pill>Member · Hcp {member.hcp.toFixed(1)} · {member.rounds} rounds</>}
        </div>
      )}
      <div className="grid grid-cols-2 gap-4">
        <Field label="Tee"><select className={inputCls} value={tee} onChange={(e) => setTee(+e.target.value)}>{TEES.map((t, i) => <option key={t} value={i}>{t}</option>)}</select></Field>
        <Field label="Time">
          <select className={inputCls} value={m ?? ''} onChange={(e) => setMin(+e.target.value)} disabled={!open.length}>
            {open.length ? open.map((o) => <option key={o} value={o}>{fmtMin(o)}</option>) : <option>No open times</option>}
          </select>
        </Field>
      </div>
      <div className="grid grid-cols-2 gap-4">
        <Field label="Party size">
          <div className="grid grid-cols-4 gap-1">
            {[1, 2, 3, 4].map((n) => <button key={n} disabled={n > settings.maxParty} onClick={() => setParty(n)} className={`h-10 rounded-xl text-sm font-semibold border disabled:opacity-30 ${party === n ? 'bg-forest text-white border-forest' : 'border-gray-200 text-gray-600'}`}>{n}</button>)}
          </div>
        </Field>
        <Field label="Holes">
          <div className="p-1 bg-gray-100 rounded-xl grid grid-cols-2">
            {([18, 9] as const).map((h) => <button key={h} disabled={h === 9 && !settings.flags.nine} onClick={() => setHoles(h)} className={`h-8 rounded-lg text-sm font-semibold disabled:opacity-30 ${holes === h ? 'bg-white text-forest shadow' : 'text-gray-500'}`}>{h}</button>)}
          </div>
        </Field>
      </div>
      <div className="flex justify-between items-center rounded-xl bg-offwhite px-4 py-3 text-sm">
        <span className="text-gray-500">{party} × {currSym}{unit}{m !== undefined && m >= settings.twilightStart && settings.flags.twilight ? ' (twilight)' : isWeekend(offset) ? ' (weekend)' : ''}</span>
        <span className="text-lg font-extrabold">{currSym}{party * unit}</span>
      </div>
    </ModalShell>
  )
}

function BlockModal({ day, offset, onClose, onApply }: { day: DayData; offset: number; onClose: () => void; onApply: (tees: number[], mins: number[], reason: string) => void }) {
  const initStart = Math.max(720, offset === 0 ? NOW_MIN : 420)
  const [tee, setTee] = useState('both')
  const [start, setStart] = useState(initStart)
  const [end, setEnd] = useState(initStart + 60)
  const [reason, setReason] = useState('Maintenance')
  const tees = tee === 'both' ? [0, 1] : [+tee]
  const range = SLOT_MINS.filter((m) => m >= start && m < end)
  const conflicts = tees.flatMap((t) => range.filter((m) => day[`${t}-${m}`]?.kind === 'booking')).length
  const free = tees.flatMap((t) => range.filter((m) => !day[`${t}-${m}`])).length
  const valid = end > start && free > 0
  return (
    <ModalShell title="Block time" desc="Blocked slots can't be booked by members." onClose={onClose}
      footer={<>
        <button onClick={onClose} className={btnGhost}>Cancel</button>
        <button disabled={!valid} onClick={() => onApply(tees, range, reason)} className={btnPrimary}>Block {end > start ? free : 0} slots</button>
      </>}>
      <Field label="Tee">
        <div className="p-1 bg-gray-100 rounded-xl grid grid-cols-3">
          {[['both', 'Both tees'], ['0', 'Hole 1'], ['1', 'Hole 10']].map(([v, l]) => (
            <button key={v} onClick={() => setTee(v)} className={`h-8 rounded-lg text-sm font-semibold ${tee === v ? 'bg-white text-forest shadow' : 'text-gray-500'}`}>{l}</button>
          ))}
        </div>
      </Field>
      <div className="grid grid-cols-2 gap-4">
        <Field label="From"><select className={inputCls} value={start} onChange={(e) => setStart(+e.target.value)}>{SLOT_MINS.map((m) => <option key={m} value={m}>{fmtMin(m)}</option>)}</select></Field>
        <Field label="Until"><select className={inputCls} value={end} onChange={(e) => setEnd(+e.target.value)}>{SLOT_MINS.map((m) => <option key={m} value={m + 10}>{fmtMin(m + 10)}</option>)}</select></Field>
      </div>
      <Field label="Reason">
        <select className={inputCls} value={reason} onChange={(e) => setReason(e.target.value)}>
          {['Maintenance', 'Tournament', 'Lessons', 'Weather', 'Private event'].map((r) => <option key={r}>{r}</option>)}
        </select>
      </Field>
      {end <= start && <p className="text-sm font-semibold text-danger">End time must be after start time.</p>}
      {end > start && conflicts > 0 && <p className="text-sm rounded-xl bg-wait/15 text-[#a85d1f] px-3 py-2.5 font-medium">{conflicts} booked slot{conflicts > 1 ? 's' : ''} in this range will be kept. Cancel them first to block fully.</p>}
    </ModalShell>
  )
}

export default function MasterGridPage() {
  const { focus, go, getDay, book, cancel, setStatus, move, blockRange, setEntry, members, price, waitlist, events, toast, currSym } = useAdmin()
  const [offset, setOffset] = useState(focus?.offset ?? 0)
  const [sel, setSel] = useState<string | null>(focus?.key ?? null)
  const [q, setQ] = useState('')
  const [teeFilter, setTeeFilter] = useState<number | null>(null)
  const [bookAt, setBookAt] = useState<{ tee: number; min: number } | null>(null)
  const [blocking, setBlocking] = useState(false)
  const [moveTo, setMoveTo] = useState('')
  const [confirmCancel, setConfirmCancel] = useState(false)
  const scrollRef = useRef<HTMLDivElement>(null)

  const day = getDay(offset)
  const goDay = (o: number) => { setOffset(o); setSel(null) }
  const pick = (k: string | null) => { setSel(k); setMoveTo(''); setConfirmCancel(false) }

  useEffect(() => {
    const el = scrollRef.current
    if (!el) return
    const target = sel ? parseKey(sel).m : offset === 0 ? NOW_MIN : 420
    el.scrollTop = Math.max(0, ((target - 420) / 10 - 3) * ROW_H)
  }, [offset])

  const s = dayStats(day, offset, price)
  const firstOpen = (() => {
    for (const m of SLOT_MINS) for (const t of [0, 1]) if (!day[`${t}-${m}`] && !isPast(offset, m)) return { tee: t, min: m }
    return null
  })()
  const tees = teeFilter === null ? [0, 1] : [teeFilter]
  const cols = { gridTemplateColumns: `84px repeat(${tees.length}, 1fr)` }
  const selE = sel ? day[sel] : undefined
  const upNext = s.bookings.filter(([k, e]) => e.status === 'Booked' && !isPast(offset, parseKey(k).m)).sort((a, b) => parseKey(a[0]).m - parseKey(b[0]).m || parseKey(a[0]).t - parseKey(b[0]).t).slice(0, 5)
  const dayWait = waitlist.filter((w) => w.offset === offset)
  const dayEvents = events.filter((e) => e.offset === offset)
  const matches = q ? s.bookings.filter(([, e]) => e.name.toLowerCase().includes(q.toLowerCase())).length : 0

  const exportDay = () => downloadCSV(`tee-sheet-${dateKey(offset)}.csv`, [
    ['Time', 'Tee', 'Player', 'Party', 'Holes', 'Status', 'Amount'],
    ...Object.entries(day).sort((a, b) => parseKey(a[0]).m - parseKey(b[0]).m).map(([k, e]) => {
      const { t, m } = parseKey(k)
      return e.kind === 'booking' ? [fmtMin(m), TEES[t], e.name, e.party, e.holes, e.status, e.party * price(e.holes, offset, m)] : [fmtMin(m), TEES[t], `Blocked: ${e.reason}`, '', '', 'Blocked', '']
    }),
  ])

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-hidden flex flex-col">
      <div className="flex flex-col xl:flex-row xl:justify-between xl:items-center gap-3 mb-4">
        <div className="min-w-0">
          <h1 className="text-xl sm:text-2xl font-extrabold">Master Schedule</h1>
          <div className="flex flex-wrap items-center gap-2 mt-1">
            <button onClick={() => goDay(offset - 1)} disabled={offset <= -7} aria-label="Previous day" className="w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-forest disabled:opacity-40 flex items-center justify-center"><Icon name="left" className="w-4 h-4" /></button>
            <span className="text-sm font-semibold text-gray-700 min-w-0 sm:w-48 text-center">{fmtLong(offset)}</span>
            <button onClick={() => goDay(offset + 1)} disabled={offset >= 13} aria-label="Next day" className="w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-forest disabled:opacity-40 flex items-center justify-center"><Icon name="chevron" className="w-4 h-4" /></button>
            {offset === 0 ? <Pill cls="bg-forest/10 text-forest">Today</Pill> : <button onClick={() => goDay(0)} className="text-xs font-semibold text-forest hover:underline">Back to today</button>}
            {isWeekend(offset) && <Pill cls="bg-sand/40 text-[#6b5a3a]">Weekend rates</Pill>}
            {dayEvents.map((e) => <button key={e.id} onClick={() => go('Events', { eventId: e.id })}><Pill cls="bg-[#E8C766]/30 text-[#7a5b00] hover:brightness-95 gap-1"><Icon name="trophy" className="w-3 h-3" />{e.name}</Pill></button>)}
            {dayWait.length > 0 && <button onClick={() => go('Waitlist')}><Pill cls="bg-wait/20 text-[#a85d1f] hover:brightness-95">{dayWait.length} on waitlist</Pill></button>}
          </div>
        </div>
        <div className="flex gap-2">
          <button onClick={exportDay} aria-label="Export day" className={btnSecondary + ' flex items-center gap-2 px-3 sm:px-4'}><Icon name="download" className="w-4 h-4" /><span className="hidden sm:inline">Export</span></button>
          <button disabled={!firstOpen} onClick={() => firstOpen && setBookAt(firstOpen)} className={btnSecondary + ' flex-1 sm:flex-none'}>+ New Booking</button>
          <button disabled={offset < 0} onClick={() => setBlocking(true)} className={btnPrimary + ' flex-1 sm:flex-none disabled:opacity-40'}>+ Block Time</button>
        </div>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Stat label={offset === 0 ? "Today's Revenue" : 'Revenue'} value={`$${s.revenue.toLocaleString()}`} sub={`${s.active.length} paying groups`} />
        <Stat label="Total Players" value={s.players} sub={`${s.bookings.length} groups booked`} />
        <Stat label="Course Utilization %" value={`${s.util}%`} bar={s.util} />
        <Stat label="Checked in" value={`${s.count('Checked in')}/${s.bookings.length}`} sub={`${s.count('No-show')} no-shows`} tone={s.count('No-show') ? 'danger' : 'gray'} />
      </div>

      <div className="flex flex-wrap items-center gap-3 mb-3">
        <div className="relative w-full sm:w-auto">
          <Icon name="search" className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
          <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Find a player on this day" className={inputCls + ' w-full sm:w-64 pl-9'} />
          {q && <span className="absolute right-3 top-2.5 text-xs font-semibold text-gray-400">{matches} found</span>}
        </div>
        <Segmented value={teeFilter} onChange={setTeeFilter} options={[[null, 'Both tees'], [0, 'Hole 1'], [1, 'Hole 10']]} />
        <div className="w-full xl:w-auto xl:ml-auto flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
          <span className="flex items-center gap-1.5"><i className="w-3 h-3 rounded bg-forest/15 border-l-2 border-forest" />Booked</span>
          <span className="flex items-center gap-1.5"><i className="w-3 h-3 rounded bg-forest" />Checked in</span>
          <span className="flex items-center gap-1.5"><i className="w-3 h-3 rounded bg-danger/15 border-l-2 border-danger" />No-show</span>
          <span className="flex items-center gap-1.5"><i className={`w-3 h-3 rounded ${stripes}`} />Blocked</span>
        </div>
      </div>

      <div className="flex flex-col lg:flex-row gap-4 lg:flex-1 lg:min-h-0">
        <div ref={scrollRef} className="h-[65vh] min-h-[360px] lg:h-auto lg:min-h-0 lg:flex-1 bg-white rounded-2xl border border-gray-100 overflow-auto">
          <div className="grid sticky top-0 bg-white border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase z-10" style={cols}>
            <div className="p-3">Time</div>{tees.map((t) => <div key={t} className="p-3 border-l border-gray-50">{TEES[t]}</div>)}
          </div>
          {SLOT_MINS.map((m) => {
            const past = isPast(offset, m)
            const now = offset === 0 && m === NOW_MIN
            return (
              <div key={m} className={`grid border-b ${now ? 'border-t-2 border-t-danger border-b-gray-50' : m % 60 === 50 ? 'border-gray-100' : 'border-gray-50'} ${past ? 'bg-gray-50/60' : ''}`} style={{ ...cols, height: ROW_H }}>
                <div className="px-3 flex items-center gap-1.5">
                  <span className={`text-xs ${m % 60 === 0 ? 'font-bold text-gray-700' : 'text-gray-400'} ${past ? 'opacity-60' : ''}`}>{fmtMin(m)}</span>
                  {now && <span className="text-[9px] font-bold uppercase text-white bg-danger rounded px-1">Now</span>}
                </div>
                {tees.map((t) => {
                  const k = `${t}-${m}`
                  const e = day[k]
                  const dim = q && !(e?.kind === 'booking' && e.name.toLowerCase().includes(q.toLowerCase()))
                  const ring = sel === k ? 'ring-2 ring-forest ring-offset-1' : ''
                  const target = moveTo === k ? 'ring-2 ring-wait ring-offset-1' : ''
                  return (
                    <div key={t} className="p-1 border-l border-gray-50">
                      {e?.kind === 'block' ? (
                        <button onClick={() => pick(k)} className={`w-full h-9 rounded-lg ${stripes} text-xs text-gray-500 font-semibold flex items-center px-3 ${ring} ${dim ? 'opacity-30' : ''}`}>Blocked · {e.reason}</button>
                      ) : e ? (
                        <button onClick={() => pick(k)} className={`w-full h-9 rounded-lg px-3 flex items-center justify-between text-sm text-left transition hover:brightness-95 ${bookingCls[e.status]} ${ring} ${dim ? 'opacity-25' : ''}`}>
                          <span className="font-semibold truncate">{e.name}</span>
                          <span className="text-xs shrink-0 ml-2 opacity-75">{e.party}<span className="hidden sm:inline"> players</span>{e.holes === 9 ? ' · 9h' : ''}</span>
                        </button>
                      ) : past ? <div className="h-9" /> : (
                        <button onClick={() => setBookAt({ tee: t, min: m })}
                          className={`w-full h-9 rounded-lg text-xs font-semibold border border-transparent transition ${target || 'text-gray-300 lg:text-transparent hover:text-forest hover:bg-forest/5 hover:border-dashed hover:border-forest/40'} ${target ? 'bg-wait/10 text-[#a85d1f]' : ''}`}>{target ? 'Move target' : '+ Book'}</button>
                      )}
                    </div>
                  )
                })}
              </div>
            )
          })}
        </div>

        <SidePanel sheet={!!selE} onClose={() => pick(null)} className="lg:w-72">
          {selE?.kind === 'booking' && sel ? (() => {
            const { t, m } = parseKey(sel)
            const member = members.find((u) => u.name === selE.name)
            const unit = price(selE.holes, offset, m)
            const openSlots = SLOT_MINS.flatMap((mm) => [0, 1].map((tt) => `${tt}-${mm}`)).filter((k) => !day[k] && !isPast(offset, parseKey(k).m))
            return <>
              <div className="p-5 border-b border-gray-100">
                <div className="flex justify-between items-start">
                  <div><p className="text-xs font-semibold uppercase text-gray-400">{TEES[t]}</p><p className="text-2xl font-extrabold">{fmtMin(m)}</p></div>
                  <button onClick={() => pick(null)} aria-label="Close" className="w-8 h-8 rounded-lg text-gray-400 hover:bg-gray-100 flex items-center justify-center"><Icon name="x" className="w-4 h-4" /></button>
                </div>
                <button disabled={!member} onClick={() => member && go('Users', { memberId: member.id })} className="flex items-center gap-3 mt-4 text-left group disabled:cursor-default">
                  <div className="w-11 h-11 rounded-full bg-sand text-forest font-bold flex items-center justify-center">{initials(selE.name)}</div>
                  <div>
                    <p className={`font-bold ${member ? 'group-hover:underline' : ''}`}>{selE.name}</p>
                    <p className="text-xs text-gray-500">{member ? `${member.tier} member · Hcp ${member.hcp.toFixed(1)}` : 'Guest'}</p>
                  </div>
                </button>
              </div>
              <div className="flex-1 overflow-auto p-5 space-y-4 text-sm">
                <div className="flex justify-between items-center"><span className="text-gray-500">Status</span>
                  <Pill cls={selE.status === 'Checked in' ? 'bg-forest text-white' : selE.status === 'No-show' ? 'bg-danger/10 text-danger' : 'bg-forest/10 text-forest'}>{selE.status}</Pill></div>
                <div className="flex justify-between items-center"><span className="text-gray-500">Party size</span>
                  <div className="flex items-center gap-2">
                    <button disabled={selE.party <= 1} onClick={() => setEntry(offset, sel, { ...selE, party: selE.party - 1 })} className="w-7 h-7 rounded-lg border border-gray-200 disabled:opacity-30">−</button>
                    <span className="w-4 text-center font-bold">{selE.party}</span>
                    <button disabled={selE.party >= 4} onClick={() => setEntry(offset, sel, { ...selE, party: selE.party + 1 })} className="w-7 h-7 rounded-lg border border-gray-200 disabled:opacity-30">+</button>
                  </div></div>
                <div className="flex justify-between items-center"><span className="text-gray-500">Holes</span>
                  <div className="p-0.5 bg-gray-100 rounded-lg flex">
                    {([18, 9] as const).map((h) => <button key={h} onClick={() => setEntry(offset, sel, { ...selE, holes: h })} className={`px-3 h-7 rounded-md text-xs font-semibold ${selE.holes === h ? 'bg-white text-forest shadow' : 'text-gray-500'}`}>{h}</button>)}
                  </div></div>
                <div className="flex justify-between items-center pt-3 border-t border-gray-100"><span className="text-gray-500">{selE.party} × {currSym}{unit}</span><span className="text-lg font-extrabold">{currSym}{selE.party * unit}</span></div>
                {selE.status === 'Booked' && !isPast(offset, m) && (
                  <div className="pt-3 border-t border-gray-100">
                    <p className="text-gray-500 mb-1.5 flex items-center gap-1.5"><Icon name="move" className="w-4 h-4" />Move to</p>
                    <div className="flex gap-2">
                      <select value={moveTo} onChange={(e) => setMoveTo(e.target.value)} className={inputCls + ' h-9'}>
                        <option value="">Pick an open slot…</option>
                        {openSlots.map((k) => <option key={k} value={k}>{fmtMin(parseKey(k).m)} · {parseKey(k).t === 0 ? 'Hole 1' : 'Hole 10'}</option>)}
                      </select>
                      <button disabled={!moveTo} onClick={() => { move(offset, sel, moveTo); toast(`${selE.name} moved to ${fmtMin(parseKey(moveTo).m)}`); pick(moveTo) }} className="h-9 px-3 rounded-xl bg-gray-900 text-white text-xs font-semibold disabled:opacity-30">Move</button>
                    </div>
                  </div>
                )}
              </div>
              <div className="p-4 border-t border-gray-100 space-y-2">
                {confirmCancel ? (
                  <div className="rounded-xl bg-danger/5 border border-danger/20 p-3 space-y-2">
                    <p className="text-sm font-semibold text-danger">Cancel and refund ${selE.party * unit}?</p>
                    <div className="grid grid-cols-2 gap-2">
                      <button onClick={() => setConfirmCancel(false)} className="h-9 rounded-lg border border-gray-200 bg-white text-sm font-semibold">Keep</button>
                      <button onClick={() => { cancel(offset, sel); pick(null); toast(`Booking for ${selE.name} cancelled`) }} className="h-9 rounded-lg bg-danger text-white text-sm font-semibold">Cancel it</button>
                    </div>
                  </div>
                ) : <>
                  {selE.status === 'Booked' ? <>
                    <button onClick={() => { setStatus(offset, sel, 'Checked in'); toast(`${selE.name} checked in`) }} className={btnPrimary + ' w-full'}>Check in</button>
                    <button onClick={() => { setStatus(offset, sel, 'No-show'); toast(`${selE.name} marked as no-show`) }} className="w-full h-10 rounded-xl border border-gray-200 text-sm font-semibold hover:border-danger hover:text-danger">Mark no-show</button>
                  </> : <button onClick={() => setStatus(offset, sel, 'Booked')} className={btnSecondary + ' w-full'}>Undo · mark as booked</button>}
                  <button onClick={() => setConfirmCancel(true)} className="w-full h-10 rounded-xl text-danger text-sm font-semibold hover:bg-danger/5">Cancel booking</button>
                </>}
              </div>
            </>
          })() : selE?.kind === 'block' && sel ? (() => {
            const { t, m } = parseKey(sel)
            return <>
              <div className="p-5 border-b border-gray-100 flex justify-between items-start">
                <div><p className="text-xs font-semibold uppercase text-gray-400">{TEES[t]}</p><p className="text-2xl font-extrabold">{fmtMin(m)}</p></div>
                <button onClick={() => pick(null)} aria-label="Close" className="w-8 h-8 rounded-lg text-gray-400 hover:bg-gray-100 flex items-center justify-center"><Icon name="x" className="w-4 h-4" /></button>
              </div>
              <div className="flex-1 p-5">
                <div className={`h-20 rounded-xl ${stripes} flex items-center justify-center text-sm font-semibold text-gray-500`}>Blocked · {selE.reason}</div>
                <p className="text-sm text-gray-500 mt-4">Members can't book this slot in the mobile app.</p>
              </div>
              <div className="p-4 border-t border-gray-100">
                <button onClick={() => { setEntry(offset, sel, null); pick(null); toast(`${fmtMin(m)} reopened`) }} className={btnSecondary + ' w-full'}>Remove block</button>
              </div>
            </>
          })() : (
            <div className="p-5 flex-1 overflow-auto">
              <h2 className="font-bold">Day overview</h2>
              <p className="text-xs text-gray-500 mb-4">Click an open slot to book, or a booking to manage it.</p>
              <div className="grid grid-cols-2 gap-2 mb-5">
                {[['Booked', s.count('Booked'), 'text-forest'], ['Checked in', s.count('Checked in'), 'text-forest'], ['No-show', s.count('No-show'), 'text-danger'], ['Blocked', s.blocked, 'text-gray-500']].map(([l, v, c]) => (
                  <div key={l as string} className="bg-offwhite rounded-xl p-3"><p className={`text-xl font-extrabold ${c}`}>{v}</p><p className="text-xs text-gray-500">{l}</p></div>
                ))}
              </div>
              <h3 className="text-xs font-bold uppercase text-gray-400 mb-2">Up next</h3>
              {upNext.length ? upNext.map(([k, e]) => (
                <button key={k} onClick={() => pick(k)} className="w-full flex items-center gap-3 py-2 border-b border-gray-50 last:border-0 text-left hover:bg-gray-50 rounded-lg px-1">
                  <span className="w-16 text-xs font-bold">{fmtMin(parseKey(k).m)}</span>
                  <span className="flex-1 text-sm font-semibold truncate">{e.name}</span>
                  <span className="text-xs text-gray-400">{parseKey(k).t === 0 ? 'H1' : 'H10'}</span>
                </button>
              )) : <p className="text-sm text-gray-400">No upcoming tee times</p>}
              {dayWait.length > 0 && <>
                <h3 className="text-xs font-bold uppercase text-gray-400 mt-5 mb-2">Waitlist · {fmtDate(offset)}</h3>
                {dayWait.map((w) => (
                  <button key={w.id} onClick={() => go('Waitlist')} className="w-full flex items-center gap-3 py-2 text-left hover:bg-gray-50 rounded-lg px-1">
                    <span className="w-16 text-xs font-bold text-[#a85d1f]">~{fmtMin(w.min)}</span>
                    <span className="flex-1 text-sm font-semibold truncate">{w.name}</span>
                    <span className="text-xs text-gray-400">{w.party}p</span>
                  </button>
                ))}
              </>}
            </div>
          )}
        </SidePanel>
      </div>

      {bookAt && <BookingModal day={day} offset={offset} init={bookAt} onClose={() => setBookAt(null)}
        onSave={(k, e) => { book(offset, k, e); setBookAt(null); pick(k); toast(`${e.name} booked at ${fmtMin(parseKey(k).m)}`) }} />}
      {blocking && <BlockModal day={day} offset={offset} onClose={() => setBlocking(false)} onApply={(ts, mins, reason) => {
        const r = blockRange(offset, ts, mins, reason); setBlocking(false)
        toast(`Blocked ${r.blocked} slots · ${reason}${r.conflicts ? ` · ${r.conflicts} bookings kept` : ''}`)
      }} />}
    </main>
  )
}

const dateKey = (o: number) => { const d = new Date(); d.setDate(d.getDate() + o); return d.toISOString().slice(0, 10) }
