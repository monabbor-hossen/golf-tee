import { useMemo, useState } from 'react'
import { Avatar, Icon, PageHeader, SLOT_MINS, Segmented, TEES, btnSecondary, dateFor, downloadCSV, rng } from './shared'
import { dayStats, useAdmin } from './store'

type Range = '7D' | '30D' | '90D' | 'YTD'
type Point = { label: string; greens: number; pro: number; fb: number; groups: number; players: number; noShows: number }
const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

export default function ReportsPage() {
  const { getDay, price, members, go, currSym } = useAdmin()
  const [range, setRange] = useState<Range>('7D')

  const dayPoint = (o: number): Omit<Point, 'label'> => {
    const s = dayStats(getDay(o), o, price)
    const r = rng(o + 3000)
    return { greens: s.revenue, pro: Math.round(s.revenue * 0.2 * (0.8 + 0.4 * r())), fb: Math.round(s.revenue * 0.17 * (0.8 + 0.4 * r())), groups: s.bookings.length, players: s.players, noShows: s.count('No-show') }
  }
  const sumPts = (os: number[], label: string): Point => os.map(dayPoint).reduce((a, p) => ({ label, greens: a.greens + p.greens, pro: a.pro + p.pro, fb: a.fb + p.fb, groups: a.groups + p.groups, players: a.players + p.players, noShows: a.noShows + p.noShows }), { label, greens: 0, pro: 0, fb: 0, groups: 0, players: 0, noShows: 0 })
  const days = (n: number, end = 0) => Array.from({ length: n }, (_, i) => end - n + 1 + i)

  const { series, prev, period } = useMemo(() => {
    const fmt = (o: number, opt: Intl.DateTimeFormatOptions) => dateFor(o).toLocaleDateString('en', opt)
    if (range === '7D' || range === '30D') {
      const n = range === '7D' ? 7 : 30
      const ds = days(n)
      return {
        series: ds.map((o, i) => ({ ...dayPoint(o), label: n === 7 ? fmt(o, { weekday: 'short' }) : i % 5 === 4 || i === n - 1 ? fmt(o, { month: 'short', day: 'numeric' }) : '' })),
        prev: sumPts(days(n, -n), 'prev'),
        period: `${fmt(ds[0], { month: 'short', day: 'numeric' })} – ${fmt(0, { month: 'short', day: 'numeric' })}`,
      }
    }
    if (range === '90D') {
      const weeks = Array.from({ length: 13 }, (_, w) => days(7, -7 * (12 - w)))
      return {
        series: weeks.map((ds, i) => sumPts(ds, i % 2 === 0 || i === 12 ? fmt(ds[0], { month: 'short', day: 'numeric' }) : '')),
        prev: sumPts(days(91, -91), 'prev'),
        period: `${fmt(-90, { month: 'short', day: 'numeric' })} – ${fmt(0, { month: 'short', day: 'numeric' })}`,
      }
    }
    const today = new Date()
    const months = Array.from({ length: today.getMonth() + 1 }, (_, mIdx) => {
      const start = new Date(today.getFullYear(), mIdx, 1)
      const end = mIdx === today.getMonth() ? today : new Date(today.getFullYear(), mIdx + 1, 0)
      const o0 = Math.round((start.getTime() - today.getTime()) / 86400000), o1 = Math.round((end.getTime() - today.getTime()) / 86400000)
      const p = sumPts(Array.from({ length: o1 - o0 + 1 }, (_, i) => o0 + i), start.toLocaleDateString('en', { month: 'short' }))
      const season = [0.35, 0.45, 0.7, 0.9, 1.05, 1.15, 1.2, 1.15, 1.05, 0.95, 0.65, 0.4][mIdx] // golf seasonality
      return { ...p, greens: Math.round(p.greens * season), pro: Math.round(p.pro * season), fb: Math.round(p.fb * season), groups: Math.round(p.groups * season), players: Math.round(p.players * season), noShows: Math.round(p.noShows * season) }
    })
    const tot = months.reduce((a, p) => ({ ...a, greens: a.greens + p.greens, pro: a.pro + p.pro, fb: a.fb + p.fb, groups: a.groups + p.groups, players: a.players + p.players, noShows: a.noShows + p.noShows }), { label: 'prev', greens: 0, pro: 0, fb: 0, groups: 0, players: 0, noShows: 0 })
    const k = 0.91
    return { series: months, prev: { ...tot, greens: tot.greens * k, pro: tot.pro * k, fb: tot.fb * k, groups: tot.groups * 0.95, players: tot.players * 0.95, noShows: tot.noShows * 1.1 }, period: `Jan 1 – ${today.toLocaleDateString('en', { month: 'short', day: 'numeric' })}` }
  }, [range, getDay, price])

  const cur = series.reduce((a, p) => ({ ...a, greens: a.greens + p.greens, pro: a.pro + p.pro, fb: a.fb + p.fb, groups: a.groups + p.groups, players: a.players + p.players, noShows: a.noShows + p.noShows }), { label: '', greens: 0, pro: 0, fb: 0, groups: 0, players: 0, noShows: 0 })
  const total = cur.greens + cur.pro + cur.fb
  const prevTotal = prev.greens + prev.pro + prev.fb
  const pct = (a: number, b: number) => (b ? ((a - b) / b) * 100 : 0)
  const avg = cur.groups ? cur.greens / cur.groups : 0, prevAvg = prev.groups ? prev.greens / prev.groups : 0
  const nsRate = cur.groups ? (cur.noShows / cur.groups) * 100 : 0, prevNs = prev.groups ? (prev.noShows / prev.groups) * 100 : 0
  const kpis: [string, string, number, boolean][] = [
    ['Total Revenue', total >= 10000 ? `${currSym}${(total / 1000).toFixed(1)}k` : `${currSym}${total.toLocaleString()}`, pct(total, prevTotal), true],
    ['Rounds Played', cur.players.toLocaleString(), pct(cur.players, prev.players), true],
    ['Avg. Booking Value', `${currSym}${avg.toFixed(2)}`, pct(avg, prevAvg), true],
    ['No-show Rate', `${nsRate.toFixed(1)}%`, nsRate - prevNs, false],
  ]
  const max = Math.max(1, ...series.map((r) => r.greens + r.pro + r.fb))
  const mix = [['Green fees', cur.greens, '#1E5631'], ['Pro shop', cur.pro, '#D4C4A8'], ['Food & bev', cur.fb, '#F4A261']] as const
  let acc = 0
  const conic = mix.map(([, v, c]) => { const a = acc; acc += (v / Math.max(1, total)) * 100; return `${c} ${a}% ${acc}%` }).join(',')

  // Utilization heatmap from the last 28 days, by weekday × hour
  const heat = useMemo(() => {
    const hrs = Array.from({ length: 11 }, (_, i) => 7 + i)
    const grid = hrs.map(() => WEEKDAYS.map(() => ({ booked: 0, slots: 0 })))
    days(28).forEach((o) => {
      const day = getDay(o)
      const wd = (dateFor(o).getDay() + 6) % 7
      SLOT_MINS.forEach((m) => TEES.forEach((_, t) => {
        const cell = grid[Math.floor(m / 60) - 7]?.[wd]
        if (!cell) return
        cell.slots++
        if (day[`${t}-${m}`]?.kind === 'booking') cell.booked++
      }))
    })
    return { hrs, vals: grid.map((row) => row.map((c) => (c.slots ? Math.round((c.booked / c.slots) * 100) : 0))) }
  }, [getDay])
  const flat = heat.vals.flatMap((row, i) => row.map((v, j) => ({ v, h: heat.hrs[i], d: WEEKDAYS[j] })))
  const peak = flat.reduce((a, b) => (b.v > a.v ? b : a), flat[0])
  const quiet = flat.reduce((a, b) => (b.v < a.v ? b : a), flat[0])
  const best = series.reduce((a, b) => (b.greens + b.pro + b.fb > a.greens + a.pro + a.fb ? b : a), series[0])
  const hourLbl = (h: number) => `${((h + 11) % 12) + 1} ${h < 12 ? 'AM' : 'PM'}`
  const top = [...members].sort((a, b) => b.rounds - a.rounds).slice(0, 5)
  const topMax = Math.max(1, ...top.map((m) => m.rounds))
  const bestDays = series.filter((p) => p.label)

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-auto">
      <PageHeader title="Reports" subtitle={`Performance overview · ${period}`}>
        <Segmented value={range} onChange={setRange} options={[['7D', '7D'], ['30D', '30D'], ['90D', '90D'], ['YTD', 'YTD']]} />
        <button onClick={() => downloadCSV(`report-${range}.csv`, [['Period', 'Green fees', 'Pro shop', 'Food & bev', 'Total', 'Groups', 'Players', 'No-shows'], ...series.map((p, i) => [p.label || `#${i + 1}`, p.greens, p.pro, p.fb, p.greens + p.pro + p.fb, p.groups, p.players, p.noShows])])} className={btnSecondary + ' flex items-center gap-2'}><Icon name="download" className="w-4 h-4" />Export CSV</button>
      </PageHeader>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        {kpis.map(([l, v, c, upGood]) => {
          const good = upGood ? c >= 0 : c <= 0
          return (
            <div key={l} className="bg-white rounded-2xl px-5 py-4 border border-gray-100">
              <p className="text-xs font-medium text-gray-500">{l}</p>
              <p className="text-2xl font-extrabold mt-0.5">{v}</p>
              <p className={`text-xs font-semibold mt-0.5 ${good ? 'text-forest' : 'text-danger'}`}>{c >= 0 ? '+' : ''}{c.toFixed(1)}{l === 'No-show Rate' ? ' pts' : '%'} vs {range === 'YTD' ? 'last year' : 'prior period'}</p>
            </div>
          )
        })}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div className="lg:col-span-2 bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 min-w-0">
          <div className="flex flex-wrap justify-between gap-2 mb-4">
            <h2 className="font-bold">{range === '90D' ? 'Weekly' : range === 'YTD' ? 'Monthly' : 'Daily'} revenue</h2>
            <div className="flex gap-3 text-xs text-gray-500">{mix.map(([l, , c]) => <span key={l} className="flex items-center gap-1"><i className="w-2.5 h-2.5 rounded-sm" style={{ background: c }} />{l}</span>)}</div>
          </div>
          <div className={`h-52 flex items-end border-b border-gray-100 ${series.length > 15 ? 'gap-0.5 sm:gap-1' : 'gap-1.5 sm:gap-3'}`}>
            {series.map((r, i) => {
              const t = r.greens + r.pro + r.fb
              return (
                <div key={i} className="group flex-1 flex flex-col items-center justify-end h-full relative">
                  <span className="absolute text-[11px] font-semibold text-white bg-gray-900 rounded px-1.5 py-0.5 opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10" style={{ bottom: `${(t / max) * 88 + 2}%` }}>{currSym}{t >= 1000 ? `${(t / 1000).toFixed(1)}k` : t}</span>
                  <div className="w-full max-w-12 flex flex-col rounded-t-md overflow-hidden group-hover:opacity-80 transition" style={{ height: `${(t / max) * 88}%` }}>
                    <div className="bg-wait" style={{ flex: r.fb }} /><div className="bg-sand" style={{ flex: r.pro }} /><div className="bg-forest" style={{ flex: r.greens }} />
                  </div>
                </div>
              )
            })}
          </div>
          <div className={`flex mt-2 ${series.length > 15 ? 'gap-0.5 sm:gap-1' : 'gap-1.5 sm:gap-3'}`}>{series.map((r, i) => <span key={i} className="flex-1 text-center text-[10px] text-gray-400 whitespace-nowrap">{r.label}</span>)}</div>
        </div>
        <div className="bg-white rounded-2xl p-5 border border-gray-100">
          <h2 className="font-bold mb-4">Revenue mix</h2>
          <div className="relative w-36 h-36 mx-auto rounded-full" style={{ background: `conic-gradient(${conic})` }}>
            <div className="absolute inset-5 bg-white rounded-full flex flex-col items-center justify-center">
              <p className="text-lg font-extrabold">{total >= 10000 ? `${currSym}${(total / 1000).toFixed(1)}k` : `${currSym}${total.toLocaleString()}`}</p><p className="text-xs text-gray-500">{range}</p>
            </div>
          </div>
          <div className="mt-5 space-y-2">
            {mix.map(([l, v, c]) => (
              <div key={l} className="flex items-center text-sm"><i className="w-2.5 h-2.5 rounded-sm mr-2" style={{ background: c }} />
                <span className="flex-1 text-gray-600">{l}</span><span className="font-semibold mr-3">{currSym}{Math.round(v).toLocaleString()}</span><span className="w-9 text-right text-gray-400 text-xs">{Math.round((v / Math.max(1, total)) * 100)}%</span></div>
            ))}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2 bg-white rounded-2xl p-4 sm:p-5 border border-gray-100 min-w-0">
          <div className="flex justify-between items-baseline mb-1"><h2 className="font-bold">Tee sheet utilization</h2><span className="text-xs text-gray-400">Last 28 days</span></div>
          <p className="text-xs text-gray-500 mb-4">Percent of slots booked by hour and weekday</p>
          <div className="grid grid-cols-[36px_repeat(7,1fr)] sm:grid-cols-[50px_repeat(7,1fr)] gap-0.5 sm:gap-1 text-[10px] sm:text-xs">
            <span />{WEEKDAYS.map((d) => <span key={d} className="text-center text-gray-400 font-medium">{d}</span>)}
            {heat.vals.map((row, i) => [
              <span key={'l' + i} className="text-gray-400 self-center">{hourLbl(heat.hrs[i])}</span>,
              ...row.map((v, j) => (
                <div key={i + '-' + j} title={`${WEEKDAYS[j]} ${hourLbl(heat.hrs[i])} · ${v}% booked`} className={`h-7 rounded sm:rounded-md flex items-center justify-center font-semibold ${v > 55 ? 'text-white' : 'text-forest'}`}
                  style={{ background: `rgba(30,86,49,${0.06 + (v / 100) * 0.94})` }}>{v}</div>
              )),
            ])}
          </div>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4 content-start">
          <div className="bg-white rounded-2xl p-5 border border-gray-100">
            <h2 className="font-bold mb-3">Insights</h2>
            <ul className="space-y-2.5 text-sm">
              <li className="flex gap-2"><i className="w-1.5 h-1.5 rounded-full bg-forest mt-2 shrink-0" /><span>Busiest slot is <b>{peak.d} {hourLbl(peak.h)}</b> at {peak.v}% booked.</span></li>
              <li className="flex gap-2"><i className="w-1.5 h-1.5 rounded-full bg-wait mt-2 shrink-0" /><span><b>{quiet.d} {hourLbl(quiet.h)}</b> is quietest ({quiet.v}%) — a good window for a promo.</span></li>
              {best && bestDays.length > 0 && <li className="flex gap-2"><i className="w-1.5 h-1.5 rounded-full bg-sand mt-2 shrink-0" /><span>Top {range === 'YTD' ? 'month' : range === '90D' ? 'week' : 'day'}: <b>{best.label || 'recent'}</b> with ${(best.greens + best.pro + best.fb).toLocaleString()}.</span></li>}
            </ul>
          </div>
          <div className="bg-white rounded-2xl p-5 border border-gray-100">
            <h2 className="font-bold mb-3">Top members</h2>
            <div className="space-y-2.5">
              {top.map((m, i) => (
                <button key={m.id} onClick={() => go('Users', { memberId: m.id })} className="w-full flex items-center gap-3 text-left hover:bg-gray-50 rounded-lg -mx-1 px-1">
                  <span className="w-4 text-sm font-bold text-gray-300">{i + 1}</span>
                  <Avatar name={m.name} size="sm" />
                  <div className="flex-1 min-w-0"><p className="text-sm font-semibold truncate">{m.name}</p>
                    <div className="h-1.5 mt-1 rounded-full bg-gray-100"><div className="h-full rounded-full bg-forest" style={{ width: `${(m.rounds / topMax) * 100}%` }} /></div></div>
                  <span className="text-sm font-semibold w-7 text-right">{m.rounds}</span>
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </main>
  )
}

