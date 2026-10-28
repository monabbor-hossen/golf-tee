import React, { useEffect, useState } from 'react'

/* ---------- types ---------- */

export type BookingEntry = { kind: 'booking'; name: string; party: number; holes: 18 | 9; status: 'Booked' | 'Checked in' | 'No-show' }
export type GridEntry = BookingEntry | { kind: 'block'; reason: string }
export type DayData = Record<string, GridEntry>

export type Tier = 'Gold' | 'Silver' | 'Standard'
export type Status = 'Active' | 'Suspended' | 'Pending'
export type Member = {
  id: number; name: string; email: string; phone: string; tier: Tier; hcp: number; rounds: number
  last: string; joined: string; status: Status; spend: number; next?: string; history: [string, string, string][]
  passwordSet?: boolean; setupToken?: string
}

export type Page = 'Dashboard' | 'Master Grid' | 'Waitlist' | 'Events' | 'Users' | 'Payments' | 'Reports' | 'Settings'
export type Focus = { memberId?: number; offset?: number; key?: string; txnId?: string; eventId?: number }

export type WaitEntry = { id: number; name: string; offset: number; min: number; party: number; holes: 18 | 9; addedMin: number; status: 'Waiting' | 'Offered'; offeredMin?: number }
export type TxnType = 'Green fee' | 'Pro shop' | 'Food & bev' | 'Event' | 'Membership'
export type Txn = { id: string; offset: number; min: number; name: string; desc: string; type: TxnType; method: string; amount: number; status: 'Paid' | 'Refunded' | 'Pending'; ref?: string }
export type EventT = { id: number; name: string; offset: number; start: number; hours: number; format: string; capacity: number; fee: number; registered: string[]; open: boolean; blocked: boolean }
export type Staff = { name: string; email: string; role: 'Owner' | 'Manager' | 'Front Desk' | 'Read only' }
export type Pricing = { weekday: [number, number]; weekend: [number, number]; twilight: [number, number] }
export type Settings = {
  courseName: string; phone: string; address: string; timezone: string; currency: string
  openDays: string[]; firstTee: string; lastTee: string; interval: number
  maxParty: number; advanceDays: number; cancelHours: number; twilightStart: number
  pricing: Pricing; flags: Record<string, boolean>; staff: Staff[]
}
export type Tone = 'forest' | 'wait' | 'danger' | 'sand' | 'gray'
export type Notif = { id: number; title: string; desc: string; at: number; page: Page; focus?: Focus; read: boolean; tone: Tone }
export type Activity = { id: number; title: string; desc: string; at: number; tone: Tone }

/* ---------- constants & helpers ---------- */

export const TEES = ['Hole 1 Tee', 'Hole 10 Tee']
export const SLOT_MINS = Array.from({ length: 66 }, (_, i) => 420 + i * 10) // 7:00 AM – 5:50 PM
export const NOW_MIN = 660 // simulated current time: 11:00 AM (shared with mobile app)
export const fmtMin = (m: number) => {
  const h = Math.floor(m / 60)
  return `${((h + 11) % 12) + 1}:${String(m % 60).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`
}
export const dateFor = (o: number) => { const d = new Date(); d.setDate(d.getDate() + o); return d }
export const isWeekend = (o: number) => [0, 6].includes(dateFor(o).getDay())
export const isPast = (o: number, m: number) => o < 0 || (o === 0 && m < NOW_MIN)
export const fmtDate = (o: number, opts: Intl.DateTimeFormatOptions = { weekday: 'short', month: 'short', day: 'numeric' }) =>
  o === 0 ? 'Today' : o === 1 ? 'Tomorrow' : o === -1 ? 'Yesterday' : dateFor(o).toLocaleDateString('en', opts)
