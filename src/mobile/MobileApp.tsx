import { useEffect, useState } from 'react'

/* ---------- data & helpers ---------- */

const IMG_HERO = 'https://images.unsplash.com/photo-1758190153146-a1507e2e000d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080'
const IMG_TWILIGHT = 'https://images.unsplash.com/photo-1786375013010-51e39f91be0b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080'
const IMG_NEWS = 'https://images.unsplash.com/photo-1761141987961-ed96a841fe21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600'

const NOW = 660 // simulated 11:00 AM, matches admin
const SLOTS = Array.from({ length: 66 }, (_, i) => 420 + i * 10)
const COURSE = 'Pine Valley GC'
const PARS = [4, 5, 3, 4, 4, 4, 3, 5, 4, 4, 3, 5, 4, 4, 3, 4, 5, 4]
const YARDS = [412, 528, 178, 395, 441, 367, 165, 545, 420, 404, 190, 512, 388, 430, 155, 376, 560, 445]
const FRIENDS = ['Mina Lee', 'Raj Patel', 'Daniel Brown', 'Priya Shah', 'Lucia Rossi']
const ADDONS = [
  { id: 'cart', label: 'Golf cart', desc: 'Per player', price: 20, perPlayer: true },
  { id: 'clubs', label: 'Club rental', desc: 'Premium set', price: 35, perPlayer: false },
  { id: 'balls', label: 'Range balls', desc: 'Large bucket', price: 8, perPlayer: false },
]

const dateFor = (o: number) => { const d = new Date(); d.setDate(d.getDate() + o); return d }
const isWeekend = (o: number) => [0, 6].includes(dateFor(o).getDay())
const fmtMin = (m: number) => { const h = Math.floor(m / 60); return `${((h + 11) % 12) + 1}:${String(m % 60).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}` }
const fmtDay = (o: number) => (o === 0 ? 'Today' : o === 1 ? 'Tomorrow' : dateFor(o).toLocaleDateString('en', { weekday: 'short', month: 'short', day: 'numeric' }))
const fmtDayLong = (o: number) => dateFor(o).toLocaleDateString('en', { weekday: 'long', month: 'long', day: 'numeric' })
const priceFor = (holes: 18 | 9, o: number, m: number) =>
  m >= 900 ? (holes === 18 ? 55 : 35) : isWeekend(o) ? (holes === 18 ? 110 : 62) : holes === 18 ? 85 : 48
const relDays = (o: number) => (o === 0 ? 'Today' : o === 1 ? 'Tomorrow' : o > 0 ? `In ${o} days` : `${-o} days ago`)
const initials = (n: string) => n.split(' ').map((x) => x[0]).join('').slice(0, 2)

type SlotState = 'available' | 'selected' | 'locked' | 'booked' | 'waitlist'
function slotState(day: number, m: number, holes: 18 | 9): Exclude<SlotState, 'selected'> {
  if (day === 0 && m < NOW) return 'locked'
  const h = ((day + 20) * 131 + m * 7 + (holes === 9 ? 3 : 0)) % 17
  return h < 5 ? 'booked' : h < 7 ? 'waitlist' : h === 7 ? 'locked' : 'available'
}

type Booking = { id: number; day: number; min: number; holes: 18 | 9; players: number; partners: string[]; addons: string[]; total: number; card: string; status: 'upcoming' | 'past' | 'cancelled'; score?: number; tee: string }
type Wait = { id: number; day: number; min: number; holes: 18 | 9; players: number; position: number }
type Card = { id: string; brand: string; last4: string; exp: string; isDefault: boolean }
type Prefs = { day: number; holes: 18 | 9; players: number; tod: 'All' | 'Morning' | 'Midday' | 'Twilight'; min: number | null }
type Screen = 'home' | 'book' | 'bookings' | 'score' | 'profile' | 'checkout' | 'success' | 'detail' | 'login' | 'set-password'
export type MemberAuth = { id: number; name: string; email: string; tier: string; hcp: number; passwordSet?: boolean }

const seedBookings: Booking[] = [
  { id: 1, day: 2, min: 450, holes: 18, players: 4, partners: ['Mina Lee', 'Daniel Brown'], addons: ['cart'], total: 386, card: 'v', status: 'upcoming', tee: 'Hole 1' },
  { id: 2, day: 6, min: 540, holes: 9, players: 2, partners: ['Priya Shah'], addons: [], total: 86, card: 'v', status: 'upcoming', tee: 'Hole 10' },
  { id: 3, day: -10, min: 500, holes: 18, players: 4, partners: [], addons: ['cart'], total: 386, card: 'v', status: 'past', score: 82, tee: 'Hole 1' },
  { id: 4, day: -24, min: 460, holes: 18, players: 2, partners: [], addons: [], total: 170, card: 'm', status: 'past', score: 79, tee: 'Hole 1' },
  { id: 5, day: -38, min: 550, holes: 18, players: 3, partners: [], addons: ['balls'], total: 263, card: 'v', status: 'past', score: 85, tee: 'Hole 10' },
]
const seedWait: Wait[] = [
  { id: 1, day: 1, min: 480, holes: 18, players: 2, position: 1 },
  { id: 2, day: 4, min: 430, holes: 18, players: 4, position: 3 },
]
const seedCards: Card[] = [
  { id: 'v', brand: 'Visa', last4: '4242', exp: '09/27', isDefault: true },
  { id: 'm', brand: 'Mastercard', last4: '8810', exp: '03/26', isDefault: false },
]

/* ---------- primitives ---------- */

const ICONS: Record<string, string> = {
  home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z',
  calendar: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
  list: 'M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01',
  flag: 'M5 21V4M5 4h11l-2 4 2 4H5',
  user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
  back: 'M15 18l-6-6 6-6',
  chevron: 'M9 18l6-6-6-6',
  check: 'M5 12l5 5 9-11',
  x: 'M6 6l12 12M18 6 6 18',
  bell: 'M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8M10 21h4',
  sun: 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4',
  wind: 'M3 8h11a3 3 0 1 0-3-3M3 16h15a3 3 0 1 1-3 3M3 12h18',
  clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
  users: 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7M22 21a7 7 0 0 0-4-6.3',
  card: 'M2 6h20v12H2zM2 10h20M6 15h4',
  pin: 'M12 21s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12zM12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
  plus: 'M12 5v14M5 12h14',
  minus: 'M5 12h14',
  drop: 'M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z',
  cart: 'M3 4h3l2 11h10l2-7H7M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM17 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2z',
  tag: 'M3 12V3h9l9 9-9 9zM7.5 7.5h.01',
}
function Icon({ name, className = 'w-5 h-5' }: { name: string; className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden>
      <path d={ICONS[name]} />
    </svg>
  )
}

function TimeSlotChip({ time, price, state, onClick }: { time: string; price?: number; state: SlotState; onClick?: () => void }) {
  const styles: Record<SlotState, string> = {
    available: 'border-forest/60 text-forest bg-white active:scale-95 hover:bg-forest/5',
    selected: 'border-forest bg-forest text-white shadow-lg shadow-forest/30 scale-[1.03]',
    locked: 'border-gray-200 bg-gray-100 text-gray-300 cursor-not-allowed',
    booked: 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed',
    waitlist: 'border-wait bg-wait/15 text-[#a85d1f] active:scale-95',
  }
  const disabled = state === 'locked' || state === 'booked'
  const [t, ap] = time.split(' ')
  return (
    <button disabled={disabled} onClick={onClick} className={`h-[58px] rounded-2xl border-[1.5px] flex flex-col items-center justify-center transition ${styles[state]}`}>
      <span className={`text-base font-semibold leading-tight ${state === 'booked' ? 'line-through' : ''}`}>{t}<span className="text-[10px] font-medium ml-0.5">{ap}</span></span>
      <span className="text-[10px] font-semibold uppercase tracking-wide opacity-80">
        {state === 'waitlist' ? 'Waitlist' : state === 'booked' ? 'Booked' : state === 'locked' ? 'Closed' : `$${price}`}
      </span>
    </button>
  )
}

function PrimaryButton({ children, variant = 'active', onClick, className = '' }: { children: React.ReactNode; variant?: 'active' | 'disabled' | 'loading'; onClick?: () => void; className?: string }) {
  return (
    <button disabled={variant !== 'active'} onClick={onClick}
      className={`w-full h-14 rounded-2xl font-semibold text-base flex items-center justify-center gap-2 transition active:scale-[0.98] ${
        variant === 'disabled' ? 'bg-gray-200 text-gray-400' : 'bg-forest text-white hover:bg-forest-dark'} ${className}`}>
      {variant === 'loading' && <span className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />}
      {children}
    </button>
  )
}

function HorizontalDateSelector({ sel, onSel }: { sel: number; onSel: (i: number) => void }) {
  return (
    <div className="flex gap-2 overflow-x-auto no-scrollbar px-5 py-1">
      {Array.from({ length: 14 }, (_, i) => {
        const d = dateFor(i)
        return (
          <button key={i} onClick={() => onSel(i)}
            className={`shrink-0 w-[52px] py-2.5 rounded-2xl flex flex-col items-center transition ${
              sel === i ? 'bg-forest text-white shadow-lg shadow-forest/30' : 'bg-white text-gray-700 border border-gray-100'}`}>
            <span className={`text-[11px] ${sel === i ? 'text-white/70' : 'text-gray-400'}`}>{d.toLocaleDateString('en', { weekday: 'short' })}</span>
            <span className="text-lg font-bold leading-tight">{d.getDate()}</span>
            <i className={`w-1 h-1 rounded-full mt-0.5 ${isWeekend(i) ? (sel === i ? 'bg-sand' : 'bg-wait') : 'bg-transparent'}`} />
          </button>
        )
      })}
    </div>
  )
}

function Sheet({ open, onClose, title, children }: { open: boolean; onClose: () => void; title: string; children: React.ReactNode }) {
  return (
    <div className={`absolute inset-0 z-40 transition ${open ? 'pointer-events-auto' : 'pointer-events-none'}`}>
      <div onClick={onClose} className={`absolute inset-0 bg-black/40 transition-opacity ${open ? 'opacity-100' : 'opacity-0'}`} />
      <div className={`absolute inset-x-0 bottom-0 bg-white rounded-t-[28px] p-5 pb-8 transition-transform duration-300 ${open ? 'translate-y-0' : 'translate-y-full'}`}>
        <div className="w-10 h-1 rounded-full bg-gray-200 mx-auto mb-4" />
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-extrabold">{title}</h3>
          <button onClick={onClose} aria-label="Close" className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500"><Icon name="x" className="w-4 h-4" /></button>
        </div>
        {children}
      </div>
    </div>
  )
}

