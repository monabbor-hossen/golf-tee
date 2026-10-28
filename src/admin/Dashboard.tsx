import { useState } from 'react'
import { BookingModal } from './MasterGrid'
import { Avatar, Icon, NOW_MIN, Pill, SLOT_MINS, Stat, TEES, type Page, type Focus, ago, btnPrimary, btnSecondary, fmtDate, fmtMin, isPast, parseKey, toneDot } from './shared'
import { dayStats, useAdmin } from './store'

export default function DashboardPage() {
  const { getDay, price, go, setStatus, book, activity, members, waitlist, events, txns, now, toast, currSym } = useAdmin()
  const [booking, setBooking] = useState(false)
  const today = getDay(0)
  const s = dayStats(today, 0, price)
  const lastWeek = dayStats(getDay(-7), -7, price)
  const delta = (a: number, b: number) => (b ? Math.round(((a - b) / b) * 1000) / 10 : 0)
  const revDelta = delta(s.revenue, lastWeek.revenue)

  const soon = s.bookings.filter(([k, e]) => { const { m } = parseKey(k); return m >= NOW_MIN - 30 && e.status !== 'No-show' }).sort((a, b) => parseKey(a[0]).m - parseKey(b[0]).m || parseKey(a[0]).t - parseKey(b[0]).t).slice(0, 7)
  const late = s.bookings.filter(([k, e]) => e.status === 'Booked' && isPast(0, parseKey(k).m))
  const hours = Array.from({ length: 11 }, (_, i) => 7 + i)
  const byHour = hours.map((h) => s.active.filter(([k]) => Math.floor(parseKey(k).m / 60) === h).reduce((a, [, e]) => a + e.party, 0))
  const maxH = Math.max(1, ...byHour)
  const peakIdx = byHour.indexOf(Math.max(...byHour))
  const nowHour = Math.floor(NOW_MIN / 60)
  const pending = members.filter((m) => m.status === 'Pending')
  const todayWait = waitlist.filter((w) => w.offset === 0)
  const offers = waitlist.filter((w) => w.status === 'Offered')
  const pendingPay = txns.filter((t) => t.status === 'Pending')
  const nextEvent = events.filter((e) => e.offset >= 0).sort((a, b) => a.offset - b.offset)[0]
  const firstOpen = SLOT_MINS.find((m) => !isPast(0, m) && (!today[`0-${m}`] || !today[`1-${m}`]))

  const attention: [string, string, Page, string, Focus?][] = [
    ...(pending.length ? [[`${pending.length} member${pending.length > 1 ? 's' : ''} pending approval`, pending.map((m) => m.name).join(', '), 'Users', 'bg-wait/15 text-[#8a4a12]', { memberId: pending[0].id }] as [string, string, Page, string, Focus]] : []),
    ...(late.length ? [[`${late.length} group${late.length > 1 ? 's' : ''} not checked in`, late.slice(0, 2).map(([k, e]) => `${e.name} ${fmtMin(parseKey(k).m)}`).join(', '), 'Master Grid', 'bg-danger/10 text-danger', { offset: 0, key: late[0][0] }] as [string, string, Page, string, Focus]] : []),
    ...(offers.length ? [[`${offers.length} waitlist offer${offers.length > 1 ? 's' : ''} awaiting reply`, offers.map((w) => w.name).join(', '), 'Waitlist', 'bg-wait/15 text-[#8a4a12]'] as [string, string, Page, string]] : []),
    ...(todayWait.length ? [[`${todayWait.length} on today's waitlist`, 'Fill cancellations fast', 'Waitlist', 'bg-gray-100 text-gray-700'] as [string, string, Page, string]] : []),
    ...(pendingPay.length ? [[`${pendingPay.length} pending payment${pendingPay.length > 1 ? 's' : ''}`, `${currSym}${pendingPay.reduce((a, t) => a + t.amount, 0).toLocaleString()} outstanding`, 'Payments', 'bg-gray-100 text-gray-700'] as [string, string, Page, string]] : []),
    ...(s.blocked ? [[`${s.blocked} slots blocked today`, 'Review maintenance windows', 'Master Grid', 'bg-gray-100 text-gray-700', { offset: 0 }] as [string, string, Page, string, Focus]] : []),
  ]

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-auto">
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-5">
        <div className="min-w-0">
          <h1 className="text-xl sm:text-2xl font-extrabold">Good morning, Admin</h1>
          <p className="text-sm text-gray-500 flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5">
            {new Date().toLocaleDateString('en', { weekday: 'long', month: 'long', day: 'numeric' })}
            <span className="text-gray-300">·</span><Icon name="sun" className="w-4 h-4 text-wait" />68°F, light breeze
            <span className="text-gray-300">·</span><span className="flex items-center gap-1.5 text-forest font-semibold"><i className="w-2 h-2 rounded-full bg-forest animate-pulse" />Course open</span>
          </p>
        </div>
        <div className="flex gap-2">
          <button onClick={() => go('Master Grid', { offset: 0 })} className={btnSecondary + ' flex-1 sm:flex-none'}>Open Master Grid</button>
          <button disabled={firstOpen === undefined} onClick={() => setBooking(true)} className={btnPrimary + ' flex-1 sm:flex-none'}>+ New Booking</button>
        </div>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Stat highlight label="Today's Revenue" value={`${currSym}${s.revenue.toLocaleString()}`} sub={`${revDelta >= 0 ? '+' : ''}${revDelta}% vs last ${new Date().toLocaleDateString('en', { weekday: 'long' })}`} />
        <Stat label="Players Today" value={s.players} sub={`${s.active.length} groups · ${s.count('Checked in')} checked in`} tone="forest" />
        <Stat label="Utilization" value={`${s.util}%`} bar={s.util} />
        <Stat label="Waitlist" value={waitlist.length} sub={`${todayWait.length} for today`} tone={todayWait.length ? 'wait' : 'gray'} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div className="lg:col-span-2 bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 min-w-0">
          <div className="flex justify-between items-center mb-3">
            <h2 className="font-bold">Starting soon</h2>
            <button onClick={() => go('Master Grid', { offset: 0 })} className="text-sm font-semibold text-forest">View all</button>
          </div>
          <div className="divide-y divide-gray-50">
            {soon.map(([k, e]) => {
              const { t, m } = parseKey(k)
              const isLate = e.status === 'Booked' && isPast(0, m)
              const st = e.status === 'Checked in' ? 'Checked in' : isLate ? 'Late' : 'Expected'
              return (
                <div key={k} className="flex items-center gap-2 sm:gap-4 py-2.5">
                  <span className="w-16 sm:w-20 shrink-0 text-xs sm:text-sm font-bold">{fmtMin(m)}</span>
                  <span className="hidden sm:block"><Avatar name={e.name} size="sm" /></span>
                  <button onClick={() => go('Master Grid', { offset: 0, key: k })} className="flex-1 text-left min-w-0"><p className="text-sm font-semibold truncate hover:underline">{e.name}</p><p className="text-xs text-gray-400">{e.party} players · {e.holes} holes · {TEES[t].replace(' Tee', '')}</p></button>
                  <span className="hidden sm:block"><Pill cls={st === 'Checked in' ? 'bg-forest/10 text-forest' : st === 'Late' ? 'bg-wait/20 text-[#a85d1f]' : 'bg-gray-100 text-gray-600'}>{st}</Pill></span>
                  <button disabled={st === 'Checked in'} onClick={() => { setStatus(0, k, 'Checked in'); toast(`${e.name} checked in`) }} className="shrink-0 px-2 sm:px-0 sm:w-20 h-8 rounded-lg text-xs font-semibold text-forest hover:bg-forest/10 disabled:text-gray-300 disabled:hover:bg-transparent">{st === 'Checked in' ? 'Done' : 'Check in'}</button>
                </div>
              )
            })}
            {soon.length === 0 && <p className="text-sm text-gray-400 py-6 text-center">No more tee times today</p>}
          </div>
        </div>
        <div className="bg-white rounded-2xl p-5 border border-gray-100 flex flex-col">
          <h2 className="font-bold mb-3">Recent activity</h2>
          <div className="space-y-3.5 flex-1">
            {activity.slice(0, 7).map((a) => (
              <div key={a.id} className="flex gap-3">
                <i className={`w-2 h-2 rounded-full mt-1.5 shrink-0 ${toneDot[a.tone]}`} />
                <div className="flex-1 min-w-0"><p className="text-sm font-semibold">{a.title}</p><p className="text-xs text-gray-500 truncate">{a.desc}</p></div>
                <span className="text-xs text-gray-400 whitespace-nowrap">{ago(a.at, now)}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2 bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 min-w-0">
          <div className="flex flex-wrap justify-between gap-x-3 mb-4"><h2 className="font-bold">Players on course by hour</h2><span className="text-xs text-gray-500">Peak: {hours[peakIdx] > 12 ? hours[peakIdx] - 12 : hours[peakIdx]} {hours[peakIdx] < 12 ? 'AM' : 'PM'} · {byHour[peakIdx]} players</span></div>
          <div className="h-40 flex items-end gap-1 sm:gap-2">
            {byHour.map((v, i) => {
              const h = hours[i]
              return (
                <div key={h} className="group flex-1 flex flex-col items-center justify-end h-full">
                  <span className="text-[10px] font-semibold text-gray-500 opacity-0 group-hover:opacity-100 mb-1">{v}</span>
                  <div className={`w-full rounded-t-md transition-all ${h < nowHour ? 'bg-forest/30' : h === nowHour ? 'bg-forest' : 'bg-sand'}`} style={{ height: `${(v / maxH) * 100}%`, minHeight: v ? 4 : 0 }} />
                  <span className={`text-[10px] mt-1.5 ${h === nowHour ? 'font-bold text-forest' : 'text-gray-400'}`}>{((h + 11) % 12) + 1}{h < 12 ? 'a' : 'p'}</span>
                </div>
              )
            })}
          </div>
          <div className="flex gap-4 mt-3 text-xs text-gray-500">
            <span className="flex items-center gap-1"><i className="w-2.5 h-2.5 rounded-sm bg-forest/30" />Completed</span>
            <span className="flex items-center gap-1"><i className="w-2.5 h-2.5 rounded-sm bg-forest" />Now</span>
            <span className="flex items-center gap-1"><i className="w-2.5 h-2.5 rounded-sm bg-sand" />Booked ahead</span>
          </div>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4 content-start">
          <div className="bg-white rounded-2xl p-5 border border-gray-100">
            <h2 className="font-bold mb-3">Needs attention</h2>
            <div className="space-y-2">
              {attention.length ? attention.slice(0, 4).map(([t, d, to, c, f]) => (
                <button key={t} onClick={() => go(to, f)} className={`w-full text-left px-3 py-2.5 rounded-xl flex justify-between items-center gap-2 hover:brightness-95 ${c}`}>
                  <div className="min-w-0"><p className="text-sm font-semibold">{t}</p><p className="text-xs opacity-75 truncate">{d}</p></div><Icon name="chevron" className="w-4 h-4 shrink-0" />
                </button>
              )) : <p className="text-sm text-gray-400 flex items-center gap-2"><Icon name="check" className="w-4 h-4 text-forest" />All clear</p>}
            </div>
          </div>
          {nextEvent && (
            <button onClick={() => go('Events', { eventId: nextEvent.id })} className="w-full text-left self-start bg-white rounded-2xl p-5 border border-gray-100 hover:border-forest/40 transition">
              <p className="text-xs font-bold uppercase text-forest flex items-center gap-1.5"><Icon name="trophy" className="w-3.5 h-3.5" />Next event · {fmtDate(nextEvent.offset)}</p>
              <p className="font-bold mt-1">{nextEvent.name}</p>
              <div className="flex items-center gap-2 mt-2">
                <div className="flex-1 h-1.5 rounded-full bg-gray-100"><div className="h-full rounded-full bg-forest" style={{ width: `${Math.min(100, (nextEvent.registered.length / nextEvent.capacity) * 100)}%` }} /></div>
                <span className="text-xs font-semibold text-gray-500">{nextEvent.registered.length}/{nextEvent.capacity}</span>
              </div>
            </button>
          )}
        </div>
      </div>

      {booking && firstOpen !== undefined && <BookingModal day={today} offset={0} init={{ tee: today[`0-${firstOpen}`] ? 1 : 0, min: firstOpen }} onClose={() => setBooking(false)}
        onSave={(k, e) => { book(0, k, e); setBooking(false); toast(`${e.name} booked at ${fmtMin(parseKey(k).m)}`) }} />}
    </main>
  )
}
