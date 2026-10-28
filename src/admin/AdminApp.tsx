import { useEffect, useRef, useState } from 'react'
import Dashboard from './Dashboard'
import Events from './Events'
import MasterGrid from './MasterGrid'
import Payments from './Payments'
import Reports from './Reports'
import Settings from './Settings'
import Users from './Users'
import Waitlist from './Waitlist'
import { type Focus, type Page, Avatar, Icon, NOW_MIN, ago, fmtDate, fmtMin, parseKey, toneDot } from './shared'
import { AdminProvider, useAdmin } from './store'

const NAV: { group: string; items: { page: Page; icon: string }[] }[] = [
  { group: 'Overview', items: [{ page: 'Dashboard', icon: 'dashboard' }, { page: 'Reports', icon: 'chart' }] },
  { group: 'Operations', items: [{ page: 'Master Grid', icon: 'grid' }, { page: 'Waitlist', icon: 'clock' }, { page: 'Events', icon: 'trophy' }] },
  { group: 'Management', items: [{ page: 'Users', icon: 'users' }, { page: 'Payments', icon: 'card' }, { page: 'Settings', icon: 'settings' }] },
]

function useOutside(ref: React.RefObject<HTMLElement | null>, onOut: () => void) {
  useEffect(() => {
    const h = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) onOut() }
    document.addEventListener('mousedown', h)
    return () => document.removeEventListener('mousedown', h)
  }, [ref, onOut])
}

