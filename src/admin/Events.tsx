import { useRef, useState } from 'react'
import {
  type EventT, Avatar, Empty, Field, Icon, ModalShell, PageHeader, Pill, SLOT_MINS, Segmented, Toggle,
  btnGhost, btnPrimary, btnSecondary, dateFor, downloadCSV, fmtDate, fmtMin, inputCls, useDesktop,
} from './shared'
import { useAdmin } from './store'

const FORMATS = ['Scramble', 'Stroke play', 'Best ball', 'Match play', 'Stableford', 'Clinic']

function CreateModal({ onClose, onSave }: { onClose: () => void; onSave: (e: Omit<EventT, 'id' | 'registered'>) => void }) {
  const { currSym } = useAdmin()
  const [name, setName] = useState('')
  const [offset, setOffset] = useState(7)
  const [start, setStart] = useState(480)
  const [hours, setHours] = useState(4)
  const [format, setFormat] = useState('Scramble')
  const [capacity, setCapacity] = useState(48)
  const [fee, setFee] = useState(75)
  const [blocked, setBlocked] = useState(true)
  const valid = name.trim() && capacity > 0 && fee >= 0
  return (
    <ModalShell wide title="Create event" desc="Tournaments, clinics and leagues members can register for." onClose={onClose}
      footer={<><button onClick={onClose} className={btnGhost}>Cancel</button><button disabled={!valid} onClick={() => onSave({ name: name.trim(), offset, start, hours, format, capacity, fee, open: true, blocked })} className={btnPrimary}>Create event</button></>}>
      <Field label="Event name"><input autoFocus className={inputCls} value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Spring Member-Guest" /></Field>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Field label="Date"><select className={inputCls} value={offset} onChange={(e) => setOffset(+e.target.value)}>{Array.from({ length: 30 }, (_, i) => i + 1).map((i) => <option key={i} value={i}>{fmtDate(i)}</option>)}</select></Field>
        <Field label="Start"><select className={inputCls} value={start} onChange={(e) => setStart(+e.target.value)}>{SLOT_MINS.filter((m) => m % 30 === 0).map((m) => <option key={m} value={m}>{fmtMin(m)}</option>)}</select></Field>
        <Field label="Duration"><select className={inputCls} value={hours} onChange={(e) => setHours(+e.target.value)}>{[1, 2, 3, 4, 5, 6, 8].map((h) => <option key={h} value={h}>{h} hours</option>)}</select></Field>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Field label="Format"><select className={inputCls} value={format} onChange={(e) => setFormat(e.target.value)}>{FORMATS.map((f) => <option key={f}>{f}</option>)}</select></Field>
        <Field label="Capacity"><input type="number" min={1} className={inputCls} value={capacity} onChange={(e) => setCapacity(+e.target.value)} /></Field>
        <Field label="Entry fee"><div className="relative"><span className="absolute left-3 top-2.5 text-gray-400 text-sm">{currSym}</span><input type="number" min={0} className={inputCls + ' pl-7'} value={fee} onChange={(e) => setFee(+e.target.value)} /></div></Field>
      </div>
      <div className="flex items-center justify-between gap-4 rounded-xl bg-offwhite px-4 py-3">
        <div><p className="text-sm font-semibold">Block the tee sheet</p><p className="text-xs text-gray-500">Reserve both tees {fmtMin(start)} – {fmtMin(Math.min(1080, start + hours * 60))} so members can't book over it.</p></div>
        <Toggle on={blocked} onChange={setBlocked} />
      </div>
    </ModalShell>
  )
}