export const WORLD_CURRENCIES: string[] = [
  'USD ($)', 'EUR (€)', 'GBP (£)', 'CAD (CA$)', 'AUD (A$)', 'JPY (¥)', 'CHF (CHF)', 'CNY (¥)',
  'INR (₹)', 'BDT (৳)', 'PKR (₨)', 'LKR (Rs)', 'NPR (Rs)', 'AED (AED)', 'SAR (SAR)', 'QAR (QAR)',
  'KWD (KWD)', 'BHD (BHD)', 'OMR (OMR)', 'JOD (JD)', 'SGD (S$)', 'HKD (HK$)', 'NZD (NZ$)',
  'KRW (₩)', 'TWD (NT$)', 'THB (฿)', 'MYR (RM)', 'IDR (Rp)', 'PHP (₱)', 'VND (₫)', 'BRL (R$)',
  'MXN (MX$)', 'ARS (AR$)', 'CLP (CLP$)', 'COP (COL$)', 'PEN (S/)', 'UYU ($U)', 'CRC (₡)', 'DOP (RD$)',
  'JMD (J$)', 'TTD (TT$)', 'ZAR (R)', 'EGP (E£)', 'NGN (₦)', 'KES (KSh)', 'GHS (GH₵)', 'UGX (USh)',
  'TZS (TSh)', 'MAD (MAD)', 'DZD (DA)', 'TND (DT)', 'SEK (kr)', 'NOK (kr)', 'DKK (kr)', 'ISK (kr)',
  'PLN (zł)', 'CZK (Kč)', 'HUF (Ft)', 'RON (lei)', 'BGN (лв)', 'TRY (₺)', 'RUB (₽)', 'UAH (₴)',
  'RSD (din)', 'BAM (KM)', 'GEL (₾)', 'KZT (₸)', 'AZN (₼)', 'MOP (MOP$)', 'BND (B$)', 'FJD (FJ$)',
  'XAF (FCFA)', 'XOF (CFA)'
]

export const extractCurrencySymbol = (str?: string): string => {
  if (!str) return '$'
  const match = str.match(/\((.*?)\)/)
  return match ? match[1] : (str.trim() || '$')
}

export const money = (n: number, sym: string = '$') => `${n < 0 ? '−' : ''}${sym}${Math.abs(n).toLocaleString()}`
export const initials = (n: string) => n.split(' ').filter(Boolean).map((x) => x[0]).join('').slice(0, 2).toUpperCase()
export const parseKey = (k: string) => { const [t, m] = k.split('-').map(Number); return { t, m } }
export const ago = (at: number, now: number) => {
  const m = Math.round((now - at) / 60000)
  return m < 1 ? 'Just now' : m < 60 ? `${m}m ago` : m < 1440 ? `${Math.round(m / 60)}h ago` : `${Math.round(m / 1440)}d ago`
}
export const rng = (seed: number) => { let r = Math.abs(Math.floor(seed)) % 233280; return () => { r = (r * 9301 + 49297) % 233280; return r / 233280 } }

export function downloadCSV(filename: string, rows: (string | number)[][]) {
  const csv = rows.map((r) => r.map((c) => { const s = String(c); return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s }).join(',')).join('\n')
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }))
  const a = document.createElement('a')
  a.href = url; a.download = filename; a.click()
  URL.revokeObjectURL(url)
}

/* ---------- seed data ---------- */

