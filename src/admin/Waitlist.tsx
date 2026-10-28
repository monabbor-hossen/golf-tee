import { useState } from 'react'
import {
  type WaitEntry, Avatar, Empty, Field, Icon, ModalShell, PageHeader, Pill, SLOT_MINS, Segmented, Stat,
  btnGhost, btnPrimary, fmtDate, fmtMin, inputCls, isPast,
} from './shared'
import { useAdmin } from './store'

function AddModal({ onClose, onSave }: { onClose: () => void; onSave: (w: Omit<WaitEntry, 'id' | 'addedMin' | 'status'>) => void }) {
  const { members } = useAdmin()
  const [name, setName] = useState('')
  const [offset, setOffset] = useState(0)
  const [min, setMin] = useState(780)
  const [party, setParty] = useState(2)
  const [holes, setHoles] = useState<18 | 9>(18)
  const times = SLOT_MINS.filter((m) => !isPast(offset, m))
  const valid = name.trim() && times.includes(min)
  return (
    <ModalShell title="Add to waitlist" desc="We'll match them to the first opening within an hour of their preferred time." onClose={onClose}
      footer={<><button onClick={onClose} className={btnGhost}>Cancel</button><button disabled={!valid} onClick={() => onSave({ name: name.trim(), offset, min, party, holes })} className={btnPrimary}>Add to waitlist</button></>}>
      <Field label="Player">
        <input autoFocus list="wl-names" className={inputCls} value={name} onChange={(e) => setName(e.target.value)} placeholder="Member or guest name" />
        <datalist id="wl-names">{members.filter((m) => m.status !== 'Suspended').map((m) => <option key={m.id} value={m.name} />)}</datalist>
      </Field>
      <div className="grid grid-cols-2 gap-4">
        <Field label="Day"><select className={inputCls} value={offset} onChange={(e) => setOffset(+e.target.value)}>{Array.from({ length: 14 }, (_, i) => <option key={i} value={i}>{fmtDate(i)}</option>)}</select></Field>
        <Field label="Preferred time"><select className={inputCls} value={times.includes(min) ? min : ''} onChange={(e) => setMin(+e.target.value)}>
          {!times.includes(min) && <option value="">Choose…</option>}{times.map((m) => <option key={m} value={m}>{fmtMin(m)}</option>)}</select></Field>
      </div>
      <div className="grid grid-cols-2 gap-4">
        <Field label="Party size"><div className="grid grid-cols-4 gap-1">{[1, 2, 3, 4].map((n) => <button key={n} onClick={() => setParty(n)} className={`h-10 rounded-xl text-sm font-semibold border ${party === n ? 'bg-forest text-white border-forest' : 'border-gray-200 text-gray-600'}`}>{n}</button>)}</div></Field>
        <Field label="Holes"><div className="p-1 bg-gray-100 rounded-xl grid grid-cols-2">{([18, 9] as const).map((h) => <button key={h} onClick={() => setHoles(h)} className={`h-8 rounded-lg text-sm font-semibold ${holes === h ? 'bg-white text-forest shadow' : 'text-gray-500'}`}>{h}</button>)}</div></Field>
      </div>
    </ModalShell>
  )
}