function GlobalSearch() {
  const { members, getDay, events, txns, go } = useAdmin()
  const [q, setQ] = useState('')
  const [open, setOpen] = useState(false)
  const [idx, setIdx] = useState(0)
  const box = useRef<HTMLDivElement>(null)
  const input = useRef<HTMLInputElement>(null)
  useOutside(box, () => setOpen(false))
  useEffect(() => {
    const h = (e: KeyboardEvent) => {
      if ((e.key === 'k' && (e.metaKey || e.ctrlKey)) || (e.key === '/' && !(e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement))) { e.preventDefault(); input.current?.focus(); setOpen(true) }
    }
    window.addEventListener('keydown', h)
    return () => window.removeEventListener('keydown', h)
  }, [])

  const term = q.trim().toLowerCase()
  type R = { group: string; title: string; sub: string; page: Page; focus?: Focus; icon: string }
  const results: R[] = !term ? [] : [
    ...NAV.flatMap((g) => g.items).filter((i) => i.page.toLowerCase().includes(term)).map((i) => ({ group: 'Pages', title: i.page, sub: 'Go to page', page: i.page, icon: i.icon })),
    ...members.filter((m) => (m.name + m.email + m.phone).toLowerCase().includes(term)).slice(0, 4).map((m) => ({ group: 'Members', title: m.name, sub: `${m.tier} · ${m.status} · ${m.email}`, page: 'Users' as Page, focus: { memberId: m.id }, icon: 'users' })),
    ...[0, 1].flatMap((o) => Object.entries(getDay(o)).filter(([, e]) => e.kind === 'booking' && e.name.toLowerCase().includes(term)).map(([k, e]) => ({ o, k, e }))).slice(0, 4)
      .map(({ o, k, e }) => ({ group: 'Tee times', title: e.kind === 'booking' ? e.name : '', sub: `${fmtDate(o)} · ${fmtMin(parseKey(k).m)} · ${parseKey(k).t === 0 ? 'Hole 1' : 'Hole 10'}`, page: 'Master Grid' as Page, focus: { offset: o, key: k }, icon: 'grid' })),
    ...events.filter((e) => e.name.toLowerCase().includes(term)).slice(0, 3).map((e) => ({ group: 'Events', title: e.name, sub: `${fmtDate(e.offset)} · ${e.registered.length}/${e.capacity} registered`, page: 'Events' as Page, focus: { eventId: e.id }, icon: 'trophy' })),
    ...txns.filter((t) => t.id.toLowerCase().includes(term)).slice(0, 3).map((t) => ({ group: 'Payments', title: t.id, sub: `${t.name} · $${t.amount} · ${t.status}`, page: 'Payments' as Page, focus: { txnId: t.id }, icon: 'card' })),
  ]
  const choose = (r: R) => { go(r.page, r.focus); setQ(''); setOpen(false); input.current?.blur() }

  return (
    <div ref={box} className="relative flex-1 min-w-0 max-w-md">
      <Icon name="search" className="w-4 h-4 absolute left-3.5 top-3 text-gray-400" />
      <input ref={input} value={q} onFocus={() => setOpen(true)} onChange={(e) => { setQ(e.target.value); setOpen(true); setIdx(0) }}
        onKeyDown={(e) => {
          if (e.key === 'ArrowDown') { e.preventDefault(); setIdx((i) => Math.min(results.length - 1, i + 1)) }
          if (e.key === 'ArrowUp') { e.preventDefault(); setIdx((i) => Math.max(0, i - 1)) }
          if (e.key === 'Enter' && results[idx]) choose(results[idx])
          if (e.key === 'Escape') { setOpen(false); input.current?.blur() }
        }}
        placeholder="Search members, tee times, events…" className="w-full h-10 pl-10 pr-3 md:pr-14 rounded-xl bg-offwhite border border-transparent text-sm outline-none focus:bg-white focus:border-forest focus:ring-2 focus:ring-forest/15" />
      <kbd className="hidden md:block absolute right-3 top-2.5 text-[10px] font-semibold text-gray-400 border border-gray-200 rounded px-1.5 py-0.5 bg-white">⌘K</kbd>
      {open && term && (
        <div className="fixed sm:absolute left-3 right-3 sm:left-0 sm:right-0 top-16 sm:top-12 bg-white rounded-2xl shadow-2xl border border-gray-100 p-2 z-50 max-h-[70vh] sm:max-h-96 overflow-auto">
          {results.length === 0 ? <p className="px-3 py-6 text-sm text-gray-400 text-center">No results for "{q}"</p> : results.map((r, i) => (
            <div key={r.group + r.title + r.sub}>
              {(i === 0 || results[i - 1].group !== r.group) && <p className="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">{r.group}</p>}
              <button onMouseEnter={() => setIdx(i)} onClick={() => choose(r)} className={`w-full flex items-center gap-3 px-3 py-2 rounded-xl text-left ${idx === i ? 'bg-forest/5' : ''}`}>
                <span className="w-8 h-8 rounded-lg bg-offwhite text-forest flex items-center justify-center shrink-0"><Icon name={r.icon} className="w-4 h-4" /></span>
                <span className="min-w-0 flex-1"><span className="block text-sm font-semibold truncate">{r.title}</span><span className="block text-xs text-gray-400 truncate">{r.sub}</span></span>
                {idx === i && <Icon name="chevron" className="w-4 h-4 text-gray-300" />}
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

function Notifications() {
  const { notifs, setNotifs, go, now } = useAdmin()
  const [open, setOpen] = useState(false)
  const box = useRef<HTMLDivElement>(null)
  useOutside(box, () => setOpen(false))
  const unread = notifs.filter((n) => !n.read).length
  return (
    <div ref={box} className="relative">
      <button onClick={() => setOpen(!open)} aria-label="Notifications" className={`relative w-10 h-10 rounded-xl flex items-center justify-center transition ${open ? 'bg-forest/10 text-forest' : 'text-gray-500 hover:bg-gray-100'}`}>
        <Icon name="bell" />
        {unread > 0 && <span className="absolute top-1.5 right-1.5 min-w-4 h-4 px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white">{unread}</span>}
      </button>
      {open && (
        <div className="fixed sm:absolute left-3 right-3 sm:left-auto sm:right-0 top-16 sm:top-12 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden">
          <div className="flex justify-between items-center px-4 py-3 border-b border-gray-100">
            <p className="font-bold">Notifications</p>
            <button disabled={!unread} onClick={() => setNotifs((l) => l.map((n) => ({ ...n, read: true })))} className="text-xs font-semibold text-forest disabled:text-gray-300">Mark all as read</button>
          </div>
          <div className="max-h-[60vh] sm:max-h-96 overflow-auto">
            {notifs.length === 0 && <p className="p-6 text-center text-sm text-gray-400">You're all caught up</p>}
            {notifs.map((n) => (
              <button key={n.id} onClick={() => { setNotifs((l) => l.map((x) => (x.id === n.id ? { ...x, read: true } : x))); go(n.page, n.focus); setOpen(false) }}
                className={`w-full flex gap-3 px-4 py-3 text-left border-b border-gray-50 last:border-0 hover:bg-gray-50 ${n.read ? '' : 'bg-forest/[0.03]'}`}>
                <i className={`w-2 h-2 rounded-full mt-1.5 shrink-0 ${n.read ? 'bg-transparent' : toneDot[n.tone]}`} />
                <div className="flex-1 min-w-0"><p className={`text-sm ${n.read ? 'font-medium text-gray-600' : 'font-bold'}`}>{n.title}</p><p className="text-xs text-gray-500 truncate">{n.desc}</p></div>
                <span className="text-[11px] text-gray-400 whitespace-nowrap">{ago(n.at, now)}</span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

function ProfileMenu() {
  const { go, toast } = useAdmin()
  const [open, setOpen] = useState(false)
  const box = useRef<HTMLDivElement>(null)
  useOutside(box, () => setOpen(false))
  return (
    <div ref={box} className="relative">
      <button onClick={() => setOpen(!open)} aria-label="Account menu" className="flex items-center gap-2.5 pl-1 sm:pr-2 h-10 rounded-xl hover:bg-gray-100">
        <Avatar name="Admin User" size="sm" dark />
        <span className="hidden sm:block text-left leading-tight"><span className="block text-sm font-semibold">Admin</span><span className="block text-[11px] text-gray-400">Owner</span></span>
      </button>
      {open && (
        <div className="absolute right-0 top-12 w-52 bg-white rounded-2xl shadow-2xl border border-gray-100 p-1.5 z-50">
          <button onClick={() => { go('Settings'); setOpen(false) }} className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-50"><Icon name="settings" className="w-4 h-4" />Settings</button>
          <button onClick={() => { toast('Keyboard: ⌘K or / to search'); setOpen(false) }} className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-50"><Icon name="search" className="w-4 h-4" />Shortcuts</button>
          <button onClick={() => { toast('Signed out (demo)'); setOpen(false) }} className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-danger hover:bg-danger/5"><Icon name="logout" className="w-4 h-4" />Sign out</button>
        </div>
      )}
    </div>
  )
}

function SidebarContent({ compact, onNavigate }: { compact: boolean; onNavigate: () => void }) {
  const { page, go, members, waitlist, events, txns, settings } = useAdmin()
  const badges: Partial<Record<Page, number>> = {
    Users: members.filter((m) => m.status === 'Pending').length,
    Waitlist: waitlist.length,
    Events: events.filter((e) => e.offset >= 0 && e.offset <= 7).length,
    Payments: txns.filter((t) => t.status === 'Pending').length,
  }
  const short = settings.courseName.replace(/\s+(Golf Club|Golf Course|GC|Country Club)$/i, '')
  // In compact mode labels collapse to icons between lg and xl (small laptops)
  const full = compact ? 'lg:hidden xl:block' : ''
  return (
    <>
      <button onClick={() => { go('Dashboard'); onNavigate() }} className={`flex items-center gap-2.5 text-white px-2 mb-7 mt-1 text-left ${compact ? 'lg:justify-center lg:px-0 xl:justify-start xl:px-2' : ''}`}>
        <span className="w-9 h-9 rounded-xl bg-forest flex items-center justify-center shrink-0"><Icon name="flag" className="w-5 h-5" /></span>
        <span className={`leading-tight min-w-0 ${full}`}><span className="block font-extrabold truncate">{short}</span><span className="block text-[11px] text-white/40">Course admin</span></span>
      </button>
      <nav className="space-y-5 flex-1 overflow-auto no-scrollbar">
        {NAV.map((g) => (
          <div key={g.group}>
            <p className={`px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-white/30 ${full}`}>{g.group}</p>
            {compact && <div className="hidden lg:block xl:hidden h-px bg-white/10 mx-2 mb-2" />}
            {g.items.map(({ page: p, icon }) => (
              <button key={p} title={p} onClick={() => { go(p); onNavigate() }}
                className={`relative w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mb-0.5 transition ${compact ? 'lg:justify-center xl:justify-start' : ''} ${p === page ? 'bg-forest text-white' : 'hover:bg-white/5 hover:text-white'}`}>
                <Icon name={icon} className="w-[18px] h-[18px] shrink-0" /><span className={`flex-1 text-left ${full}`}>{p}</span>
                {!!badges[p] && <>
                  <span className={`${compact ? 'lg:hidden xl:flex' : ''} flex min-w-5 h-5 px-1.5 rounded-full text-[10px] font-bold items-center justify-center ${p === page ? 'bg-white/20 text-white' : p === 'Users' || p === 'Payments' ? 'bg-wait text-[#3d2006]' : 'bg-white/10 text-white/80'}`}>{badges[p]}</span>
                  {compact && <i className="hidden lg:block xl:hidden absolute top-1.5 right-3 w-2 h-2 rounded-full bg-wait ring-2 ring-[#0f2418]" />}
                </>}
              </button>
            ))}
          </div>
        ))}
      </nav>
      <div className={`rounded-xl bg-white/5 p-3 mt-4 ${full}`}>
        <p className="text-[11px] text-white/40">Course status</p>
        <p className="text-sm font-semibold text-white flex items-center gap-2 mt-0.5"><i className="w-2 h-2 rounded-full bg-[#4ade80] animate-pulse" />Open · {fmtMin(NOW_MIN)}</p>
        <p className="text-[11px] text-white/40 mt-1">68°F · Carts on fairways</p>
      </div>
    </>
  )
}

function Shell() {
  const { page, focus, go, toastMsg } = useAdmin()
  const [navOpen, setNavOpen] = useState(false)
  useEffect(() => setNavOpen(false), [page, focus])
  const pages: Record<Page, () => React.ReactElement> = {
    Dashboard: () => <Dashboard />, 'Master Grid': () => <MasterGrid />, Waitlist: () => <Waitlist />, Events: () => <Events />,
    Users: () => <Users />, Payments: () => <Payments />, Reports: () => <Reports />, Settings: () => <Settings />,
  }
  return (
    <div className="w-full lg:max-w-[1440px] min-h-screen lg:min-h-[640px] lg:h-[calc(100vh-7rem)] lg:max-h-[960px] lg:rounded-2xl lg:overflow-hidden lg:shadow-2xl flex bg-offwhite relative">
      {/* Desktop / laptop sidebar */}
      <aside className="hidden lg:flex lg:w-20 xl:w-60 shrink-0 bg-[#0f2418] text-white/70 p-3 xl:p-4 flex-col">
        <SidebarContent compact onNavigate={() => {}} />
      </aside>

      {/* Phone / tablet drawer */}
      <div className={`fixed inset-0 z-[80] lg:hidden ${navOpen ? '' : 'pointer-events-none'}`} aria-hidden={!navOpen}>
        <div onClick={() => setNavOpen(false)} className={`absolute inset-0 bg-black/50 transition-opacity ${navOpen ? 'opacity-100' : 'opacity-0'}`} />
        <aside className={`absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-[#0f2418] text-white/70 p-4 flex flex-col shadow-2xl transition-transform duration-300 ${navOpen ? 'translate-x-0' : '-translate-x-full'}`}>
          <button onClick={() => setNavOpen(false)} aria-label="Close menu" className="absolute top-4 right-3 w-9 h-9 rounded-lg text-white/60 hover:bg-white/10 flex items-center justify-center"><Icon name="x" className="w-4 h-4" /></button>
          <SidebarContent compact={false} onNavigate={() => setNavOpen(false)} />
        </aside>
      </div>

      <div className="flex-1 flex flex-col min-w-0">
        <header className="sticky top-0 z-40 lg:static h-14 sm:h-16 shrink-0 bg-white/95 backdrop-blur lg:bg-white border-b border-gray-100 px-3 sm:px-6 flex items-center gap-2 sm:gap-4">
          <button onClick={() => setNavOpen(true)} aria-label="Open menu" className="lg:hidden w-10 h-10 shrink-0 rounded-xl text-gray-600 hover:bg-gray-100 flex items-center justify-center"><Icon name="menu" /></button>
          <GlobalSearch />
          <div className="ml-auto flex items-center gap-1 sm:gap-2 shrink-0">
            <button onClick={() => go('Master Grid', { offset: 0 })} className="hidden xl:flex h-10 px-3 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 items-center gap-2"><Icon name="grid" className="w-4 h-4" />Today's tee sheet</button>
            <Notifications />
            <div className="hidden sm:block w-px h-6 bg-gray-200 mx-1" />
            <ProfileMenu />
          </div>
        </header>
        <div key={page + JSON.stringify(focus)} className="flex-1 lg:min-h-0 flex">{pages[page]()}</div>
      </div>
      <div className={`fixed bottom-6 left-1/2 -translate-x-1/2 z-[90] max-w-[calc(100vw-2rem)] px-4 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold shadow-lg flex items-center gap-2 transition-all duration-300 ${toastMsg ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2 pointer-events-none'}`}>
        <Icon name="check" className="w-4 h-4 shrink-0 text-[#4ade80]" /><span className="truncate">{toastMsg}</span>
      </div>
    </div>
  )
}

export default function AdminApp() {
  return <AdminProvider><Shell /></AdminProvider>
}