export const seedMembers: Member[] = [
  { id: 1, name: 'James Carter', email: 'james.carter@mail.com', phone: '(555) 301-2201', tier: 'Gold', hcp: 8.4, rounds: 42, last: 'Oct 12', joined: 'Mar 2021', status: 'Active', spend: 4820, next: 'Sat, Nov 2 · 7:30 AM · 4 players', history: [['Oct 12', '8:20 AM · 4 players', '$340'], ['Sep 28', '7:40 AM · 2 players', '$170']], passwordSet: true },
  { id: 2, name: 'Mina Lee', email: 'mina.lee@mail.com', phone: '(555) 301-4410', tier: 'Gold', hcp: 5.1, rounds: 67, last: 'Today', joined: 'Jun 2019', status: 'Active', spend: 7310, next: 'Tue, Oct 29 · 7:00 AM · 4 players', history: [['Today', '7:00 AM · 4 players', '$340'], ['Oct 19', '7:10 AM · 4 players', '$340']], passwordSet: true },
  { id: 3, name: 'Raj Patel', email: 'r.patel@mail.com', phone: '(555) 301-8812', tier: 'Silver', hcp: 14.2, rounds: 23, last: 'Today', joined: 'Jan 2023', status: 'Active', spend: 2140, history: [['Today', '7:00 AM · 3 players', '$255']], passwordSet: true },
  { id: 4, name: 'Sofia Kim', email: 'sofia.k@mail.com', phone: '(555) 301-0937', tier: 'Standard', hcp: 21.0, rounds: 6, last: 'Oct 20', joined: 'Oct 2024', status: 'Pending', spend: 410, history: [['Oct 20', '2:40 PM · 2 players', '$110']], passwordSet: false, setupToken: 'demo-sofia-token' },
  { id: 5, name: 'Alejandro Gomez', email: 'agomez@mail.com', phone: '(555) 301-5521', tier: 'Silver', hcp: 11.7, rounds: 31, last: 'Today', joined: 'Aug 2022', status: 'Active', spend: 2980, next: 'Sat, Nov 2 · 8:30 AM · 4 players', history: [['Today', '8:30 AM · 4 players', '$340']], passwordSet: true },
  { id: 6, name: 'Thanh Nguyen', email: 'thanh.n@mail.com', phone: '(555) 301-7746', tier: 'Standard', hcp: 18.3, rounds: 12, last: 'Sep 30', joined: 'Feb 2024', status: 'Suspended', spend: 960, history: [['Sep 30', '10:00 AM · 2 players', '$170']], passwordSet: true },
  { id: 7, name: 'Daniel Brown', email: 'd.brown@mail.com', phone: '(555) 301-3390', tier: 'Gold', hcp: 3.9, rounds: 88, last: 'Today', joined: 'Apr 2017', status: 'Active', spend: 11250, next: 'Thu, Oct 31 · 11:30 AM · 4 players', history: [['Today', '11:10 AM · 4 players', '$340'], ['Oct 24', '7:20 AM · 4 players', '$340']], passwordSet: true },
  { id: 8, name: 'Lucia Rossi', email: 'lucia.rossi@mail.com', phone: '(555) 301-6604', tier: 'Silver', hcp: 16.5, rounds: 19, last: 'Oct 18', joined: 'May 2023', status: 'Active', spend: 1720, next: 'Today · 11:50 AM · 3 players', history: [['Oct 18', '12:00 PM · 3 players', '$255']], passwordSet: true },
  { id: 9, name: 'Kenji Ito', email: 'kenji.ito@mail.com', phone: '(555) 301-2258', tier: 'Standard', hcp: 24.8, rounds: 4, last: 'Oct 02', joined: 'Sep 2024', status: 'Pending', spend: 220, history: [['Oct 02', '1:00 PM · 1 player', '$55']], passwordSet: false, setupToken: 'demo-kenji-token' },
  { id: 10, name: 'Priya Shah', email: 'priya.s@mail.com', phone: '(555) 301-9183', tier: 'Gold', hcp: 9.6, rounds: 54, last: 'Oct 21', joined: 'Nov 2020', status: 'Active', spend: 6040, next: 'Today · 11:40 AM · 3 players', history: [['Oct 21', '2:30 PM · 4 players', '$340']], passwordSet: true },
]
const extraNames = ['Ethan Novak', 'Carmen Diaz', 'Ben Hall', 'Olivia Chen', 'Marcus Reed', 'Hannah Fox']
export const NAME_POOL = [...seedMembers.map((u) => u.name), ...extraNames]
const FIRST = ['Ava', 'Liam', 'Noah', 'Emma', 'Oliver', 'Mia', 'Lucas', 'Zoe', 'Owen', 'Ivy', 'Leo', 'Ruby', 'Max', 'Nora', 'Eli', 'Grace']
const LAST = ['Walsh', 'Becker', 'Morales', 'Price', 'Hughes', 'Ford', 'Ward', 'Ellis', 'Grant', 'Shaw', 'Lane', 'Banks']
export const fakeNames = (n: number, seed: number) => { const r = rng(seed); return Array.from({ length: n }, () => `${FIRST[Math.floor(r() * FIRST.length)]} ${LAST[Math.floor(r() * LAST.length)]}`) }