export default function EventsPage() {
  const { events, setEvents, members, focus, go, blockRange, unblockRange, addTxn, toast, log, currSym } = useAdmin()
  const [tab, setTab] = useState<'upcoming' | 'past'>('upcoming')
  const [selId, setSelId] = useState<number | null>(focus?.eventId ?? events.filter((e) => e.offset >= 0).sort((a, b) => a.offset - b.offset)[0]?.id ?? null)
  const [creating, setCreating] = useState(false)
  const [newName, setNewName] = useState('')
  const [q, setQ] = useState('')
  const [confirmDelete, setConfirmDelete] = useState(false)
  const detailRef = useRef<HTMLElement>(null)
  const desktop = useDesktop()
  const list = events.filter((e) => (tab === 'upcoming' ? e.offset >= 0 : e.offset < 0)).sort((a, b) => (tab === 'upcoming' ? a.offset - b.offset : b.offset - a.offset))
  const sel = events.find((e) => e.id === selId)
  const patch = (id: number, p: Partial<EventT>) => setEvents((l) => l.map((e) => (e.id === id ? { ...e, ...p } : e)))
  const slotRange = (e: EventT) => SLOT_MINS.filter((m) => m >= e.start && m < e.start + e.hours * 60)
  const upcoming = events.filter((e) => e.offset >= 0)
  const totalReg = upcoming.reduce((a, e) => a + e.registered.length, 0)
  const totalRev = upcoming.reduce((a, e) => a + e.registered.length * e.fee, 0)

  const register = (e: EventT) => {
    const n = newName.trim()
    if (!n || e.registered.includes(n)) return
    patch(e.id, { registered: [n, ...e.registered] })
    addTxn({ offset: 0, min: 660, name: n, desc: `${e.name} entry`, type: 'Event', method: members.some((m) => m.name === n) ? 'Member account' : 'Visa', amount: e.fee, status: 'Paid' })
    log('Event registration', `${n} · ${e.name}`, 'forest'); toast(`${n} registered for ${e.name}`); setNewName('')
  }

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-hidden flex flex-col">
      <PageHeader title="Events & Tournaments" subtitle={`${upcoming.length} upcoming · ${totalReg} registrations · $${totalRev.toLocaleString()} in entry fees`}>
        <button onClick={() => setCreating(true)} className={btnPrimary + ' flex-1 sm:flex-none'}>+ Create event</button>
      </PageHeader>

      <div className="flex flex-col lg:flex-row gap-4 lg:flex-1 lg:min-h-0">
        <div className="w-full lg:w-[320px] xl:w-[380px] flex flex-col lg:min-h-0 lg:shrink-0">
          <div className="mb-3"><Segmented value={tab} onChange={setTab} options={[['upcoming', `Upcoming · ${upcoming.length}`], ['past', 'Past']]} /></div>
          <div className="flex lg:flex-col gap-2.5 overflow-x-auto lg:overflow-x-visible lg:overflow-y-auto lg:flex-1 snap-x pb-1 lg:pb-0 lg:pr-1 no-scrollbar">
            {list.length === 0 && <Empty icon="trophy" title="No events">Create one to get members on the course.</Empty>}
            {list.map((e) => {
              const pct = Math.round((e.registered.length / e.capacity) * 100)
              const d = dateFor(e.offset)
              return (
                <button key={e.id} onClick={() => { setSelId(e.id); setConfirmDelete(false); if (!desktop) window.setTimeout(() => detailRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50) }} className={`w-[85%] sm:w-[340px] lg:w-full shrink-0 snap-start text-left bg-white rounded-2xl border p-4 flex gap-4 transition ${selId === e.id ? 'border-forest ring-2 ring-forest/15' : 'border-gray-100 hover:border-gray-200'}`}>
                  <div className={`w-14 shrink-0 rounded-xl text-center py-2 ${e.offset < 0 ? 'bg-gray-100 text-gray-500' : 'bg-forest/10 text-forest'}`}>
                    <p className="text-[10px] font-bold uppercase">{d.toLocaleDateString('en', { month: 'short' })}</p>
                    <p className="text-xl font-extrabold leading-tight">{d.getDate()}</p>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <p className="font-bold truncate">{e.name}</p>
                      {e.offset >= 0 && <Pill cls={!e.open ? 'bg-gray-100 text-gray-500' : pct >= 90 ? 'bg-wait/20 text-[#a85d1f]' : 'bg-forest/10 text-forest'}>{!e.open ? 'Closed' : pct >= 100 ? 'Full' : pct >= 90 ? 'Almost full' : 'Open'}</Pill>}
                    </div>
                    <p className="text-xs text-gray-500">{e.format} · {fmtMin(e.start)} · {currSym}{e.fee}</p>
                    <div className="flex items-center gap-2 mt-2">
                      <div className="flex-1 h-1.5 rounded-full bg-gray-100"><div className={`h-full rounded-full ${pct >= 90 ? 'bg-wait' : 'bg-forest'}`} style={{ width: `${Math.min(100, pct)}%` }} /></div>
                      <span className="text-xs font-semibold text-gray-500">{e.registered.length}/{e.capacity}</span>
                    </div>
                  </div>
                </button>
              )
            })}
          </div>
        </div>

        <section ref={detailRef} className="scroll-mt-20 lg:flex-1 min-w-0 bg-white rounded-2xl border border-gray-100 flex flex-col overflow-hidden">
          {!sel ? <div className="flex-1 flex items-center justify-center"><Empty icon="trophy" title="Select an event" /></div> : (() => {
            const pct = Math.round((sel.registered.length / sel.capacity) * 100)
            const past = sel.offset < 0
            const full = sel.registered.length >= sel.capacity
            const regs = sel.registered.filter((n) => n.toLowerCase().includes(q.toLowerCase()))
            return <>
              <div className="p-4 sm:p-6 border-b border-gray-100 bg-gradient-to-br from-forest/5 to-transparent">
                <div className="flex flex-col md:flex-row md:justify-between md:items-start gap-3 md:gap-4">
                  <div className="min-w-0">
                    <p className="text-xs font-bold uppercase tracking-wider text-forest">{sel.format}</p>
                    <h2 className="text-xl sm:text-2xl font-extrabold mt-0.5">{sel.name}</h2>
                    <p className="text-sm text-gray-500 mt-1">{dateFor(sel.offset).toLocaleDateString('en', { weekday: 'long', month: 'long', day: 'numeric' })} · {fmtMin(sel.start)} – {fmtMin(Math.min(1080, sel.start + sel.hours * 60))}</p>
                  </div>
                  <div className="flex flex-wrap gap-2 shrink-0">
                    <button onClick={() => downloadCSV(`${sel.name.replace(/\s+/g, '-').toLowerCase()}-registrants.csv`, [['Name', 'Member'], ...sel.registered.map((n) => [n, members.some((m) => m.name === n) ? 'Yes' : 'No'])])} className={btnSecondary + ' flex items-center gap-2'}><Icon name="download" className="w-4 h-4" />Registrants</button>
                    {!past && <button onClick={() => go('Master Grid', { offset: sel.offset })} className={btnSecondary}>View tee sheet</button>}
                  </div>
                </div>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mt-5">
                  {[['Registered', `${sel.registered.length}/${sel.capacity}`], ['Fill rate', `${pct}%`], ['Entry fees', `${currSym}${(sel.registered.length * sel.fee).toLocaleString()}`], ['Spots left', Math.max(0, sel.capacity - sel.registered.length)]].map(([l, v]) => (
                    <div key={l as string} className="bg-white rounded-xl border border-gray-100 px-3 sm:px-4 py-3"><p className="text-xs text-gray-500">{l}</p><p className="text-lg sm:text-xl font-extrabold">{v}</p></div>
                  ))}
                </div>
              </div>

              <div className="flex flex-col md:flex-row lg:flex-1 lg:min-h-0">
                <div className="flex-1 flex flex-col min-w-0 lg:min-h-0 p-4 sm:p-5">
                  <div className="flex flex-wrap items-center gap-2 mb-3">
                    <h3 className="font-bold flex-1">Registrants</h3>
                    <div className="relative w-full sm:w-48"><Icon name="search" className="w-4 h-4 absolute left-3 top-3 text-gray-400" /><input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search" className={inputCls + ' pl-9'} /></div>
                  </div>
                  {!past && (
                    <div className="flex gap-2 mb-3">
                      <input list="ev-names" value={newName} onChange={(e) => setNewName(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && register(sel)} disabled={full || !sel.open} placeholder={full ? 'Event is full' : !sel.open ? 'Registration closed' : 'Add a player…'} className={inputCls} />
                      <datalist id="ev-names">{members.filter((m) => !sel.registered.includes(m.name)).map((m) => <option key={m.id} value={m.name} />)}</datalist>
                      <button disabled={!newName.trim() || full || !sel.open} onClick={() => register(sel)} className={btnPrimary}>Register</button>
                    </div>
                  )}
                  <div className="max-h-96 lg:max-h-none lg:flex-1 overflow-auto -mx-1 px-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-1 xl:grid-cols-2 gap-x-4 content-start">
                    {regs.map((n, i) => {
                      const member = members.find((m) => m.name === n)
                      return (
                        <div key={n + i} className="group flex items-center gap-3 py-2 border-b border-gray-50">
                          <Avatar name={n} size="sm" />
                          <div className="flex-1 min-w-0"><p className="text-sm font-semibold truncate">{n}</p><p className="text-[11px] text-gray-400">{member ? `${member.tier} · Hcp ${member.hcp.toFixed(1)}` : 'Guest'}</p></div>
                          {!past && <button onClick={() => { patch(sel.id, { registered: sel.registered.filter((x) => x !== n) }); toast(`${n} removed`) }} aria-label={`Remove ${n}`} className="lg:opacity-0 lg:group-hover:opacity-100 w-7 h-7 rounded-lg text-gray-400 hover:text-danger hover:bg-danger/10 flex items-center justify-center"><Icon name="x" className="w-3.5 h-3.5" /></button>}
                        </div>
                      )
                    })}
                    {regs.length === 0 && <p className="text-sm text-gray-400 col-span-2 py-6 text-center">No registrants{q ? ' match' : ' yet'}</p>}
                  </div>
                </div>

                {!past && (
                  <aside className="w-full md:w-64 md:shrink-0 border-t md:border-t-0 md:border-l border-gray-100 p-4 sm:p-5 space-y-4 overflow-auto">
                    <div className="flex items-center justify-between gap-3">
                      <div><p className="text-sm font-semibold">Registration open</p><p className="text-xs text-gray-500">Members can sign up in the app</p></div>
                      <Toggle on={sel.open} onChange={(v) => { patch(sel.id, { open: v }); toast(`Registration ${v ? 'opened' : 'closed'}`) }} />
                    </div>
                    <div className="flex items-center justify-between gap-3">
                      <div><p className="text-sm font-semibold">Tee sheet blocked</p><p className="text-xs text-gray-500">Both tees during the event</p></div>
                      <Toggle on={sel.blocked} onChange={(v) => {
                        if (v) { const r = blockRange(sel.offset, [0, 1], slotRange(sel), 'Tournament'); toast(`Blocked ${r.blocked} slots${r.conflicts ? ` · ${r.conflicts} bookings in the way` : ''}`) }
                        else { unblockRange(sel.offset, sel.start, sel.start + sel.hours * 60, 'Tournament'); toast('Tee sheet reopened') }
                        patch(sel.id, { blocked: v })
                      }} />
                    </div>
                    <div className="rounded-xl bg-offwhite p-3 text-xs text-gray-600 space-y-1">
                      <p><span className="font-semibold">Capacity:</span> {sel.capacity} players</p>
                      <p><span className="font-semibold">Entry fee:</span> {currSym}{sel.fee} per player</p>
                      <p><span className="font-semibold">Duration:</span> {sel.hours} hours</p>
                    </div>
                    <button onClick={() => toast(`Reminder sent to ${sel.registered.length} registrants`)} className={btnSecondary + ' w-full flex items-center justify-center gap-2'}><Icon name="mail" className="w-4 h-4" />Email registrants</button>
                    {confirmDelete ? (
                      <div className="rounded-xl bg-danger/5 border border-danger/20 p-3 space-y-2">
                        <p className="text-sm font-semibold text-danger">Delete this event?</p>
                        <div className="grid grid-cols-2 gap-2">
                          <button onClick={() => setConfirmDelete(false)} className="h-9 rounded-lg border border-gray-200 bg-white text-sm font-semibold">Keep</button>
                          <button onClick={() => {
                            if (sel.blocked) unblockRange(sel.offset, sel.start, sel.start + sel.hours * 60, 'Tournament')
                            setEvents((l) => l.filter((e) => e.id !== sel.id)); setSelId(null); setConfirmDelete(false); toast(`${sel.name} deleted`); log('Event deleted', sel.name, 'danger')
                          }} className="h-9 rounded-lg bg-danger text-white text-sm font-semibold">Delete</button>
                        </div>
                      </div>
                    ) : <button onClick={() => setConfirmDelete(true)} className="w-full h-10 rounded-xl text-danger text-sm font-semibold hover:bg-danger/5">Delete event</button>}
                  </aside>
                )}
              </div>
            </>
          })()}
        </section>
      </div>

      {creating && <CreateModal onClose={() => setCreating(false)} onSave={(e) => {
        const id = Date.now()
        setEvents((l) => [...l, { ...e, id, registered: [] }])
        let extra = ''
        if (e.blocked) { const r = blockRange(e.offset, [0, 1], SLOT_MINS.filter((m) => m >= e.start && m < e.start + e.hours * 60), 'Tournament'); extra = ` · ${r.blocked} slots blocked` }
        setCreating(false); setSelId(id); setTab('upcoming'); toast(`${e.name} created${extra}`); log('Event created', `${e.name} · ${fmtDate(e.offset)}`, 'forest')
      }} />}
    </main>
  )
}
