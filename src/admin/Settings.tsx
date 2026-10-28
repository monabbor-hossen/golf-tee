import { useState } from 'react'
import {
  type Settings, type Staff, Avatar, Field, Icon, ModalShell, PageHeader, Pill, Toggle,
  btnGhost, btnPrimary, btnSecondary, extractCurrencySymbol, fmtMin, inputCls, WORLD_CURRENCIES,
} from './shared'
import { useAdmin } from './store'

const TABS = ['Course', 'Tee Sheet', 'Pricing', 'Waitlist', 'Notifications', 'Staff'] as const
const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

function InviteModal({ onClose, onSave }: { onClose: () => void; onSave: (s: Staff) => void }) {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [role, setRole] = useState<Staff['role']>('Front Desk')
  const valid = name.trim() && /\S+@\S+\.\S+/.test(email)
  const perms: Record<Staff['role'], string> = { Owner: '', Manager: 'Everything except billing and staff management.', 'Front Desk': 'Tee sheet, check-in, waitlist and payments.', 'Read only': 'View dashboards and reports only.' }
  return (
    <ModalShell title="Invite staff member" desc="They'll get an email to set up their account." onClose={onClose}
      footer={<><button onClick={onClose} className={btnGhost}>Cancel</button><button disabled={!valid} onClick={() => onSave({ name: name.trim(), email: email.trim(), role })} className={btnPrimary}>Send invite</button></>}>
      <Field label="Name"><input autoFocus className={inputCls} value={name} onChange={(e) => setName(e.target.value)} /></Field>
      <Field label="Email"><input className={inputCls} value={email} onChange={(e) => setEmail(e.target.value)} placeholder="name@pinevalley.com" /></Field>
      <Field label="Role" hint={perms[role]}>
        <select className={inputCls} value={role} onChange={(e) => setRole(e.target.value as Staff['role'])}><option>Manager</option><option>Front Desk</option><option>Read only</option></select>
      </Field>
    </ModalShell>
  )
}