export function seedDay(o: number): DayData {
  const rand = rng(((o + 50) * 7919) % 233280)
  const busy = isWeekend(o) ? 1.25 : 1
  const d: DayData = {}
  TEES.forEach((_, t) => SLOT_MINS.forEach((m) => {
    if (o % 3 === 0 && t === 0 && m >= 570 && m < 630) { d[`${t}-${m}`] = { kind: 'block', reason: 'Maintenance' }; return }
    if (rand() < (m < 600 ? 0.62 : m < 840 ? 0.4 : 0.5) * busy) {
      d[`${t}-${m}`] = {
        kind: 'booking', name: NAME_POOL[Math.floor(rand() * NAME_POOL.length)], party: [2, 3, 4, 4][Math.floor(rand() * 4)],
        holes: rand() < 0.2 ? 9 : 18, status: isPast(o, m) ? (rand() < 0.08 ? 'No-show' : 'Checked in') : 'Booked',
      }
    }
  }))
  return d
}

export const defaultSettings: Settings = {
  courseName: 'Pine Valley Golf Club', phone: '(555) 214-8890', address: '1200 Fairway Drive, Pine Valley, NJ 08021', timezone: 'Eastern (ET)', currency: 'USD ($)',
  openDays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], firstTee: '07:00', lastTee: '17:50', interval: 10,
  maxParty: 4, advanceDays: 14, cancelHours: 24, twilightStart: 900,
  pricing: { weekday: [85, 48], weekend: [110, 62], twilight: [55, 35] },
  flags: { waitlist: true, nine: true, guest: true, deposit: false, twilight: true, dynamic: false, autoOffer: true, nBook: true, nCancel: true, nWait: true, nDaily: false, nSms: false },
  staff: [
    { name: 'Admin', email: 'admin@pinevalley.com', role: 'Owner' },
    { name: 'Grace Walsh', email: 'grace@pinevalley.com', role: 'Manager' },
    { name: 'Tom Becker', email: 'tom@pinevalley.com', role: 'Front Desk' },
    { name: 'Ava Morales', email: 'ava@pinevalley.com', role: 'Front Desk' },
  ],
}

/* ---------- style tokens ---------- */

export const tierCls: Record<Tier, string> = { Gold: 'bg-[#E8C766]/30 text-[#7a5b00]', Silver: 'bg-gray-200 text-gray-600', Standard: 'bg-sand/40 text-[#6b5a3a]' }
export const statusCls: Record<Status, string> = { Active: 'bg-forest/10 text-forest', Suspended: 'bg-danger/10 text-danger', Pending: 'bg-wait/20 text-[#a85d1f]' }
export const toneDot: Record<Tone, string> = { forest: 'bg-forest', wait: 'bg-wait', danger: 'bg-danger', sand: 'bg-sand', gray: 'bg-gray-400' }
export const inputCls = 'w-full h-10 px-3 rounded-xl bg-white border border-gray-200 text-sm outline-none focus:border-forest focus:ring-2 focus:ring-forest/15'
export const btnPrimary = 'h-10 px-4 rounded-xl bg-forest text-white text-sm font-semibold hover:bg-forest-dark disabled:bg-gray-200 disabled:text-gray-400 transition'
export const btnSecondary = 'h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold hover:border-forest disabled:opacity-40 transition'
export const btnGhost = 'h-10 px-4 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition'
export const stripes = 'bg-[repeating-linear-gradient(45deg,#f3f4f6,#f3f4f6_6px,#e5e7eb_6px,#e5e7eb_12px)]'

/* ---------- primitives ---------- */