function MToggle({ on, onChange }: { on: boolean; onChange: (v: boolean) => void }) {
  return (
    <button role="switch" aria-checked={on} onClick={() => onChange(!on)} className={`relative w-11 h-6 rounded-full transition shrink-0 ${on ? 'bg-forest' : 'bg-gray-300'}`}>
      <span className={`absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-all ${on ? 'left-[22px]' : 'left-0.5'}`} />
    </button>
  )
}

function QR({ seed, className = 'w-32 h-32' }: { seed: number; className?: string }) {
  const n = 21
  let r = (seed * 7919 + 13) % 233280
  const cells: [number, number][] = []
  const finder = (x: number, y: number) => {
    for (const [ox, oy] of [[0, 0], [14, 0], [0, 14]]) {
      const dx = x - ox, dy = y - oy
      if (dx >= 0 && dx < 7 && dy >= 0 && dy < 7) { const d = Math.max(Math.abs(dx - 3), Math.abs(dy - 3)); return d === 2 ? 0 : 1 }
    }
    return -1
  }
  for (let y = 0; y < n; y++) for (let x = 0; x < n; x++) {
    const f = finder(x, y)
    r = (r * 9301 + 49297) % 233280
    if (f === 1 || (f === -1 && r / 233280 > 0.52)) cells.push([x, y])
  }
  return (
    <svg viewBox="-1 -1 23 23" className={className} shapeRendering="crispEdges" aria-label="Check-in QR code">
      <rect x={-1} y={-1} width={23} height={23} fill="white" />
      {cells.map(([x, y]) => <rect key={x + '-' + y} x={x} y={y} width={1} height={1} fill="#111" />)}
    </svg>
  )
}

function TopBar({ title, onBack, right }: { title: string; onBack?: () => void; right?: React.ReactNode }) {
  return (
    <div className="flex items-center gap-3 px-5 pt-2 pb-3">
      {onBack && <button onClick={onBack} aria-label="Back" className="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center"><Icon name="back" /></button>}
      <h1 className="flex-1 text-2xl font-extrabold text-gray-900">{title}</h1>
      {right}
    </div>
  )
}