export default function SettingsPage() {
  const { settings, setSettings, toast, log } = useAdmin()
  const [tab, setTab] = useState<(typeof TABS)[number]>('Course')
  const [d, setD] = useState<Settings>(settings)
  const [inviting, setInviting] = useState(false)
  const dirty = JSON.stringify(d) !== JSON.stringify(settings)
  const up = <K extends keyof Settings>(k: K, v: Settings[K]) => setD((x) => ({ ...x, [k]: v }))
  const flag = (k: string) => (v: boolean) => setD((x) => ({ ...x, flags: { ...x.flags, [k]: v } }))
  const setPrice = (row: keyof Settings['pricing'], i: 0 | 1, v: string) => setD((x) => {
    const r = [...x.pricing[row]] as [number, number]; r[i] = Math.max(0, Math.round(+v || 0))
    return { ...x, pricing: { ...x.pricing, [row]: r } }
  })
  const save = () => { setSettings(d); toast('Settings saved'); log('Settings updated', `${tab} preferences changed`, 'sand') }

  const row = (k: string, t: string, desc: string) => (
    <div key={k} className="flex items-center justify-between gap-6 py-4 border-b border-gray-50 last:border-0">
      <div><p className="text-sm font-semibold">{t}</p><p className="text-xs text-gray-500">{desc}</p></div>
      <Toggle on={!!d.flags[k]} onChange={flag(k)} />
    </div>
  )
  const card = (title: string, desc: string, body: React.ReactNode) => (
    <section className="bg-white rounded-2xl border border-gray-100 p-4 sm:p-6">
      <h2 className="font-bold">{title}</h2><p className="text-sm text-gray-500 mb-5">{desc}</p>{body}
    </section>
  )
  const num = (v: number, set: (n: number) => void, min = 0) => <input type="number" min={min} className={inputCls} value={v} onChange={(e) => set(Math.max(min, +e.target.value || 0))} />

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-hidden flex flex-col">
      <PageHeader title="Settings" subtitle="Manage course details, booking rules, pricing and team access">
        {dirty && <span className="hidden lg:flex self-center text-sm font-semibold text-[#a85d1f] items-center gap-1.5"><i className="w-2 h-2 rounded-full bg-wait" />Unsaved changes</span>}
        <button disabled={!dirty} onClick={() => setD(settings)} className={btnSecondary}>Discard</button>
        <button disabled={!dirty} onClick={save} className={btnPrimary}>Save Changes</button>
      </PageHeader>
      <div className="flex flex-col lg:flex-row gap-4 lg:gap-6 lg:flex-1 lg:min-h-0">
        <nav className="flex lg:flex-col gap-1 lg:w-48 shrink-0 overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
          {TABS.map((t) => (
            <button key={t} onClick={() => setTab(t)}
              className={`shrink-0 whitespace-nowrap lg:w-full text-left px-3 py-2.5 rounded-lg text-sm font-semibold ${tab === t ? 'bg-white text-forest shadow-sm border border-gray-100' : 'text-gray-500 hover:text-gray-800'}`}>{t}</button>
          ))}
        </nav>
        <div className="lg:flex-1 min-w-0 lg:overflow-auto space-y-4 sm:space-y-5 lg:pr-1 pb-20 lg:pb-4">
          {tab === 'Course' && <>
            {card('Course profile', 'Shown to members in the mobile app and on receipts.', (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Field label="Course name"><input className={inputCls} value={d.courseName} onChange={(e) => up('courseName', e.target.value)} /></Field>
                <Field label="Phone"><input className={inputCls} value={d.phone} onChange={(e) => up('phone', e.target.value)} /></Field>
                <div className="sm:col-span-2"><Field label="Address"><input className={inputCls} value={d.address} onChange={(e) => up('address', e.target.value)} /></Field></div>
                <Field label="Time zone"><select className={inputCls} value={d.timezone} onChange={(e) => up('timezone', e.target.value)}>{['Eastern (ET)', 'Central (CT)', 'Mountain (MT)', 'Pacific (PT)'].map((z) => <option key={z}>{z}</option>)}</select></Field>
                <Field label="Currency"><select className={inputCls} value={d.currency} onChange={(e) => up('currency', e.target.value)}>{WORLD_CURRENCIES.map((c) => <option key={c}>{c}</option>)}</select></Field>
              </div>
            ))}
            {card('Operating hours', 'Days you are open and your first and last tee times.', (
              <>
                <div className="flex flex-wrap gap-2 mb-5">
                  {DAYS.map((day) => (
                    <button key={day} onClick={() => up('openDays', d.openDays.includes(day) ? d.openDays.filter((x) => x !== day) : [...d.openDays, day])}
                      className={`w-12 h-10 rounded-xl text-sm font-semibold border ${d.openDays.includes(day) ? 'bg-forest text-white border-forest' : 'bg-white text-gray-400 border-gray-200'}`}>{day}</button>
                  ))}
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <Field label="First tee time"><input type="time" className={inputCls} value={d.firstTee} onChange={(e) => up('firstTee', e.target.value)} /></Field>
                  <Field label="Last tee time"><input type="time" className={inputCls} value={d.lastTee} onChange={(e) => up('lastTee', e.target.value)} /></Field>
                </div>
              </>
            ))}
          </>}
          {tab === 'Tee Sheet' && <>
            {card('Tee time interval', 'Minutes between consecutive tee times.', (
              <div className="flex flex-wrap gap-2">
                {[7, 8, 10, 12, 15].map((m) => (
                  <button key={m} onClick={() => up('interval', m)} className={`px-4 h-10 rounded-xl text-sm font-semibold border ${d.interval === m ? 'bg-forest text-white border-forest' : 'bg-white text-gray-600 border-gray-200 hover:border-forest'}`}>{m} min</button>
                ))}
              </div>
            ))}
            {card('Booking rules', 'Applied to member bookings and the admin booking form.', (
              <>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-2">
                  <Field label="Max party size">{num(d.maxParty, (n) => up('maxParty', Math.min(4, Math.max(1, n))), 1)}</Field>
                  <Field label="Advance booking" hint="Days ahead members can book">{num(d.advanceDays, (n) => up('advanceDays', n), 1)}</Field>
                  <Field label="Cancellation window" hint="Hours before tee time for a full refund">{num(d.cancelHours, (n) => up('cancelHours', n))}</Field>
                </div>
                {row('nine', 'Allow 9-hole bookings', 'Show the 9 Holes option in the mobile app and booking form.')}
                {row('guest', 'Allow guests', 'Members can add non-member guests to their party.')}
              </>
            ))}
          </>}
          {tab === 'Pricing' && <>
            {card('Green fees', 'Per-player rates used across the tee sheet, dashboard and reports.', (
              <div className="overflow-x-auto rounded-xl border border-gray-100">
                <table className="w-full text-sm">
                  <thead className="bg-offwhite text-xs uppercase text-gray-500 text-left"><tr><th className="p-3">Rate</th><th className="p-3">18 holes</th><th className="p-3">9 holes</th></tr></thead>
                  <tbody>
                    {([['weekday', 'Weekday'], ['weekend', 'Weekend'], ['twilight', `Twilight (after ${fmtMin(d.twilightStart)})`]] as const).map(([k, n]) => (
                      <tr key={k} className="border-t border-gray-100">
                        <td className="p-3 font-semibold">{n}</td>
                        {([0, 1] as const).map((i) => <td key={i} className="p-2"><div className="relative w-20 sm:w-28"><span className="absolute left-3 top-2.5 text-gray-400">{extractCurrencySymbol(d.currency)}</span><input type="number" min={0} className={inputCls + ' pl-6'} value={d.pricing[k][i]} onChange={(e) => setPrice(k, i, e.target.value)} /></div></td>)}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ))}
            {card('Pricing rules', 'How and when players are charged.', (
              <>
                {row('twilight', 'Twilight pricing', 'Automatically apply twilight rates late in the day.')}
                {d.flags.twilight && (
                  <div className="py-3 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-6">
                    <div><p className="text-sm font-semibold">Twilight starts at</p><p className="text-xs text-gray-500">Tee times from this time use twilight rates.</p></div>
                    <select className={inputCls + ' sm:w-36'} value={d.twilightStart} onChange={(e) => up('twilightStart', +e.target.value)}>{[780, 840, 900, 960].map((m) => <option key={m} value={m}>{fmtMin(m)}</option>)}</select>
                  </div>
                )}
                {row('deposit', 'Require deposit at booking', 'New bookings are recorded as pending until paid at check-in.')}
                {row('dynamic', 'Dynamic pricing', 'Adjust rates based on demand and weather.')}
              </>
            ))}
            <div className="rounded-2xl bg-forest/5 border border-forest/15 px-4 sm:px-5 py-4 text-sm text-forest flex items-center gap-3">
              <Icon name="chart" className="w-5 h-5 shrink-0" />
              <span>Preview: a weekday foursome (18 holes) pays <b>{extractCurrencySymbol(d.currency)}{d.pricing.weekday[0] * 4}</b>, weekend <b>{extractCurrencySymbol(d.currency)}{d.pricing.weekend[0] * 4}</b>, twilight <b>{extractCurrencySymbol(d.currency)}{d.pricing.twilight[0] * 4}</b>.</span>
            </div>
          </>}
          {tab === 'Waitlist' && card('Waitlist', 'Fill cancellations automatically.', (
            <>
              {row('waitlist', 'Enable waitlist', 'Members can join a waitlist for fully booked times.')}
              {row('autoOffer', 'Auto-offer freed slots', 'When a booking is cancelled, offer it to the closest waiting group.')}
              {row('nWait', 'Notify front desk', 'Show a notification whenever a waitlist match is found.')}
            </>
          ))}
          {tab === 'Notifications' && card('Admin notifications', 'Choose what the front desk is alerted about.', (
            <>
              {row('nBook', 'New bookings', 'Email when a member books a tee time.')}
              {row('nCancel', 'Cancellations', 'Email when a booking is cancelled.')}
              {row('nDaily', 'Daily summary', 'A recap of revenue and rounds every evening at 7 PM.')}
              {row('nSms', 'SMS alerts', 'Send urgent alerts by text message.')}
            </>
          ))}
          {tab === 'Staff' && card('Staff accounts', 'People with access to this admin dashboard.', (
            <>
              <div className="divide-y divide-gray-50">
                {d.staff.map((s, i) => (
                  <div key={s.email} className="flex flex-wrap sm:flex-nowrap items-center gap-3 py-3">
                    <Avatar name={s.name} />
                    <div className="flex-1 min-w-0"><p className="text-sm font-semibold truncate">{s.name}</p><p className="text-xs text-gray-400 truncate">{s.email}</p></div>
                    {s.role === 'Owner' ? <Pill cls="bg-forest/10 text-forest">Owner</Pill> : <>
                      <select value={s.role} onChange={(e) => up('staff', d.staff.map((x, j) => (j === i ? { ...x, role: e.target.value as Staff['role'] } : x)))} className="h-9 px-2 rounded-lg border border-gray-200 text-sm bg-white"><option>Manager</option><option>Front Desk</option><option>Read only</option></select>
                      <button onClick={() => up('staff', d.staff.filter((_, j) => j !== i))} aria-label={`Remove ${s.name}`} className="w-8 h-8 rounded-lg text-gray-400 hover:text-danger hover:bg-danger/10 flex items-center justify-center"><Icon name="trash" className="w-4 h-4" /></button>
                    </>}
                  </div>
                ))}
              </div>
              <button onClick={() => setInviting(true)} className="mt-4 h-10 px-4 rounded-xl border border-dashed border-gray-300 text-sm font-semibold text-gray-600 hover:border-forest hover:text-forest">+ Invite staff member</button>
            </>
          ))}
        </div>
      </div>
      {dirty && (
        <div className="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur border-t border-gray-100 p-3 flex items-center gap-2 shadow-[0_-8px_24px_rgba(0,0,0,0.06)]">
          <span className="flex-1 text-xs font-semibold text-[#a85d1f] flex items-center gap-1.5"><i className="w-2 h-2 rounded-full bg-wait" />Unsaved changes</span>
          <button onClick={() => setD(settings)} className={btnSecondary}>Discard</button>
          <button onClick={save} className={btnPrimary}>Save</button>
        </div>
      )}
      {inviting && <InviteModal onClose={() => setInviting(false)} onSave={(s) => { up('staff', [...d.staff, s]); setInviting(false); toast(`Invite ready for ${s.email} — save to send`) }} />}
    </main>
  )
}