const ICONS: Record<string, string> = {
  dashboard: 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z',
  grid: 'M4 5h16v15H4zM4 10h16M9 5v15M8 2v4M16 2v4',
  clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
  trophy: 'M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3',
  users: 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0M16 3.5a4 4 0 0 1 0 7M22 21a7 7 0 0 0-4-6.3',
  card: 'M2 6h20v12H2zM2 10h20M6 15h4',
  chart: 'M3 3v18h18M7 15l4-4 3 3 5-6',
  settings: 'M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1M15 4v4M9 10v4M17 16v4',
  bell: 'M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8M10 21h4',
  search: 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM21 21l-4.3-4.3',
  plus: 'M12 5v14M5 12h14',
  x: 'M6 6l12 12M18 6 6 18',
  chevron: 'M9 18l6-6-6-6',
  left: 'M15 18l-6-6 6-6',
  download: 'M12 3v12M7 10l5 5 5-5M5 21h14',
  check: 'M5 12l5 5 9-11',
  flag: 'M5 21V4M5 4h11l-2 4 2 4H5',
  mail: 'M3 5h18v14H3zM3 6l9 7 9-7',
  move: 'M5 9l-3 3 3 3M19 9l3 3-3 3M2 12h20',
  refund: 'M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5',
  logout: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
  sun: 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2',
  alert: 'M12 3l10 18H2zM12 10v4M12 17.5h.01',
  trash: 'M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3',
  menu: 'M4 6h16M4 12h16M4 18h16',
}
export function Icon({ name, className = 'w-5 h-5' }: { name: string; className?: string }) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden>
      <path d={ICONS[name]} />
    </svg>
  )
}

export function Toggle({ on, onChange }: { on: boolean; onChange: (v: boolean) => void }) {
  return (
    <button role="switch" aria-checked={on} onClick={() => onChange(!on)}
      className={`relative w-11 h-6 rounded-full transition shrink-0 ${on ? 'bg-forest' : 'bg-gray-300'}`}>
      <span className={`absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-all ${on ? 'left-[22px]' : 'left-0.5'}`} />
    </button>
  )
}

export function Field({ label, children, hint }: { label: string; children: React.ReactNode; hint?: string }) {
  return (
    <label className="block">
      <span className="text-sm font-semibold text-gray-700">{label}</span>
      <div className="mt-1.5">{children}</div>
      {hint && <span className="text-xs text-gray-400 mt-1 block">{hint}</span>}
    </label>
  )
}

export function ModalShell({ title, desc, onClose, children, footer, wide }: { title: string; desc?: string; onClose: () => void; children: React.ReactNode; footer: React.ReactNode; wide?: boolean }) {
  return (
    <div className="fixed inset-0 z-[70] bg-black/40 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-6" onClick={onClose}>
      <div className={`w-full ${wide ? 'sm:max-w-lg' : 'sm:max-w-md'} max-h-[92vh] overflow-auto bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl p-5 sm:p-6`} onClick={(e) => e.stopPropagation()}>
        <div className="flex justify-between items-start mb-5">
          <div><h2 className="text-lg font-extrabold">{title}</h2>{desc && <p className="text-sm text-gray-500">{desc}</p>}</div>
          <button onClick={onClose} aria-label="Close" className="w-8 h-8 rounded-lg text-gray-400 hover:bg-gray-100 flex items-center justify-center"><Icon name="x" className="w-4 h-4" /></button>
        </div>
        <div className="space-y-4">{children}</div>
        <div className="flex justify-end gap-2 mt-6">{footer}</div>
      </div>
    </div>
  )
}

export function PageHeader({ title, subtitle, children }: { title: string; subtitle?: React.ReactNode; children?: React.ReactNode }) {
  return (
    <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-5">
      <div className="min-w-0"><h1 className="text-xl sm:text-2xl font-extrabold">{title}</h1>{subtitle && <div className="text-sm text-gray-500 mt-0.5">{subtitle}</div>}</div>
      {children && <div className="flex flex-wrap gap-2 sm:shrink-0">{children}</div>}
    </div>
  )
}