function Section({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
  return (
    <section className="px-5">
      <div className="flex justify-between items-baseline mb-2.5"><h2 className="font-bold text-gray-900">{title}</h2>{action}</div>
      {children}
    </section>
  )
}

/* ---------- app context ---------- */

type Ctx = {
  screen: Screen; nav: (s: Screen, id?: number) => void; back: () => void; detailId: number | null
  bookings: Booking[]; setBookings: React.Dispatch<React.SetStateAction<Booking[]>>
  waitlist: Wait[]; setWaitlist: React.Dispatch<React.SetStateAction<Wait[]>>
  cards: Card[]; setCards: React.Dispatch<React.SetStateAction<Card[]>>
  prefs: Prefs; setPrefs: React.Dispatch<React.SetStateAction<Prefs>>
  scores: number[]; setScores: React.Dispatch<React.SetStateAction<number[]>>
  hcpHistory: number[]; setHcpHistory: React.Dispatch<React.SetStateAction<number[]>>
  toast: (m: string) => void
  member: MemberAuth | null
  setMember: React.Dispatch<React.SetStateAction<MemberAuth | null>>
}

/* ---------- screens ---------- */

function HomeScreen({ ctx }: { ctx: Ctx }) {
  const next = ctx.bookings.filter((b) => b.status === 'upcoming').sort((a, b) => a.day - b.day || a.min - b.min)[0]
  const ready = ctx.waitlist.find((w) => w.position === 1)
  const played = ctx.scores.filter(Boolean).length
  return (
    <div className="pb-6 space-y-6">
      <div className="px-5 pt-2 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-11 h-11 rounded-full bg-sand flex items-center justify-center font-bold text-forest">{initials(ctx.member?.name ?? 'James Carter')}</div>
          <div><p className="text-sm text-gray-500">Good morning,</p><h1 className="text-xl font-extrabold text-gray-900 leading-tight">{ctx.member?.name ?? 'James Carter'}</h1></div>
        </div>
        <button onClick={() => ctx.nav('bookings')} aria-label="Notifications" className="relative w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center">
          <Icon name="bell" />{ready && <i className="absolute top-2 right-2.5 w-2 h-2 rounded-full bg-danger ring-2 ring-white" />}
        </button>
      </div>

      <div className="px-5">
        <div className="relative h-52 rounded-[28px] overflow-hidden text-white shadow-xl shadow-forest/20">
          <img src={IMG_HERO} alt="Tree-lined fairway at Pine Valley" className="absolute inset-0 w-full h-full object-cover" />
          <div className="absolute inset-0 bg-gradient-to-t from-[#0c2414] via-[#0c2414]/60 to-transparent" />
          <div className="relative h-full p-5 flex flex-col justify-end">
            {next ? <>
              <span className="self-start text-[11px] font-bold uppercase tracking-wider bg-white/20 backdrop-blur px-2.5 py-1 rounded-full mb-auto">Next tee time · {relDays(next.day)}</span>
              <p className="text-sm text-white/80">{fmtDayLong(next.day)}</p>
              <div className="flex items-end justify-between">
                <div><p className="text-3xl font-extrabold">{fmtMin(next.min)}</p><p className="text-sm text-white/80">{next.holes} holes · {next.players} players · {next.tee}</p></div>
                <button onClick={() => ctx.nav('detail', next.id)} className="h-10 px-4 rounded-full bg-white text-forest text-sm font-bold">View pass</button>
              </div>
            </> : <>
              <p className="text-2xl font-extrabold">No rounds booked</p>
              <p className="text-sm text-white/80 mb-3">Perfect conditions this week — grab a time.</p>
              <button onClick={() => ctx.nav('book')} className="self-start h-10 px-4 rounded-full bg-white text-forest text-sm font-bold">Book a tee time</button>
            </>}
          </div>
        </div>
      </div>

      {ready && (
        <div className="px-5">
          <button onClick={() => { ctx.setPrefs((p) => ({ ...p, day: ready.day, holes: ready.holes, players: ready.players, min: ready.min, tod: 'All' })); ctx.nav('checkout') }}
            className="w-full flex items-center gap-3 p-4 rounded-2xl bg-wait/15 border border-wait/40 text-left">
            <span className="w-10 h-10 rounded-full bg-wait text-white flex items-center justify-center shrink-0"><Icon name="bell" /></span>
            <div className="flex-1"><p className="font-bold text-[#8a4a12]">A waitlist spot opened</p><p className="text-xs text-[#a85d1f]">{fmtDay(ready.day)} · {fmtMin(ready.min)} — claim within 15 min</p></div>
            <Icon name="chevron" className="w-5 h-5 text-[#a85d1f]" />
          </button>
        </div>
      )}

      <Section title="Course conditions" action={<span className="text-xs font-semibold text-forest">Updated 7:45 AM</span>}>
        <div className="grid grid-cols-4 gap-2">
          {[['sun', '68°', 'Sunny'], ['wind', '6 mph', 'NW wind'], ['drop', '10.5', 'Green speed'], ['cart', 'Open', 'Carts']].map(([i, v, l]) => (
            <div key={l} className="bg-white rounded-2xl border border-gray-100 py-3 flex flex-col items-center">
              <Icon name={i} className="w-5 h-5 text-forest" /><p className="font-bold text-sm mt-1">{v}</p><p className="text-[10px] text-gray-400">{l}</p>
            </div>
          ))}
        </div>
      </Section>

      <Section title="Quick actions">
        <div className="grid grid-cols-3 gap-2">
          {[['calendar', 'Book', () => ctx.nav('book'), ''], ['flag', 'Scorecard', () => ctx.nav('score'), played ? `Hole ${Math.min(played + 1, 18)}` : ''], ['list', 'Waitlist', () => ctx.nav('bookings'), ctx.waitlist.length ? String(ctx.waitlist.length) : '']].map(([i, l, fn, badge]) => (
            <button key={l as string} onClick={fn as () => void} className="relative bg-white rounded-2xl border border-gray-100 py-4 flex flex-col items-center gap-1.5 active:scale-95 transition">
              <span className="w-10 h-10 rounded-full bg-forest/10 text-forest flex items-center justify-center"><Icon name={i as string} /></span>
              <span className="text-sm font-semibold">{l as string}</span>
              {badge && <span className="absolute top-2 right-2 text-[10px] font-bold bg-forest text-white rounded-full px-1.5 py-0.5">{badge as string}</span>}
            </button>
          ))}
        </div>
      </Section>

      <Section title="Deals for you">
        <button onClick={() => { ctx.setPrefs((p) => ({ ...p, tod: 'Twilight', min: null })); ctx.nav('book') }} className="relative w-full h-32 rounded-3xl overflow-hidden text-left text-white">
          <img src={IMG_TWILIGHT} alt="Golf course at sunset" className="absolute inset-0 w-full h-full object-cover" />
          <div className="absolute inset-0 bg-gradient-to-r from-black/70 to-black/0" />
          <div className="relative p-5">
            <span className="text-[10px] font-bold uppercase tracking-wider bg-wait text-white px-2 py-0.5 rounded-full">Save 35%</span>
            <p className="text-xl font-extrabold mt-2">Twilight Special</p>
            <p className="text-sm text-white/80">Tee off after 3 PM from $35</p>
          </div>
        </button>
      </Section>

      <Section title="Club news">
        <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden flex">
          <img src={IMG_NEWS} alt="Golfer on a misty morning" className="w-24 object-cover" />
          <div className="p-3.5">
            <p className="text-[10px] font-bold uppercase text-forest">Event · Nov 9</p>
            <p className="font-bold text-sm leading-snug">Autumn Member Classic — registration is open</p>
            <p className="text-xs text-gray-500 mt-1">Shotgun start 8 AM · 72 spots left</p>
          </div>
        </div>
      </Section>
    </div>
  )
}

function BookScreen({ ctx }: { ctx: Ctx }) {
  const { prefs, setPrefs } = ctx
  const [waitSlot, setWaitSlot] = useState<number | null>(null)
  const ranges = { All: [0, 2000], Morning: [0, 660], Midday: [660, 900], Twilight: [900, 2000] } as const
  const mins = SLOTS.filter((m) => m >= ranges[prefs.tod][0] && m < ranges[prefs.tod][1])
  const mine = ctx.bookings.filter((b) => b.status === 'upcoming' && b.day === prefs.day).map((b) => b.min)
  const openCount = mins.filter((m) => slotState(prefs.day, m, prefs.holes) === 'available').length
  const price = prefs.min !== null ? priceFor(prefs.holes, prefs.day, prefs.min) : 0
  const set = (p: Partial<Prefs>) => setPrefs((x) => ({ ...x, ...p }))
  return (
    <div className="min-h-full">
      <TopBar title="Book a tee time" right={<span className="text-xs font-semibold text-forest bg-forest/10 px-2.5 py-1 rounded-full">{COURSE}</span>} />
      <div className="px-5 mb-2 flex justify-between items-baseline">
        <h2 className="font-bold text-gray-900">{dateFor(prefs.day).toLocaleDateString('en', { month: 'long', year: 'numeric' })}</h2>
        {isWeekend(prefs.day) && <span className="text-[11px] font-semibold text-[#a85d1f]">Weekend rates</span>}
      </div>
      <HorizontalDateSelector sel={prefs.day} onSel={(i) => set({ day: i, min: null })} />

      <div className="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 p-3 space-y-3">
        <div className="p-1 bg-gray-100 rounded-xl grid grid-cols-2">
          {([18, 9] as const).map((h) => (
            <button key={h} onClick={() => set({ holes: h, min: null })} className={`h-10 rounded-lg text-sm font-semibold transition ${prefs.holes === h ? 'bg-white text-forest shadow' : 'text-gray-500'}`}>{h} Holes</button>
          ))}
        </div>
        <div className="flex items-center justify-between px-1">
          <span className="flex items-center gap-2 text-sm font-semibold text-gray-700"><Icon name="users" className="w-4 h-4 text-forest" />Players</span>
          <div className="flex items-center gap-3">
            <button disabled={prefs.players <= 1} onClick={() => set({ players: prefs.players - 1 })} aria-label="Fewer players" className="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center disabled:opacity-30"><Icon name="minus" className="w-4 h-4" /></button>
            <span className="w-4 text-center font-bold text-lg">{prefs.players}</span>
            <button disabled={prefs.players >= 4} onClick={() => set({ players: prefs.players + 1 })} aria-label="More players" className="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center disabled:opacity-30"><Icon name="plus" className="w-4 h-4" /></button>
          </div>
        </div>
      </div>

      <div className="px-5 mt-4 flex gap-2 overflow-x-auto no-scrollbar">
        {(['All', 'Morning', 'Midday', 'Twilight'] as const).map((t) => (
          <button key={t} onClick={() => set({ tod: t, min: null })}
            className={`shrink-0 h-9 px-4 rounded-full text-sm font-semibold border transition ${prefs.tod === t ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200'}`}>
            {t}{t === 'Twilight' && <span className="ml-1 text-[10px] text-wait">−35%</span>}
          </button>
        ))}
      </div>
      <div className="px-5 mt-3 flex justify-between items-center text-[11px] text-gray-500">
        <div className="flex gap-3">
          <span className="flex items-center gap-1"><i className="w-2.5 h-2.5 rounded-full border-2 border-forest" />Open</span>
          <span className="flex items-center gap-1"><i className="w-2.5 h-2.5 rounded-full bg-wait" />Waitlist</span>
          <span className="flex items-center gap-1"><i className="w-2.5 h-2.5 rounded-full bg-gray-300" />Booked</span>
        </div>
        <span className="font-semibold text-forest">{openCount} open</span>
      </div>

      <div className="px-5 pt-3 pb-5 grid grid-cols-3 gap-2.5">
        {mins.map((m) => {
          const base = mine.includes(m) ? 'booked' : slotState(prefs.day, m, prefs.holes)
          const state: SlotState = prefs.min === m ? 'selected' : base
          return (
            <TimeSlotChip key={m} time={fmtMin(m)} price={priceFor(prefs.holes, prefs.day, m)} state={state}
              onClick={() => (base === 'waitlist' ? setWaitSlot(m) : set({ min: m }))} />
          )
        })}
      </div>

      <div className="sticky bottom-0 p-5 pt-4 bg-white/95 backdrop-blur border-t border-gray-100 rounded-t-3xl">
        <div className="flex justify-between text-sm mb-3">
          <span className="text-gray-500">{prefs.min !== null ? `${fmtDay(prefs.day)} · ${fmtMin(prefs.min)} · ${prefs.holes} holes · ${prefs.players}p` : 'Select a tee time'}</span>
          <span className="font-bold">{prefs.min !== null ? `$${price * prefs.players}` : '—'}</span>
        </div>
        <PrimaryButton variant={prefs.min === null ? 'disabled' : 'active'} onClick={() => ctx.nav('checkout')}>Continue to checkout</PrimaryButton>
      </div>

      <Sheet open={waitSlot !== null} onClose={() => setWaitSlot(null)} title="Join the waitlist">
        {waitSlot !== null && <>
          <div className="flex items-center gap-4 p-4 rounded-2xl bg-wait/10 border border-wait/30 mb-4">
            <div className="w-14 h-14 rounded-2xl bg-wait text-white flex flex-col items-center justify-center"><Icon name="clock" /></div>
            <div><p className="font-bold">{fmtDay(prefs.day)} · {fmtMin(waitSlot)}</p><p className="text-sm text-gray-500">{prefs.holes} holes · {prefs.players} players · 2 ahead of you</p></div>
          </div>
          <p className="text-sm text-gray-500 mb-5">We'll send a push notification the moment this time opens up. You'll have 15 minutes to claim it — no charge until you do.</p>
          <PrimaryButton onClick={() => {
            ctx.setWaitlist((w) => [...w, { id: Date.now(), day: prefs.day, min: waitSlot, holes: prefs.holes, players: prefs.players, position: 3 }])
            setWaitSlot(null); ctx.toast("You're on the waitlist")
          }}>Notify me</PrimaryButton>
        </>}
      </Sheet>
    </div>
  )
}

function CheckoutScreen({ ctx }: { ctx: Ctx }) {
  const { prefs } = ctx
  const [addons, setAddons] = useState<string[]>(['cart'])
  const [partners, setPartners] = useState<string[]>([])
  const [card, setCard] = useState(ctx.cards.find((c) => c.isDefault)?.id ?? 'apple')
  const [promo, setPromo] = useState('')
  const [applied, setApplied] = useState(false)
  const [promoErr, setPromoErr] = useState(false)
  const [loading, setLoading] = useState(false)
  if (prefs.min === null) return null
  const min = prefs.min
  const unit = priceFor(prefs.holes, prefs.day, min)
  const fees = unit * prefs.players
  const addOnTotal = ADDONS.filter((a) => addons.includes(a.id)).reduce((s, a) => s + a.price * (a.perPlayer ? prefs.players : 1), 0)
  const member = Math.round(unit * 0.1) // Gold member: 10% off own green fee
  const discount = applied ? Math.round((fees + addOnTotal - member) * 0.1) : 0
  const total = fees + addOnTotal - member - discount
  const pay = () => {
    setLoading(true)
    window.setTimeout(() => {
      const id = Date.now()
      ctx.setBookings((b) => [...b, { id, day: prefs.day, min, holes: prefs.holes, players: prefs.players, partners, addons, total, card, status: 'upcoming', tee: min % 20 === 0 ? 'Hole 1' : 'Hole 10' }])
      ctx.setWaitlist((w) => w.filter((x) => !(x.day === prefs.day && x.min === min)))
      ctx.setPrefs((p) => ({ ...p, min: null }))
      ctx.nav('success', id)
    }, 1200)
  }
  return (
    <div className="pb-6">
      <TopBar title="Checkout" onBack={ctx.back} />
      <div className="px-5 space-y-5">
        <div className="bg-forest text-white rounded-3xl p-5 relative overflow-hidden">
          <div className="absolute -right-8 -bottom-10 w-36 h-36 rounded-full bg-white/5" />
          <p className="text-xs uppercase tracking-wider text-white/60 font-semibold">{COURSE}</p>
          <p className="text-2xl font-extrabold mt-1">{fmtMin(min)}</p>
          <p className="text-sm text-white/80">{fmtDayLong(prefs.day)}</p>
          <div className="flex gap-2 mt-3">
            {[`${prefs.holes} holes`, `${prefs.players} players`, min >= 900 ? 'Twilight' : isWeekend(prefs.day) ? 'Weekend' : 'Weekday'].map((t) => (
              <span key={t} className="text-xs font-semibold bg-white/15 px-2.5 py-1 rounded-full">{t}</span>
            ))}
          </div>
        </div>

        <div>
          <h2 className="font-bold mb-1">Playing partners</h2>
          <p className="text-xs text-gray-500 mb-3">{prefs.players > 1 ? `Invite up to ${prefs.players - 1} — they'll get the pass by text.` : 'Solo round — add players on the previous screen.'}</p>
          <div className="flex gap-3 overflow-x-auto no-scrollbar pb-1">
            <div className="flex flex-col items-center gap-1 shrink-0 w-14">
              <div className="w-12 h-12 rounded-full bg-forest text-white font-bold flex items-center justify-center ring-2 ring-forest ring-offset-2">JC</div>
              <span className="text-[11px] font-semibold">You</span>
            </div>
            {FRIENDS.map((f) => {
              const on = partners.includes(f)
              const full = !on && partners.length >= prefs.players - 1
              return (
                <button key={f} disabled={full} onClick={() => setPartners(on ? partners.filter((x) => x !== f) : [...partners, f])} className="flex flex-col items-center gap-1 shrink-0 w-14 disabled:opacity-30">
                  <div className={`relative w-12 h-12 rounded-full font-bold flex items-center justify-center transition ${on ? 'bg-sand text-forest ring-2 ring-forest ring-offset-2' : 'bg-gray-100 text-gray-500'}`}>
                    {initials(f)}
                    {on && <span className="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full bg-forest text-white flex items-center justify-center ring-2 ring-offset-white ring-white"><Icon name="check" className="w-3 h-3" /></span>}
                  </div>
                  <span className="text-[11px] text-gray-600 truncate w-full text-center">{f.split(' ')[0]}</span>
                </button>
              )
            })}
          </div>
        </div>

        <div>
          <h2 className="font-bold mb-3">Add-ons</h2>
          <div className="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50">
            {ADDONS.map((a) => {
              const on = addons.includes(a.id)
              return (
                <button key={a.id} onClick={() => setAddons(on ? addons.filter((x) => x !== a.id) : [...addons, a.id])} className="w-full flex items-center gap-3 p-4 text-left">
                  <span className={`w-6 h-6 rounded-lg border-2 flex items-center justify-center transition ${on ? 'bg-forest border-forest text-white' : 'border-gray-300'}`}>{on && <Icon name="check" className="w-3.5 h-3.5" />}</span>
                  <div className="flex-1"><p className="font-semibold text-sm">{a.label}</p><p className="text-xs text-gray-400">{a.desc}</p></div>
                  <span className="text-sm font-semibold">${a.price * (a.perPlayer ? prefs.players : 1)}</span>
                </button>
              )
            })}
          </div>
        </div>

        <div>
          <h2 className="font-bold mb-3">Payment</h2>
          <div className="space-y-2">
            {[...ctx.cards.map((c) => ({ id: c.id, label: `${c.brand} •••• ${c.last4}`, sub: `Expires ${c.exp}` })), { id: 'apple', label: 'Apple Pay', sub: 'Pay with Face ID' }].map((c) => (
              <button key={c.id} onClick={() => setCard(c.id)} className={`w-full flex items-center gap-3 p-3.5 rounded-2xl border-[1.5px] bg-white text-left transition ${card === c.id ? 'border-forest' : 'border-gray-100'}`}>
                <span className={`w-10 h-7 rounded-md ${c.id === 'apple' ? 'bg-black' : c.id === 'v' ? 'bg-gradient-to-br from-slate-800 to-slate-600' : 'bg-gradient-to-br from-[#a08a62] to-sand'}`} />
                <div className="flex-1"><p className="text-sm font-semibold">{c.label}</p><p className="text-xs text-gray-400">{c.sub}</p></div>
                <span className={`w-5 h-5 rounded-full ${card === c.id ? 'border-[6px] border-forest' : 'border-2 border-gray-300'}`} />
              </button>
            ))}
          </div>
        </div>

        <div>
          <div className="flex gap-2">
            <div className="relative flex-1">
              <Icon name="tag" className="w-4 h-4 absolute left-3.5 top-3.5 text-gray-400" />
              <input value={promo} disabled={applied} onChange={(e) => { setPromo(e.target.value); setPromoErr(false) }} placeholder="Promo code (try BIRDIE10)"
                className={`w-full h-11 pl-10 pr-3 rounded-xl bg-white border text-sm outline-none uppercase placeholder:normal-case ${promoErr ? 'border-danger' : 'border-gray-200 focus:border-forest'}`} />
            </div>
            <button onClick={() => (applied ? (setApplied(false), setPromo('')) : promo.trim().toUpperCase() === 'BIRDIE10' ? setApplied(true) : setPromoErr(true))}
              className="h-11 px-4 rounded-xl bg-gray-900 text-white text-sm font-semibold">{applied ? 'Remove' : 'Apply'}</button>
          </div>
          {promoErr && <p className="text-xs text-danger font-semibold mt-1.5">That code isn't valid.</p>}
          {applied && <p className="text-xs text-forest font-semibold mt-1.5">BIRDIE10 applied — 10% off</p>}
        </div>

        <div className="bg-white rounded-2xl border border-gray-100 p-4 space-y-2 text-sm">
          <div className="flex justify-between"><span className="text-gray-500">Green fees ({prefs.players} × ${unit})</span><span>${fees}</span></div>
          {addOnTotal > 0 && <div className="flex justify-between"><span className="text-gray-500">Add-ons</span><span>${addOnTotal}</span></div>}
          <div className="flex justify-between text-forest"><span>Gold member discount</span><span>−${member}</span></div>
          {applied && <div className="flex justify-between text-forest"><span>Promo BIRDIE10</span><span>−${discount}</span></div>}
          <div className="flex justify-between pt-2 border-t border-gray-100 text-base font-extrabold"><span>Total</span><span>${total}</span></div>
        </div>
        <p className="text-[11px] text-gray-400 text-center">Free cancellation up to 24 hours before your tee time.</p>
        <PrimaryButton variant={loading ? 'loading' : 'active'} onClick={pay}>{loading ? 'Processing…' : `Pay $${total}`}</PrimaryButton>
      </div>
    </div>
  )
}

function SuccessScreen({ ctx }: { ctx: Ctx }) {
  const b = ctx.bookings.find((x) => x.id === ctx.detailId)
  if (!b) return null
  return (
    <div className="min-h-full flex flex-col px-6 pt-10 pb-8 text-center">
      <div className="mx-auto w-20 h-20 rounded-full bg-forest/10 flex items-center justify-center">
        <div className="w-14 h-14 rounded-full bg-forest text-white flex items-center justify-center shadow-lg shadow-forest/30"><Icon name="check" className="w-7 h-7" /></div>
      </div>
      <h1 className="text-2xl font-extrabold mt-5">You're booked!</h1>
      <p className="text-gray-500 text-sm mt-1">Confirmation sent to james.carter@mail.com</p>
      <div className="mt-6 bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div className="p-5 border-b border-dashed border-gray-200">
          <p className="text-3xl font-extrabold text-forest">{fmtMin(b.min)}</p>
          <p className="font-semibold">{fmtDayLong(b.day)}</p>
          <p className="text-sm text-gray-500">{b.holes} holes · {b.players} players · {b.tee}</p>
        </div>
        <div className="p-5 flex flex-col items-center">
          <QR seed={b.id % 10000} className="w-28 h-28" />
          <p className="text-xs text-gray-400 mt-2">Scan at the pro shop to check in</p>
        </div>
      </div>
      <div className="mt-auto pt-6 space-y-2">
        <PrimaryButton onClick={() => ctx.nav('detail', b.id)}>View booking</PrimaryButton>
        <div className="grid grid-cols-2 gap-2">
          <button onClick={() => ctx.toast('Added to your calendar')} className="h-12 rounded-2xl border border-gray-200 bg-white text-sm font-semibold">Add to calendar</button>
          <button onClick={() => ctx.nav('home')} className="h-12 rounded-2xl text-sm font-semibold text-gray-600">Back to home</button>
        </div>
      </div>
    </div>
  )
}

function BookingsScreen({ ctx }: { ctx: Ctx }) {
  const [tab, setTab] = useState<'Upcoming' | 'Waitlist' | 'Past'>(ctx.waitlist.some((w) => w.position === 1) ? 'Waitlist' : 'Upcoming')
  const upcoming = ctx.bookings.filter((b) => b.status === 'upcoming').sort((a, b) => a.day - b.day || a.min - b.min)
  const past = ctx.bookings.filter((b) => b.status !== 'upcoming').sort((a, b) => b.day - a.day)
  const counts = { Upcoming: upcoming.length, Waitlist: ctx.waitlist.length, Past: past.length }
  return (
    <div className="pb-6">
      <TopBar title="My bookings" />
      <div className="mx-5 p-1 bg-gray-200/70 rounded-xl grid grid-cols-3 mb-4">
        {(['Upcoming', 'Waitlist', 'Past'] as const).map((t) => (
          <button key={t} onClick={() => setTab(t)} className={`h-10 rounded-lg text-sm font-semibold transition ${tab === t ? 'bg-white text-forest shadow' : 'text-gray-500'}`}>
            {t} <span className="text-[11px] opacity-60">{counts[t]}</span>
          </button>
        ))}
      </div>
      <div className="px-5 space-y-3">
        {tab === 'Upcoming' && (upcoming.length ? upcoming.map((b) => (
          <button key={b.id} onClick={() => ctx.nav('detail', b.id)} className="w-full bg-white rounded-2xl border border-gray-100 p-4 flex items-center gap-4 text-left shadow-sm active:scale-[0.99] transition">
            <div className="w-14 text-center rounded-xl bg-forest/10 py-2 shrink-0">
              <p className="text-[10px] font-bold text-forest uppercase">{dateFor(b.day).toLocaleDateString('en', { month: 'short' })}</p>
              <p className="text-xl font-extrabold text-forest leading-tight">{String(dateFor(b.day).getDate()).padStart(2, '0')}</p>
            </div>
            <div className="flex-1">
              <p className="font-bold">{fmtMin(b.min)} <span className="text-xs font-semibold text-forest ml-1">{relDays(b.day)}</span></p>
              <p className="text-sm text-gray-500">{b.holes} holes · {b.players} players · {b.tee}</p>
            </div>
            <Icon name="chevron" className="w-5 h-5 text-gray-300" />
          </button>
        )) : <Empty text="No upcoming rounds" cta="Book a tee time" onCta={() => ctx.nav('book')} />)}

        {tab === 'Waitlist' && (ctx.waitlist.length ? ctx.waitlist.map((w) => (
          <div key={w.id} className={`rounded-2xl border p-4 ${w.position === 1 ? 'bg-wait/10 border-wait/50' : 'bg-white border-gray-100'}`}>
            <div className="flex items-center gap-3">
              <div className={`w-12 h-12 rounded-full flex flex-col items-center justify-center font-extrabold ${w.position === 1 ? 'bg-wait text-white' : 'bg-gray-100 text-gray-600'}`}>
                <span className="text-[9px] font-semibold uppercase leading-none">No.</span>{w.position}
              </div>
              <div className="flex-1">
                <p className="font-bold">{fmtDay(w.day)} · {fmtMin(w.min)}</p>
                <p className="text-sm text-gray-500">{w.holes} holes · {w.players} players</p>
              </div>
            </div>
            {w.position === 1 ? (
              <div className="mt-3 flex gap-2">
                <PrimaryButton className="h-11! text-sm!" onClick={() => { ctx.setPrefs((p) => ({ ...p, day: w.day, holes: w.holes, players: w.players, min: w.min, tod: 'All' })); ctx.nav('checkout') }}>Claim spot · 14:52 left</PrimaryButton>
              </div>
            ) : (
              <div className="mt-3 flex justify-between items-center">
                <span className="text-xs text-gray-500">We'll notify you when a spot opens</span>
                <button onClick={() => { ctx.setWaitlist((l) => l.filter((x) => x.id !== w.id)); ctx.toast('Left the waitlist') }} className="text-sm font-semibold text-danger">Leave</button>
              </div>
            )}
          </div>
        )) : <Empty text="You're not on any waitlists" cta="Find a time" onCta={() => ctx.nav('book')} />)}

        {tab === 'Past' && past.map((b) => (
          <div key={b.id} className="bg-white rounded-2xl border border-gray-100 p-4 flex items-center gap-4">
            <div className="flex-1">
              <p className="font-bold">{dateFor(b.day).toLocaleDateString('en', { month: 'short', day: 'numeric' })} · {fmtMin(b.min)}</p>
              <p className="text-sm text-gray-500">{b.holes} holes · {b.players} players · ${b.total}</p>
              {b.status === 'cancelled' && <span className="inline-block mt-1 text-[10px] font-bold uppercase text-danger bg-danger/10 px-2 py-0.5 rounded-full">Cancelled</span>}
            </div>
            {b.score && <div className="text-center"><p className="text-xl font-extrabold">{b.score}</p><p className="text-[10px] text-gray-400">Score</p></div>}
            <button onClick={() => { ctx.setPrefs((p) => ({ ...p, holes: b.holes, players: b.players, min: null, tod: 'All' })); ctx.nav('book') }} className="h-9 px-3 rounded-full bg-forest/10 text-forest text-xs font-bold">Book again</button>
          </div>
        ))}
      </div>
    </div>
  )
}

function Empty({ text, cta, onCta }: { text: string; cta: string; onCta: () => void }) {
  return (
    <div className="py-12 flex flex-col items-center text-center">
      <div className="w-14 h-14 rounded-full bg-forest/10 text-forest flex items-center justify-center mb-3"><Icon name="flag" /></div>
      <p className="font-semibold text-gray-700">{text}</p>
      <button onClick={onCta} className="mt-3 text-sm font-bold text-forest">{cta}</button>
    </div>
  )
}

function DetailScreen({ ctx }: { ctx: Ctx }) {
  const [confirm, setConfirm] = useState(false)
  const b = ctx.bookings.find((x) => x.id === ctx.detailId)
  if (!b) return null
  const card = ctx.cards.find((c) => c.id === b.card)
  const free = b.day >= 1
  const refund = free ? b.total : Math.round(b.total / 2)
  return (
    <div className="min-h-full pb-6">
      <TopBar title="Tee time pass" onBack={ctx.back} />
      <div className="px-5 space-y-4">
        <div className="rounded-3xl overflow-hidden shadow-xl shadow-forest/15">
          <div className="bg-forest text-white p-5 relative">
            <div className="absolute right-4 top-4 text-[10px] font-bold uppercase tracking-wider bg-white/15 px-2 py-1 rounded-full">{b.status === 'cancelled' ? 'Cancelled' : relDays(b.day)}</div>
            <p className="text-xs uppercase tracking-wider text-white/60 font-semibold">{COURSE}</p>
            <p className="text-3xl font-extrabold mt-1">{fmtMin(b.min)}</p>
            <p className="text-sm text-white/80">{fmtDayLong(b.day)}</p>
            <div className="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-white/15 text-center">
              {[['Tee', b.tee], ['Holes', b.holes], ['Players', b.players]].map(([l, v]) => (
                <div key={l as string}><p className="text-[10px] uppercase text-white/60">{l}</p><p className="font-bold">{v}</p></div>
              ))}
            </div>
          </div>
          <div className="relative bg-white p-5 flex flex-col items-center">
            <span className="absolute -top-3 -left-3 w-6 h-6 rounded-full bg-offwhite" /><span className="absolute -top-3 -right-3 w-6 h-6 rounded-full bg-offwhite" />
            <div className={b.status === 'cancelled' ? 'opacity-20 grayscale' : ''}><QR seed={b.id % 10000} className="w-36 h-36" /></div>
            <p className="text-xs text-gray-400 mt-2">Booking #{String(b.id).slice(-6).padStart(6, '0')}</p>
            {b.status === 'upcoming' && <p className="text-xs font-semibold text-forest mt-1">Check-in opens 30 min before tee time</p>}
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50 text-sm">
          <div className="p-4">
            <p className="text-gray-500 text-xs mb-2">Your group</p>
            <div className="flex -space-x-2">
              {['James Carter', ...b.partners].map((n) => <div key={n} className="w-9 h-9 rounded-full bg-sand text-forest text-xs font-bold flex items-center justify-center ring-2 ring-white">{initials(n)}</div>)}
              {Array.from({ length: b.players - 1 - b.partners.length }, (_, i) => <div key={i} className="w-9 h-9 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold flex items-center justify-center ring-2 ring-white">Guest</div>)}
            </div>
          </div>
          <div className="p-4 flex justify-between"><span className="text-gray-500">Add-ons</span><span className="font-semibold">{b.addons.length ? b.addons.map((a) => ADDONS.find((x) => x.id === a)?.label).join(', ') : 'None'}</span></div>
          <div className="p-4 flex justify-between"><span className="text-gray-500">Paid</span><span className="font-semibold">${b.total} · {card ? `${card.brand} •••• ${card.last4}` : 'Apple Pay'}</span></div>
        </div>

        {b.status === 'upcoming' && <>
          <div className="grid grid-cols-2 gap-2">
            <button onClick={() => ctx.toast('Added to your calendar')} className="h-12 rounded-2xl bg-white border border-gray-200 text-sm font-semibold flex items-center justify-center gap-2"><Icon name="calendar" className="w-4 h-4" />Calendar</button>
            <button onClick={() => ctx.toast('Opening directions…')} className="h-12 rounded-2xl bg-white border border-gray-200 text-sm font-semibold flex items-center justify-center gap-2"><Icon name="pin" className="w-4 h-4" />Directions</button>
          </div>
          <button onClick={() => setConfirm(true)} className="w-full h-12 rounded-2xl text-danger text-sm font-semibold">Cancel booking</button>
        </>}
      </div>

      <Sheet open={confirm} onClose={() => setConfirm(false)} title="Cancel this booking?">
        <p className="text-sm text-gray-500 mb-4">{fmtDayLong(b.day)} at {fmtMin(b.min)} · {b.players} players</p>
        <div className={`rounded-2xl p-4 mb-5 ${free ? 'bg-forest/10' : 'bg-wait/15'}`}>
          <p className={`font-bold ${free ? 'text-forest' : 'text-[#a85d1f]'}`}>{free ? 'Free cancellation' : 'Late cancellation'}</p>
          <p className="text-sm text-gray-600">{free ? `You'll be refunded the full $${b.total}.` : `Within 24 hours — you'll be refunded $${refund} (50%).`} Partners will be notified.</p>
        </div>
        <button onClick={() => { ctx.setBookings((l) => l.map((x) => (x.id === b.id ? { ...x, status: 'cancelled' } : x))); setConfirm(false); ctx.toast(`Cancelled · $${refund} refunded`); ctx.nav('bookings') }}
          className="w-full h-14 rounded-2xl bg-danger text-white font-semibold">Yes, cancel booking</button>
        <button onClick={() => setConfirm(false)} className="w-full h-12 mt-2 rounded-2xl text-sm font-semibold text-gray-600">Keep my tee time</button>
      </Sheet>
    </div>
  )
}

function ScoreScreen({ ctx }: { ctx: Ctx }) {
  const { scores, setScores } = ctx
  const firstOpen = scores.findIndex((s) => !s)
  const [hole, setHole] = useState(firstOpen === -1 ? 17 : firstOpen)
  const played = scores.filter(Boolean).length
  const strokes = scores.reduce((a, b) => a + b, 0)
  const parPlayed = scores.reduce((a, s, i) => a + (s ? PARS[i] : 0), 0)
  const diff = strokes - parPlayed
  const cur = scores[hole] || PARS[hole]
  const label = (s: number, p: number) => (['Albatross', 'Eagle', 'Birdie', 'Par', 'Bogey', 'Double', 'Triple'][s - p + 3] ?? `+${s - p}`)
  const setCur = (v: number) => setScores((sc) => sc.map((x, i) => (i === hole ? Math.max(1, Math.min(12, v)) : x)))
  const cellCls = (s: number, p: number) => (!s ? 'text-gray-300' : s < p ? 'bg-forest text-white rounded-full' : s === p ? 'text-gray-900' : s === p + 1 ? 'bg-sand/60 text-[#5a4a2a] rounded-md' : 'bg-danger/15 text-danger rounded-md')
  const nine = (from: number) => scores.slice(from, from + 9).reduce((a, b) => a + b, 0)
  const finish = () => {
    const newHcp = Math.max(0, +(ctx.hcpHistory[ctx.hcpHistory.length - 1] + (diff - 10) * 0.05).toFixed(1))
    ctx.setHcpHistory((h) => [...h.slice(-7), newHcp])
    ctx.setBookings((b) => [...b, { id: Date.now(), day: 0, min: 450, holes: played > 9 ? 18 : 9, players: 1, partners: [], addons: [], total: 0, card: 'v', status: 'past', score: strokes, tee: 'Hole 1' }])
    ctx.toast(`Round saved · ${strokes} (${diff >= 0 ? '+' : ''}${diff}) · Hcp ${newHcp}`)
    setScores(Array(18).fill(0)); setHole(0)
  }
  return (
    <div className="pb-6">
      <TopBar title="Scorecard" right={<span className="text-xs font-semibold text-gray-500">{COURSE} · Blue tees</span>} />
      <div className="px-5 space-y-4">
        <div className="grid grid-cols-3 gap-2">
          {[['Strokes', strokes || '—'], ['To par', played ? `${diff > 0 ? '+' : ''}${diff || 'E'}` : '—'], ['Thru', played]].map(([l, v]) => (
            <div key={l as string} className="bg-white rounded-2xl border border-gray-100 py-3 text-center">
              <p className={`text-2xl font-extrabold ${l === 'To par' && played && diff < 0 ? 'text-forest' : ''}`}>{v}</p><p className="text-[11px] text-gray-400">{l}</p>
            </div>
          ))}
        </div>

        <div className="bg-forest text-white rounded-3xl p-5">
          <div className="flex justify-between items-center">
            <button disabled={hole === 0} onClick={() => setHole(hole - 1)} aria-label="Previous hole" className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center disabled:opacity-30"><Icon name="back" /></button>
            <div className="text-center">
              <p className="text-xs uppercase tracking-wider text-white/60 font-semibold">Hole</p>
              <p className="text-4xl font-extrabold leading-none">{hole + 1}</p>
              <p className="text-sm text-white/70 mt-1">Par {PARS[hole]} · {YARDS[hole]} yds</p>
            </div>
            <button disabled={hole === 17} onClick={() => setHole(hole + 1)} aria-label="Next hole" className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center disabled:opacity-30"><Icon name="chevron" /></button>
          </div>
          <div className="flex items-center justify-center gap-6 mt-5">
            <button onClick={() => setCur(cur - 1)} aria-label="One less stroke" className="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center active:scale-90 transition"><Icon name="minus" className="w-6 h-6" /></button>
            <div className="text-center w-20">
              <p className={`text-6xl font-extrabold leading-none ${scores[hole] ? '' : 'text-white/40'}`}>{cur}</p>
              <p className="text-xs font-bold uppercase tracking-wider text-sand mt-1">{scores[hole] ? label(cur, PARS[hole]) : 'Not played'}</p>
            </div>
            <button onClick={() => setCur(cur + 1)} aria-label="One more stroke" className="w-14 h-14 rounded-full bg-white/10 flex items-center justify-center active:scale-90 transition"><Icon name="plus" className="w-6 h-6" /></button>
          </div>
          <button onClick={() => { if (!scores[hole]) setCur(cur); if (hole < 17) setHole(hole + 1) }} className="w-full h-12 mt-5 rounded-2xl bg-white text-forest font-bold">
            {scores[hole] ? (hole < 17 ? 'Next hole' : 'Saved') : `Save ${cur} & next`}
          </button>
        </div>

        {[0, 9].map((from) => (
          <div key={from} className="bg-white rounded-2xl border border-gray-100 p-3">
            <div className="grid grid-cols-10 text-center text-[10px] font-semibold text-gray-400 mb-1">
              {PARS.slice(from, from + 9).map((_, i) => <span key={i}>{from + i + 1}</span>)}<span>{from ? 'IN' : 'OUT'}</span>
            </div>
            <div className="grid grid-cols-10 text-center text-[10px] text-gray-400 mb-1.5">
              {PARS.slice(from, from + 9).map((p, i) => <span key={i}>{p}</span>)}<span>{PARS.slice(from, from + 9).reduce((a, b) => a + b, 0)}</span>
            </div>
            <div className="grid grid-cols-10 text-center items-center">
              {scores.slice(from, from + 9).map((s, i) => (
                <button key={i} onClick={() => setHole(from + i)} className={`mx-auto w-7 h-7 flex items-center justify-center text-sm font-bold ${cellCls(s, PARS[from + i])} ${hole === from + i ? 'ring-2 ring-forest ring-offset-1' : ''}`}>{s || '·'}</button>
              ))}
              <span className="text-sm font-extrabold">{nine(from) || '—'}</span>
            </div>
          </div>
        ))}
        <div className="flex gap-3 justify-center text-[10px] text-gray-500">
          <span className="flex items-center gap-1"><i className="w-3 h-3 rounded-full bg-forest" />Birdie or better</span>
          <span className="flex items-center gap-1"><i className="w-3 h-3 rounded bg-sand/60" />Bogey</span>
          <span className="flex items-center gap-1"><i className="w-3 h-3 rounded bg-danger/15" />Double+</span>
        </div>
        <PrimaryButton variant={played >= 9 ? 'active' : 'disabled'} onClick={finish}>{played >= 9 ? `Finish round · ${strokes}` : `Play ${9 - played} more holes to save`}</PrimaryButton>
      </div>
    </div>
  )
}

function ProfileScreen({ ctx }: { ctx: Ctx }) {
  const [prefs, setPrefs] = useState({ remind: true, wait: true, promo: false })
  const [adding, setAdding] = useState(false)
  const [num, setNum] = useState('')
  const [exp, setExp] = useState('')
  const h = ctx.hcpHistory
  const hcp = h[h.length - 1]
  const scored = ctx.bookings.filter((b) => b.score && b.holes === 18)
  const rounds = ctx.bookings.filter((b) => b.score).length + 38
  const best = scored.length ? Math.min(...scored.map((b) => b.score!)) : '—'
  const lo = Math.min(...h) - 0.5, hi = Math.max(...h) + 0.5
  const pts = h.map((v, i) => `${(i / (h.length - 1)) * 100},${((v - lo) / (hi - lo)) * 40}`).join(' ')
  const change = +(hcp - h[0]).toFixed(1)
  const digits = num.replace(/\D/g, '')
  const validCard = digits.length >= 15 && /^\d{2}\/\d{2}$/.test(exp)
  return (
    <div className="min-h-full pb-6">
      <TopBar title="Profile" />
      <div className="px-5 space-y-5">
        <div className="rounded-3xl bg-forest text-white p-5 relative overflow-hidden">
          <div className="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-white/5" />
          <div className="absolute right-6 bottom-20 w-16 h-16 rounded-full bg-white/5" />
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-full bg-sand text-forest font-bold text-lg flex items-center justify-center">
              {initials(ctx.member?.name ?? 'James Carter')}
            </div>
            <div>
              <h2 className="text-lg font-bold">{ctx.member?.name ?? 'James Carter'}</h2>
              <div className="flex items-center gap-2 mt-1">
                <span className="text-[11px] font-bold uppercase tracking-wider bg-[#E8C766] text-[#5a4300] px-2 py-0.5 rounded-full">
                  {ctx.member?.tier ?? 'Gold'} Member
                </span>
                <span className="text-[11px] text-white/60">#PV-{ctx.member?.id ?? '20418'}</span>
              </div>
            </div>
          </div>
          <div className="mt-5 grid grid-cols-3 text-center border-t border-white/15 pt-4">
            <div><p className="text-2xl font-extrabold">{hcp.toFixed(1)}</p><p className="text-xs text-white/60">Handicap</p></div>
            <div><p className="text-2xl font-extrabold">{rounds}</p><p className="text-xs text-white/60">Rounds</p></div>
            <div><p className="text-2xl font-extrabold">{best}</p><p className="text-xs text-white/60">Best score</p></div>
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-gray-100 p-4">
          <div className="flex justify-between items-baseline">
            <h3 className="font-bold">Handicap trend</h3>
            <span className={`text-xs font-bold ${change <= 0 ? 'text-forest' : 'text-danger'}`}>{change <= 0 ? '↓' : '↑'} {Math.abs(change)} this season</span>
          </div>
          <svg viewBox="-4 -6 108 52" className="w-full h-24 mt-2" preserveAspectRatio="none">
            <defs><linearGradient id="hg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stopColor="#1E5631" stopOpacity="0.2" /><stop offset="1" stopColor="#1E5631" stopOpacity="0" /></linearGradient></defs>
            <polygon points={`0,46 ${pts} 100,46`} fill="url(#hg)" />
            <polyline points={pts} fill="none" stroke="#1E5631" strokeWidth="2" vectorEffect="non-scaling-stroke" strokeLinejoin="round" />
            {h.map((v, i) => <circle key={i} cx={(i / (h.length - 1)) * 100} cy={((v - lo) / (hi - lo)) * 40} r={i === h.length - 1 ? 2.6 : 1.6} fill={i === h.length - 1 ? '#1E5631' : 'white'} stroke="#1E5631" strokeWidth="1" vectorEffect="non-scaling-stroke" />)}
          </svg>
          <div className="flex justify-between text-[10px] text-gray-400"><span>May</span><span>Jul</span><span>Sep</span><span>Now</span></div>
        </div>

        <div>
          <div className="flex justify-between mb-2.5"><h3 className="font-bold">Saved cards</h3><button onClick={() => setAdding(true)} className="text-sm font-semibold text-forest">+ Add card</button></div>
          {ctx.cards.map((c) => (
            <div key={c.id} className="flex items-center gap-3 bg-white rounded-2xl border border-gray-100 p-3 mb-2">
              <div className={`w-12 h-8 rounded-md ${c.brand === 'Visa' ? 'bg-gradient-to-br from-slate-800 to-slate-600' : c.brand === 'Mastercard' ? 'bg-gradient-to-br from-[#a08a62] to-sand' : 'bg-gradient-to-br from-forest to-[#3d7a50]'}`} />
              <div className="flex-1 text-sm">
                <p className="font-semibold">{c.brand} •••• {c.last4} {c.isDefault && <span className="ml-1 text-[10px] font-bold uppercase text-forest bg-forest/10 px-1.5 py-0.5 rounded">Default</span>}</p>
                <p className="text-xs text-gray-500">Expires {c.exp}</p>
              </div>
              {!c.isDefault && <button onClick={() => ctx.setCards((l) => l.map((x) => ({ ...x, isDefault: x.id === c.id })))} className="text-xs font-semibold text-forest">Set default</button>}
              {!c.isDefault && <button onClick={() => { ctx.setCards((l) => l.filter((x) => x.id !== c.id)); ctx.toast('Card removed') }} className="text-xs text-gray-400 ml-1">Remove</button>}
            </div>
          ))}
        </div>

        <div>
          <h3 className="font-bold mb-2.5">Notifications</h3>
          <div className="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50">
            {([['remind', 'Tee time reminders', '2 hours before your round'], ['wait', 'Waitlist alerts', 'When a spot you want opens'], ['promo', 'Deals & events', 'Twilight specials, tournaments']] as const).map(([k, t, d]) => (
              <div key={k} className="flex items-center justify-between gap-4 p-4">
                <div><p className="text-sm font-semibold">{t}</p><p className="text-xs text-gray-400">{d}</p></div>
                <MToggle on={prefs[k]} onChange={(v) => setPrefs({ ...prefs, [k]: v })} />
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50">
          {['Personal details', 'Help & support', 'Club rules & etiquette'].map((r) => (
            <button key={r} onClick={() => ctx.toast(`${r} coming soon`)} className="w-full flex justify-between items-center p-4 text-sm font-semibold">{r}<Icon name="chevron" className="w-4 h-4 text-gray-300" /></button>
          ))}
        </div>
        <button
          onClick={() => {
            ctx.setMember(null)
            ctx.nav('login')
            ctx.toast('Signed out successfully')
          }}
          className="w-full h-12 rounded-2xl text-danger text-sm font-semibold hover:bg-danger/5 transition"
        >
          Sign out
        </button>
      </div>

      <Sheet open={adding} onClose={() => setAdding(false)} title="Add a card">
        <div className="space-y-3">
          <label className="block"><span className="text-sm font-semibold text-gray-700">Card number</span>
            <input inputMode="numeric" value={num} onChange={(e) => setNum(e.target.value.replace(/[^\d ]/g, '').slice(0, 19))} placeholder="1234 5678 9012 3456" className="mt-1.5 w-full h-12 px-4 rounded-xl border border-gray-200 outline-none focus:border-forest tracking-wider" /></label>
          <label className="block"><span className="text-sm font-semibold text-gray-700">Expiry</span>
            <input value={exp} onChange={(e) => { const d = e.target.value.replace(/\D/g, '').slice(0, 4); setExp(d.length > 2 ? `${d.slice(0, 2)}/${d.slice(2)}` : d) }} placeholder="MM/YY" className="mt-1.5 w-full h-12 px-4 rounded-xl border border-gray-200 outline-none focus:border-forest" /></label>
          <PrimaryButton variant={validCard ? 'active' : 'disabled'} onClick={() => {
            ctx.setCards((l) => [...l, { id: 'c' + Date.now(), brand: digits[0] === '4' ? 'Visa' : digits[0] === '5' ? 'Mastercard' : 'Card', last4: digits.slice(-4), exp, isDefault: false }])
            setAdding(false); setNum(''); setExp(''); ctx.toast('Card added')
          }}>Save card</PrimaryButton>
        </div>
      </Sheet>
    </div>
  )
}

/* ---------- Auth Screens ---------- */

function LoginScreen({ ctx }: { ctx: Ctx }) {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPwd, setShowPwd] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [googleModal, setGoogleModal] = useState(false)
  const [customGoogleEmail, setCustomGoogleEmail] = useState('')

  const clubMembers: MemberAuth[] = [
    { id: 1, name: 'James Carter', email: 'james.carter@mail.com', tier: 'Gold', hcp: 8.4, passwordSet: true },
    { id: 2, name: 'Mina Lee', email: 'mina.lee@mail.com', tier: 'Gold', hcp: 5.1, passwordSet: true },
    { id: 3, name: 'Raj Patel', email: 'r.patel@mail.com', tier: 'Silver', hcp: 14.2, passwordSet: true },
    { id: 4, name: 'Sofia Kim', email: 'sofia.k@mail.com', tier: 'Standard', hcp: 21.0, passwordSet: false },
  ]

  const handleEmailLogin = (e?: React.FormEvent) => {
    e?.preventDefault()
    setError(null)
    const em = email.trim().toLowerCase()
    if (!em) {
      setError('Please enter your member email.')
      return
    }

    const found = clubMembers.find((m) => m.email.toLowerCase() === em)
    if (!found) {
      setError(`Access Denied: No club account found for "${email}". Regular users cannot self-register; only accounts created by Pine Valley Club Administration can sign in.`)
      return
    }

    if (!found.passwordSet) {
      setError(`Account found for ${found.name}, but your password is not set yet. Please open the activation link sent to your email to configure your password.`)
      return
    }

    if (!password) {
      setError('Please enter your password.')
      return
    }

    ctx.setMember(found)
    ctx.toast(`Welcome back, ${found.name.split(' ')[0]}!`)
    ctx.nav('home')
  }

  const handleGoogleLogin = (targetEmail: string) => {
    setError(null)
    setGoogleModal(false)
    const em = targetEmail.trim().toLowerCase()
    if (!em) return

    const found = clubMembers.find((m) => m.email.toLowerCase() === em)
    if (!found) {
      setError(`Access Denied: The Google account "${targetEmail}" is not pre-registered with Pine Valley Golf Club. Only emails entered by club administration can sign in with Google.`)
      return
    }

    ctx.setMember(found)
    ctx.toast(`Signed in with Google as ${found.name}`)
    ctx.nav('home')
  }

  return (
    <div className="min-h-full p-5 flex flex-col justify-between">
      <div>
        <div className="text-center pt-4 pb-6">
          <div className="w-16 h-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-forest-dark to-forest flex items-center justify-center text-white border-2 border-sand shadow-lg shadow-forest/20">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className="w-8 h-8">
              <path d="M5 21V4M5 4h11l-2 4 2 4H5" />
            </svg>
          </div>
          <h1 className="text-xl font-extrabold text-forest-dark tracking-tight">Pine Valley Golf Club</h1>
          <p className="text-xs text-gray-500 font-medium mt-1">Authorized Member Portal</p>
        </div>

        <form onSubmit={handleEmailLogin} className="space-y-3 bg-white p-5 rounded-3xl border border-gray-100 shadow-sm">
          <div>
            <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">Member Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => { setEmail(e.target.value); setError(null) }}
              placeholder="e.g. james.carter@mail.com"
              className="w-full h-11 px-3.5 rounded-xl border border-gray-200 text-sm outline-none focus:border-forest"
            />
          </div>

          <div>
            <div className="flex justify-between items-center mb-1.5">
              <label className="text-[11px] font-bold uppercase tracking-wider text-gray-500">Password</label>
              <button type="button" onClick={() => ctx.toast('PIN resets are processed by the Pro Shop: (555) 301-2200')} className="text-xs text-forest font-semibold">Forgot?</button>
            </div>
            <div className="relative flex items-center">
              <input
                type={showPwd ? 'text' : 'password'}
                value={password}
                onChange={(e) => { setPassword(e.target.value); setError(null) }}
                placeholder="Enter password"
                className="w-full h-11 pl-3.5 pr-12 rounded-xl border border-gray-200 text-sm outline-none focus:border-forest"
              />
              <button type="button" onClick={() => setShowPwd(!showPwd)} className="absolute right-3 text-gray-400 hover:text-gray-600 text-xs font-semibold">
                {showPwd ? 'Hide' : 'Show'}
              </button>
            </div>
          </div>

          {error && (
            <div className="p-3 rounded-xl bg-danger/10 border border-danger/20 text-danger text-xs font-medium leading-relaxed">
              {error}
              {error.includes('password is not set') && (
                <button
                  type="button"
                  onClick={() => ctx.nav('set-password')}
                  className="mt-2 block w-full py-1.5 rounded-lg bg-danger text-white text-xs font-bold text-center"
                >
                  Open Password Setup Demo ↗
                </button>
              )}
            </div>
          )}

          <button
            type="submit"
            className="w-full h-12 mt-1 rounded-xl bg-forest hover:bg-forest-dark text-white font-bold text-sm shadow-md shadow-forest/20 transition active:scale-[0.99]"
          >
            Sign In with Email
          </button>

          <div className="relative py-1.5 flex items-center justify-center">
            <span className="absolute inset-x-0 h-px bg-gray-200" />
            <span className="relative bg-white px-3 text-[10px] font-bold text-gray-400 tracking-wider uppercase">OR</span>
          </div>

          <button
            type="button"
            onClick={() => setGoogleModal(true)}
            className="w-full h-11 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-800 font-bold text-sm flex items-center justify-center gap-2.5 transition active:scale-[0.99]"
          >
            <svg viewBox="0 0 24 24" className="w-4 h-4">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
            </svg>
            Continue with Google
          </button>
        </form>

        <div className="mt-3.5 p-3 bg-white/70 border border-gray-200/80 rounded-2xl text-xs text-gray-500 leading-relaxed">
          <p className="font-bold text-gray-700 mb-0.5">🔒 Private Club Policy</p>
          User registration is disabled. Accounts can only be created by Club Administration. Once admin enters your email, an activation link is sent to configure your password.
        </div>
      </div>

      <div className="pt-3 border-t border-gray-200">
        <p className="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Quick test accounts:</p>
        <div className="flex flex-wrap gap-1 text-[11px]">
          <button
            type="button"
            onClick={() => { setEmail('james.carter@mail.com'); setPassword('password123'); setError(null) }}
            className="px-2 py-0.5 rounded bg-white border border-gray-200 font-semibold text-forest hover:border-forest"
          >
            James Carter (Active)
          </button>
          <button
            type="button"
            onClick={() => { setEmail('sofia.k@mail.com'); setPassword(''); setError(null) }}
            className="px-2 py-0.5 rounded bg-white border border-gray-200 font-semibold text-[#a85d1f] hover:border-[#a85d1f]"
          >
            Sofia Kim (Pending Pwd)
          </button>
          <button
            type="button"
            onClick={() => { setEmail('random@mail.com'); setPassword('test'); setError(null) }}
            className="px-2 py-0.5 rounded bg-white border border-gray-200 font-semibold text-danger hover:border-danger"
          >
            Random (Test Block)
          </button>
        </div>
      </div>

      <Sheet open={googleModal} onClose={() => setGoogleModal(false)} title="Continue with Google">
        <div className="space-y-3">
          <p className="text-xs text-gray-500 leading-relaxed">
            Select a Google account. Only emails entered by club administration can sign in.
          </p>

          <div className="space-y-1.5">
            <button
              onClick={() => handleGoogleLogin('james.carter@mail.com')}
              className="w-full p-2.5 rounded-xl border border-gray-200 hover:border-forest flex items-center gap-2.5 text-left transition"
            >
              <div className="w-7 h-7 rounded-full bg-forest text-white font-bold text-xs flex items-center justify-center">JC</div>
              <div className="flex-1 min-w-0">
                <p className="text-xs font-bold truncate">James Carter</p>
                <p className="text-[11px] text-gray-500 truncate">james.carter@mail.com</p>
              </div>
              <span className="text-[10px] font-bold text-forest bg-forest/10 px-1.5 py-0.5 rounded">Pre-approved</span>
            </button>

            <button
              onClick={() => handleGoogleLogin('mina.lee@mail.com')}
              className="w-full p-2.5 rounded-xl border border-gray-200 hover:border-forest flex items-center gap-2.5 text-left transition"
            >
              <div className="w-7 h-7 rounded-full bg-[#a08a62] text-white font-bold text-xs flex items-center justify-center">ML</div>
              <div className="flex-1 min-w-0">
                <p className="text-xs font-bold truncate">Mina Lee</p>
                <p className="text-[11px] text-gray-500 truncate">mina.lee@mail.com</p>
              </div>
              <span className="text-[10px] font-bold text-forest bg-forest/10 px-1.5 py-0.5 rounded">Pre-approved</span>
            </button>

            <button
              onClick={() => handleGoogleLogin('unauthorized.guest@gmail.com')}
              className="w-full p-2.5 rounded-xl border border-danger/30 bg-danger/5 flex items-center gap-2.5 text-left transition"
            >
              <div className="w-7 h-7 rounded-full bg-danger text-white font-bold text-xs flex items-center justify-center">✕</div>
              <div className="flex-1 min-w-0">
                <p className="text-xs font-bold text-danger truncate">Unauthorized User</p>
                <p className="text-[11px] text-gray-500 truncate">unauthorized.guest@gmail.com</p>
              </div>
              <span className="text-[10px] font-bold text-danger bg-danger/10 px-1.5 py-0.5 rounded">Test Block</span>
            </button>
          </div>

          <div className="pt-2 border-t border-gray-100">
            <label className="block text-[11px] font-semibold text-gray-700 mb-1">Or enter custom Google email:</label>
            <div className="flex gap-2">
              <input
                type="email"
                value={customGoogleEmail}
                onChange={(e) => setCustomGoogleEmail(e.target.value)}
                placeholder="name@gmail.com"
                className="flex-1 h-9 px-3 rounded-xl border border-gray-200 text-xs outline-none focus:border-forest"
              />
              <button
                type="button"
                onClick={() => handleGoogleLogin(customGoogleEmail)}
                className="px-3 h-9 rounded-xl bg-forest text-white text-xs font-bold"
              >
                Sign In
              </button>
            </div>
          </div>
        </div>
      </Sheet>
    </div>
  )
}

function SetPasswordScreen({ ctx }: { ctx: Ctx }) {
  const [pwd, setPwd] = useState('')
  const [confirmPwd, setConfirmPwd] = useState('')
  const [show, setShow] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const memberName = 'Sofia Kim'
  const memberEmail = 'sofia.k@mail.com'

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    if (pwd.length < 6) {
      setError('Password must be at least 6 characters.')
      return
    }
    if (pwd !== confirmPwd) {
      setError('Passwords do not match.')
      return
    }

    const activated: MemberAuth = {
      id: 4,
      name: memberName,
      email: memberEmail,
      tier: 'Standard',
      hcp: 21.0,
      passwordSet: true,
    }
    ctx.setMember(activated)
    ctx.toast('Password configured! Account activated.')
    ctx.nav('home')
  }

  return (
    <div className="min-h-full p-5 flex flex-col justify-between">
      <div>
        <TopBar title="Activate Account" onBack={() => ctx.nav('login')} />
        
        <div className="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-4">
          <div className="p-3 bg-forest/5 border border-forest/20 rounded-2xl flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-sand text-forest font-bold flex items-center justify-center">SK</div>
            <div>
              <p className="text-sm font-bold text-gray-900">{memberName}</p>
              <p className="text-xs text-gray-500">{memberEmail} · Standard Member</p>
            </div>
          </div>

          <p className="text-xs text-gray-500 leading-relaxed">
            Welcome to Pine Valley! Your membership account was initialized by Club Administration. Please choose a password to activate your access.
          </p>

          <form onSubmit={handleSave} className="space-y-3.5">
            <div>
              <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Create Password</label>
              <div className="relative flex items-center">
                <input
                  type={show ? 'text' : 'password'}
                  value={pwd}
                  onChange={(e) => setPwd(e.target.value)}
                  placeholder="At least 6 characters"
                  className="w-full h-11 pl-3.5 pr-12 rounded-xl border border-gray-200 text-sm outline-none focus:border-forest"
                />
                <button type="button" onClick={() => setShow(!show)} className="absolute right-3 text-xs font-semibold text-gray-400">
                  {show ? 'Hide' : 'Show'}
                </button>
              </div>
            </div>

            <div>
              <label className="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Confirm Password</label>
              <input
                type={show ? 'text' : 'password'}
                value={confirmPwd}
                onChange={(e) => setConfirmPwd(e.target.value)}
                placeholder="Re-enter password"
                className="w-full h-11 px-3.5 rounded-xl border border-gray-200 text-sm outline-none focus:border-forest"
              />
            </div>

            {error && (
              <div className="p-3 rounded-xl bg-danger/10 border border-danger/20 text-danger text-xs font-medium">
                {error}
              </div>
            )}

            <button
              type="submit"
              className="w-full h-12 rounded-xl bg-forest hover:bg-forest-dark text-white font-bold text-sm shadow-md shadow-forest/20 transition active:scale-[0.99]"
            >
              Save Password & Activate Account
            </button>
          </form>
        </div>
      </div>

      <div className="p-3 bg-white/70 border border-gray-200/80 rounded-2xl text-[11px] text-gray-500 text-center">
        🔒 Once set, you can sign in anytime with this password or with Google using {memberEmail}.
      </div>
    </div>
  )
}

/* ---------- shell ---------- */

const TABS: { id: Screen; label: string; icon: string }[] = [
  { id: 'home', label: 'Home', icon: 'home' },
  { id: 'book', label: 'Book', icon: 'calendar' },
  { id: 'bookings', label: 'Bookings', icon: 'list' },
  { id: 'score', label: 'Scorecard', icon: 'flag' },
  { id: 'profile', label: 'Profile', icon: 'user' },
]
const SUB: Screen[] = ['checkout', 'success', 'detail', 'login', 'set-password']

export default function MobileApp() {
  const [member, setMember] = useState<MemberAuth | null>({
    id: 1,
    name: 'James Carter',
    email: 'james.carter@mail.com',
    tier: 'Gold',
    hcp: 8.4,
    passwordSet: true,
  })
  const [screen, setScreen] = useState<Screen>('home')
  const [history, setHistory] = useState<Screen[]>([])
  const [detailId, setDetailId] = useState<number | null>(null)
  const [bookings, setBookings] = useState(seedBookings)
  const [waitlist, setWaitlist] = useState(seedWait)
  const [cards, setCards] = useState(seedCards)
  const [prefs, setPrefs] = useState<Prefs>({ day: 0, holes: 18, players: 2, tod: 'All', min: null })
  const [scores, setScores] = useState<number[]>([4, 5, 4, 4, 5, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0])
  const [hcpHistory, setHcpHistory] = useState([10.2, 9.9, 9.7, 9.2, 9.0, 8.7, 8.4])
  const [toastMsg, setToastMsg] = useState('')
  const scrollKey = screen + (detailId ?? '')

  useEffect(() => { if (!toastMsg) return; const t = window.setTimeout(() => setToastMsg(''), 2400); return () => window.clearTimeout(t) }, [toastMsg])

  const nav = (s: Screen, id?: number) => {
    if (id !== undefined) setDetailId(id)
    setHistory((h) => (TABS.some((t) => t.id === s) ? [] : s === 'success' ? h.filter((x) => !SUB.includes(x)) : screen === 'success' ? h : [...h, screen]))
    setScreen(s)
  }
  const back = () => { const prev = history[history.length - 1] ?? 'home'; setHistory(history.slice(0, -1)); setScreen(prev) }
  const ctx: Ctx = {
    screen,
    nav,
    back,
    detailId,
    bookings,
    setBookings,
    waitlist,
    setWaitlist,
    cards,
    setCards,
    prefs,
    setPrefs,
    scores,
    setScores,
    hcpHistory,
    setHcpHistory,
    toast: setToastMsg,
    member,
    setMember
  }
  const activeTab = SUB.includes(screen) ? (history.find((h) => !SUB.includes(h)) ?? 'home') : screen
  const showTabs = !!member && screen !== 'checkout' && screen !== 'success' && screen !== 'login' && screen !== 'set-password'

  const quick: [Screen, string, () => void][] = [
    ['home', 'Home', () => nav('home')],
    ['book', 'Book tee time', () => nav('book')],
    ['checkout', 'Checkout', () => { setPrefs((p) => ({ ...p, min: p.min ?? 600 })); nav('checkout') }],
    ['bookings', 'My bookings & waitlist', () => nav('bookings')],
    ['detail', 'Tee time pass', () => nav('detail', bookings.find((b) => b.status === 'upcoming')?.id ?? 1)],
    ['score', 'Live scorecard', () => nav('score')],
    ['profile', 'Profile & cards', () => nav('profile')],
    ['login', 'Member Sign In screen', () => { setMember(null); nav('login') }],
    ['set-password', 'Set Password page', () => nav('set-password')],
  ]

  return (
    <div className="flex flex-wrap items-start justify-center gap-10">
      <div className="w-[390px] h-[820px] rounded-[50px] bg-[#0b0b0b] p-3 shadow-2xl shrink-0 ring-1 ring-black/20">
        <div className="relative w-full h-full rounded-[40px] bg-offwhite overflow-hidden flex flex-col">
          <div className="flex justify-between items-center px-8 pt-3.5 pb-1 text-[13px] font-semibold relative z-10">
            <span>9:41</span>
            <span className="absolute left-1/2 -translate-x-1/2 top-2.5 w-28 h-7 rounded-full bg-black" />
            <span className="flex items-center gap-1.5"><span className="flex items-end gap-px h-3">{[1, 2, 3, 4].map((i) => <i key={i} className="w-[3px] bg-black rounded-sm" style={{ height: i * 3 }} />)}</span><span className="w-6 h-3 rounded-[4px] border border-black/60 p-px"><i className="block h-full w-4/5 bg-black rounded-[2px]" /></span></span>
          </div>
          <div key={scrollKey} className="flex-1 overflow-y-auto no-scrollbar pt-2">
            {!member && screen !== 'set-password' ? (
              <LoginScreen ctx={ctx} />
            ) : screen === 'set-password' ? (
              <SetPasswordScreen ctx={ctx} />
            ) : (
              <>
                {screen === 'home' && <HomeScreen ctx={ctx} />}
                {screen === 'book' && <BookScreen ctx={ctx} />}
                {screen === 'checkout' && <CheckoutScreen ctx={ctx} />}
                {screen === 'success' && <SuccessScreen ctx={ctx} />}
                {screen === 'bookings' && <BookingsScreen ctx={ctx} />}
                {screen === 'detail' && <DetailScreen ctx={ctx} />}
                {screen === 'score' && <ScoreScreen ctx={ctx} />}
                {screen === 'profile' && <ProfileScreen ctx={ctx} />}
                {screen === 'login' && <LoginScreen ctx={ctx} />}
              </>
            )}
          </div>
          {showTabs && (
            <nav className="bg-white/95 backdrop-blur border-t border-gray-100 px-2 pt-2 pb-6 grid grid-cols-5">
              {TABS.map((t) => (
                <button key={t.id} onClick={() => nav(t.id)} className={`flex flex-col items-center gap-1 py-1 transition ${activeTab === t.id ? 'text-forest' : 'text-gray-400'}`}>
                  <span className={`w-12 h-7 rounded-full flex items-center justify-center transition ${activeTab === t.id ? 'bg-forest/10' : ''}`}><Icon name={t.icon} className="w-[22px] h-[22px]" /></span>
                  <span className="text-[10px] font-semibold">{t.label}</span>
                </button>
              ))}
            </nav>
          )}
          <div className={`absolute left-1/2 -translate-x-1/2 top-14 z-50 px-4 py-2.5 rounded-full bg-gray-900 text-white text-sm font-semibold shadow-lg whitespace-nowrap transition-all duration-300 ${toastMsg ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-3 pointer-events-none'}`}>{toastMsg}</div>
          <span className="absolute bottom-2 left-1/2 -translate-x-1/2 w-32 h-1 rounded-full bg-black/80 z-50 pointer-events-none" />
        </div>
      </div>

      <div className="w-64 pt-6">
        <p className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Jump to screen</p>
        <div className="bg-white rounded-2xl shadow-sm p-2 space-y-0.5">
          {quick.map(([id, l, fn]) => (
            <button key={l} onClick={fn} className={`w-full text-left px-3 py-2.5 rounded-xl text-sm font-semibold transition ${screen === id ? 'bg-forest text-white' : 'text-gray-600 hover:bg-gray-50'}`}>{l}</button>
          ))}
        </div>
        <div className="mt-5 text-xs text-gray-500 leading-relaxed space-y-2">
          <p><span className="font-bold text-gray-700">Auth & Accounts:</span> Regular users cannot self-register. Only admins create accounts. An invite link is emailed to set password.</p>
          <p><span className="font-bold text-gray-700">Google Login:</span> "Continue with Google" only permits pre-registered member emails. Unregistered emails are rejected.</p>
        </div>
      </div>
    </div>
  )
}