export default function WaitlistPage() {
  const { waitlist, setWaitlist, getDay, book, go, toast, log, settings } = useAdmin()
  const [range, setRange] = useState<'today' | 'tomorrow' | 'week' | 'all'>('all')
  const [adding, setAdding] = useState(false)
  const inRange = (o: number) => range === 'all' || (range === 'today' ? o === 0 : range === 'tomorrow' ? o === 1 : o <= 6)
  const list = waitlist.filter((w) => inRange(w.offset)).sort((a, b) => a.offset - b.offset || a.min - b.min)

  // Nearest free slot (either tee) within ±60 minutes of the preferred time
  const opening = (w: WaitEntry) => {
    const day = getDay(w.offset)
    const cands = SLOT_MINS.filter((m) => Math.abs(m - w.min) <= 60 && !isPast(w.offset, m)).sort((a, b) => Math.abs(a - w.min) - Math.abs(b - w.min))
    for (const m of cands) for (const t of [0, 1]) if (!day[`${t}-${m}`]) return { t, m }
    return null
  }
  const withOpen = waitlist.filter((w) => opening(w)).length
  const offered = waitlist.filter((w) => w.status === 'Offered').length
  const players = waitlist.reduce((a, w) => a + w.party, 0)

  const offer = (w: WaitEntry, m: number) => {
    setWaitlist((l) => l.map((x) => (x.id === w.id ? { ...x, status: 'Offered', offeredMin: m } : x)))
    toast(`Offer sent to ${w.name} for ${fmtMin(m)}`); log('Waitlist offer', `${w.name} · ${fmtDate(w.offset)} ${fmtMin(m)}`, 'wait')
  }
  const confirm = (w: WaitEntry) => {
    const day = getDay(w.offset)
    const offeredTee = w.offeredMin !== undefined ? [0, 1].find((t) => !day[`${t}-${w.offeredMin}`]) : undefined
    const slot = offeredTee !== undefined ? { t: offeredTee, m: w.offeredMin! } : opening(w)
    if (!slot) { toast('That slot was taken — no opening nearby'); setWaitlist((l) => l.map((x) => (x.id === w.id ? { ...x, status: 'Waiting', offeredMin: undefined } : x))); return }
    const k = `${slot.t}-${slot.m}`
    book(w.offset, k, { kind: 'booking', name: w.name, party: w.party, holes: w.holes, status: 'Booked' })
    setWaitlist((l) => l.filter((x) => x.id !== w.id))
    toast(`${w.name} booked ${fmtDate(w.offset)} ${fmtMin(slot.m)}`)
  }
  const waitingFor = (w: WaitEntry) => { const mins = w.addedMin; return mins < 60 ? (mins < 1 ? 'Just added' : `${mins}m`) : `${Math.floor(mins / 60)}h ${mins % 60}m` }

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-auto">
      <PageHeader title="Waitlist" subtitle={`${waitlist.length} groups · ${players} players waiting for a tee time`}>
        <button onClick={() => setAdding(true)} className={btnPrimary + ' flex-1 sm:flex-none'}>+ Add to waitlist</button>
      </PageHeader>

      {!settings.flags.waitlist && (
        <div className="mb-4 flex flex-wrap items-center gap-3 rounded-2xl bg-wait/15 border border-wait/30 px-4 py-3 text-sm text-[#8a4a12]">
          <Icon name="alert" className="w-5 h-5 shrink-0" /><span className="flex-1 min-w-[200px] font-semibold">The waitlist is turned off for members. They can't join from the mobile app.</span>
          <button onClick={() => go('Settings')} className="font-bold underline">Open settings</button>
        </div>
      )}

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <Stat label="Groups waiting" value={waitlist.length} sub={`${players} players`} />
        <Stat label="Openings available" value={withOpen} sub="Within 60 min of request" tone="forest" />
        <Stat label="Offers out" value={offered} sub="Awaiting reply" tone={offered ? 'wait' : 'gray'} />
        <Stat label="Auto-offer" value={settings.flags.autoOffer ? 'On' : 'Off'} sub="On cancellation" tone={settings.flags.autoOffer ? 'forest' : 'gray'} />
      </div>

      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
        <Segmented value={range} onChange={setRange} options={[['all', 'All'], ['today', 'Today'], ['tomorrow', 'Tomorrow'], ['week', 'Next 7 days']]} />
        <p className="text-xs text-gray-400">Cancellations on the Master Grid automatically notify the closest match.</p>
      </div>

      <div className="bg-white rounded-2xl border border-gray-100 overflow-x-auto">
        {list.length === 0 ? <Empty icon="clock" title="Nobody waiting">New requests from the mobile app will appear here.</Empty> : (
          <table className="w-full text-sm">
            <thead className="text-xs uppercase text-gray-500 text-left border-b border-gray-100 bg-offwhite/60">
              <tr><th className="p-3 pl-4 sm:pl-5">Player</th><th className="p-3 hidden sm:table-cell">Requested</th><th className="p-3 hidden lg:table-cell">Group</th><th className="p-3 hidden xl:table-cell">Waiting</th><th className="p-3 hidden md:table-cell">Nearest opening</th><th className="p-3 hidden md:table-cell">Status</th><th className="p-3 pr-4 sm:pr-5 text-right">Actions</th></tr>
            </thead>
            <tbody>
              {list.map((w) => {
                const o = opening(w)
                return (
                  <tr key={w.id} className="border-b border-gray-50 last:border-0">
                    <td className="p-3 pl-4 sm:pl-5"><div className="flex items-center gap-3"><Avatar name={w.name} /><div className="min-w-0">
                      <p className="font-semibold truncate">{w.name}</p>
                      <p className="sm:hidden text-xs text-gray-500">{fmtDate(w.offset)} ~{fmtMin(w.min)} · {w.party}p · {w.holes}h</p>
                      <p className="md:hidden text-xs font-semibold text-forest">{w.status === 'Offered' ? <span className="text-[#a85d1f]">{fmtMin(w.offeredMin!)} offered</span> : o ? `Opening ${fmtMin(o.m)}` : <span className="text-gray-400 font-normal">No opening yet</span>}</p>
                    </div></div></td>
                    <td className="p-3 hidden sm:table-cell"><p className="font-semibold">{fmtDate(w.offset)}</p><p className="text-xs text-gray-400">around {fmtMin(w.min)}</p></td>
                    <td className="p-3 text-gray-600 hidden lg:table-cell">{w.party} players · {w.holes} holes</td>
                    <td className="p-3 text-gray-500 hidden xl:table-cell">{waitingFor(w)}</td>
                    <td className="p-3 hidden md:table-cell">
                      {w.status === 'Offered' ? <span className="font-semibold text-[#a85d1f]">{fmtMin(w.offeredMin!)} offered</span>
                        : o ? <button onClick={() => go('Master Grid', { offset: w.offset })} className="font-semibold text-forest hover:underline">{fmtMin(o.m)} · {o.t === 0 ? 'Hole 1' : 'Hole 10'}</button>
                          : <span className="text-gray-400">None yet</span>}
                    </td>
                    <td className="p-3 hidden md:table-cell"><Pill cls={w.status === 'Offered' ? 'bg-wait/20 text-[#a85d1f]' : 'bg-gray-100 text-gray-600'}>{w.status}</Pill></td>
                    <td className="p-3 pr-4 sm:pr-5">
                      <div className="flex flex-wrap justify-end gap-2">
                        {w.status === 'Waiting' && o && <button onClick={() => offer(w, o.m)} className="h-8 px-3 rounded-lg border border-gray-200 text-xs font-semibold hover:border-forest">Send offer</button>}
                        {(w.status === 'Offered' || o) && <button onClick={() => confirm(w)} className="h-8 px-3 rounded-lg bg-forest text-white text-xs font-semibold hover:bg-forest-dark">Book now</button>}
                        <button onClick={() => { setWaitlist((l) => l.filter((x) => x.id !== w.id)); toast(`${w.name} removed from waitlist`) }} aria-label="Remove" className="h-8 w-8 rounded-lg text-gray-400 hover:bg-danger/10 hover:text-danger flex items-center justify-center"><Icon name="trash" className="w-4 h-4" /></button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        )}
      </div>

      {adding && <AddModal onClose={() => setAdding(false)} onSave={(w) => {
        setWaitlist((l) => [...l, { ...w, id: Date.now(), addedMin: 0, status: 'Waiting' }]); setAdding(false)
        toast(`${w.name} added to the waitlist`); log('Waitlist joined', `${w.name} · ${fmtDate(w.offset)} ${fmtMin(w.min)}`, 'wait')
      }} />}
    </main>
  )
}