export function Stat({ label, value, sub, tone = 'gray', bar, highlight }: { label: string; value: React.ReactNode; sub?: React.ReactNode; tone?: 'forest' | 'danger' | 'wait' | 'gray'; bar?: number; highlight?: boolean }) {
  const subCls = highlight ? 'text-sand' : { forest: 'text-forest', danger: 'text-danger', wait: 'text-[#c06a1f]', gray: 'text-gray-400' }[tone]
  return (
    <div className={`rounded-2xl px-4 py-3 sm:px-5 sm:py-4 border min-w-0 ${highlight ? 'bg-forest text-white border-forest' : 'bg-white border-gray-100'}`}>
      <p className={`text-xs font-medium ${highlight ? 'text-white/70' : 'text-gray-500'}`}>{label}</p>
      <p className="text-xl sm:text-2xl font-extrabold mt-0.5 truncate">{value}</p>
      {bar !== undefined ? <div className="mt-2 h-1.5 rounded-full bg-gray-100"><div className="h-full rounded-full bg-forest transition-all" style={{ width: `${Math.min(100, bar)}%` }} /></div>
        : sub && <p className={`text-xs font-semibold mt-0.5 ${subCls}`}>{sub}</p>}
    </div>
  )
}

export function Segmented<T extends string | number | null>({ value, options, onChange, size = 'md' }: { value: T; options: [T, React.ReactNode][]; onChange: (v: T) => void; size?: 'sm' | 'md' }) {
  return (
    <div className="p-1 bg-white border border-gray-200 rounded-xl flex max-w-full overflow-x-auto no-scrollbar">
      {options.map(([v, l]) => (
        <button key={String(v)} onClick={() => onChange(v)} className={`shrink-0 px-3 ${size === 'sm' ? 'h-7 text-xs' : 'h-8 text-sm'} rounded-lg font-semibold whitespace-nowrap transition ${value === v ? 'bg-forest text-white' : 'text-gray-500 hover:text-gray-800'}`}>{l}</button>
      ))}
    </div>
  )
}

export function Avatar({ name, size = 'md', dark }: { name: string; size?: 'sm' | 'md' | 'lg'; dark?: boolean }) {
  const s = { sm: 'w-8 h-8 text-xs', md: 'w-9 h-9 text-xs', lg: 'w-16 h-16 text-xl' }[size]
  return <div className={`${s} rounded-full font-bold flex items-center justify-center shrink-0 ${dark ? 'bg-forest text-white' : 'bg-sand text-forest'}`}>{initials(name)}</div>
}

export function Pill({ children, cls }: { children: React.ReactNode; cls: string }) {
  return <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap ${cls}`}>{children}</span>
}

export function Empty({ icon = 'flag', title, children }: { icon?: string; title: string; children?: React.ReactNode }) {
  return (
    <div className="py-14 flex flex-col items-center text-center">
      <div className="w-12 h-12 rounded-full bg-forest/10 text-forest flex items-center justify-center mb-3"><Icon name={icon} /></div>
      <p className="font-semibold text-gray-700">{title}</p>
      {children && <div className="text-sm text-gray-400 mt-1">{children}</div>}
    </div>
  )
}

/* ---------- responsive helpers ---------- */

export function useMedia(query: string) {
  const [match, setMatch] = useState(() => typeof window !== 'undefined' && window.matchMedia(query).matches)
  useEffect(() => {
    const mq = window.matchMedia(query)
    const h = () => setMatch(mq.matches)
    h(); mq.addEventListener('change', h)
    return () => mq.removeEventListener('change', h)
  }, [query])
  return match
}
export const useDesktop = () => useMedia('(min-width: 1024px)')

/** Side column on desktop; becomes a bottom sheet on phones/tablets while `sheet` is true. */
export function SidePanel({ sheet, onClose, className = '', children }: { sheet: boolean; onClose: () => void; className?: string; children: React.ReactNode }) {
  return (
    <>
      {sheet && <div onClick={onClose} className="fixed inset-0 z-[55] bg-black/40 lg:hidden" />}
      <aside className={`bg-white border border-gray-100 flex flex-col overflow-hidden lg:shrink-0 ${sheet ? 'fixed inset-x-0 bottom-0 z-[60] max-h-[85vh] rounded-t-3xl shadow-2xl lg:static lg:z-auto lg:max-h-none lg:rounded-2xl lg:shadow-none' : 'rounded-2xl'} ${className}`}>
        {sheet && <div className="lg:hidden w-10 h-1 rounded-full bg-gray-200 mx-auto mt-2.5 shrink-0" />}
        {children}
      </aside>
    </>
  )
}
