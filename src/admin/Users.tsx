import { useState } from 'react'
import {
  type Member, type Status, type Tier, Field, Icon, ModalShell, SidePanel, btnGhost, btnPrimary, btnSecondary, downloadCSV,
  fmtDate, fmtMin, initials, inputCls, statusCls, tierCls, useDesktop,
} from './shared'
import { useAdmin } from './store'

function MemberModal({ member, onClose, onSave }: { member: Partial<Member> | null; onClose: () => void; onSave: (m: Partial<Member>) => void }) {
  const [f, setF] = useState<Partial<Member>>(member ?? {})
  const editing = !!member?.id
  const valid = (f.name ?? '').trim() && /\S+@\S+\.\S+/.test(f.email ?? '')
  const up = (k: keyof Member) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) =>
    setF({ ...f, [k]: k === 'hcp' ? parseFloat(e.target.value) || 0 : e.target.value })
  return (
    <div className="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-6" onClick={onClose}>
      <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-6" onClick={(e) => e.stopPropagation()}>
        <div className="flex justify-between items-start mb-5">
          <div><h2 className="text-lg font-extrabold">{editing ? 'Edit member' : 'Add member'}</h2>
            <p className="text-sm text-gray-500">{editing ? 'Update profile and membership details.' : 'Accounts are invite-only. A password setup link will be emailed to the member.'}</p></div>
          <button onClick={onClose} aria-label="Close" className="w-8 h-8 rounded-lg text-gray-400 hover:bg-gray-100">✕</button>
        </div>
        <div className="grid grid-cols-2 gap-4">
          {!editing && (
            <div className="col-span-2 p-3 bg-forest/5 border border-forest/20 rounded-xl text-xs text-forest leading-relaxed">
              🔒 <strong>Invite-Only Membership:</strong> Users cannot create accounts themselves. Saving this member automatically generates and emails a password activation link to their address.
            </div>
          )}
          <div className="col-span-2"><Field label="Full name"><input autoFocus className={inputCls} value={f.name ?? ''} onChange={up('name')} placeholder="Jane Doe" /></Field></div>
          <Field label="Email"><input className={inputCls} value={f.email ?? ''} onChange={up('email')} placeholder="jane@mail.com" /></Field>
          <Field label="Phone"><input className={inputCls} value={f.phone ?? ''} onChange={up('phone')} placeholder="(555) 000-0000" /></Field>
          <Field label="Membership tier">
            <select className={inputCls} value={f.tier ?? 'Standard'} onChange={up('tier')}><option>Gold</option><option>Silver</option><option>Standard</option></select>
          </Field>
          <Field label="Handicap"><input type="number" step="0.1" className={inputCls} value={f.hcp ?? ''} onChange={up('hcp')} placeholder="18.0" /></Field>
        </div>
        <div className="flex justify-end gap-2 mt-6">
          <button onClick={onClose} className="h-10 px-4 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
          <button disabled={!valid} onClick={() => onSave(f)}
            className="h-10 px-5 rounded-xl bg-forest text-white text-sm font-semibold hover:bg-forest-dark disabled:bg-gray-200 disabled:text-gray-400">
            {editing ? 'Save changes' : 'Add member'}</button>
        </div>
      </div>
    </div>
  )
}

type SortKey = 'name' | 'hcp' | 'rounds' | 'spend'

export default function UsersPage() {
  const { members: list, setMembers: setList, focus, toast: notify, log, waitlist, txns, go, currSym } = useAdmin()
  const [q, setQ] = useState('')
  const [tier, setTier] = useState<'All' | Tier>('All')
  const [status, setStatus] = useState<'All' | Status>('All')
  const [sort, setSort] = useState<{ k: SortKey; dir: 1 | -1 }>({ k: 'name', dir: 1 })
  const desktop = useDesktop()
  const [selId, setSelId] = useState<number | null>(focus?.memberId ?? (desktop ? 1 : null))
  const [messaging, setMessaging] = useState<number[] | null>(null)
  const [checked, setChecked] = useState<number[]>([])
  const [modal, setModal] = useState<Partial<Member> | null | undefined>(undefined)
  const patch = (ids: number[], p: Partial<Member>) => setList((l) => l.map((m) => (ids.includes(m.id) ? { ...m, ...p } : m)))

  const counts = { All: list.length, Active: 0, Pending: 0, Suspended: 0 } as Record<'All' | Status, number>
  list.forEach((m) => counts[m.status]++)

  const rows = list
    .filter((u) => (tier === 'All' || u.tier === tier) && (status === 'All' || u.status === status) && (u.name + u.email).toLowerCase().includes(q.toLowerCase()))
    .sort((a, b) => (sort.k === 'name' ? a.name.localeCompare(b.name) : a[sort.k] - b[sort.k]) * sort.dir)
  const u = list.find((m) => m.id === selId)
  const allChecked = rows.length > 0 && rows.every((r) => checked.includes(r.id))

  const th = (k: SortKey, label: string, cls = '') => (
    <th className={`p-3 ${cls}`}>
      <button onClick={() => setSort({ k, dir: sort.k === k ? (-sort.dir as 1 | -1) : 1 })} className={`flex items-center gap-1 uppercase ${sort.k === k ? 'text-forest' : ''}`}>
        {label}<span className="text-[9px]">{sort.k === k ? (sort.dir === 1 ? '▲' : '▼') : ''}</span>
      </button>
    </th>
  )

  const save = (f: Partial<Member>) => {
    if (f.id) { patch([f.id], f); notify(`${f.name} updated`); log('Member updated', `${f.name}`, 'sand') } else {
      const id = Math.max(...list.map((m) => m.id)) + 1
      const setupToken = 'invite_' + Math.random().toString(36).substring(2, 10)
      setList([{
        id,
        name: f.name!.trim(),
        email: f.email!,
        phone: f.phone ?? '',
        tier: (f.tier as Tier) ?? 'Standard',
        hcp: f.hcp ?? 0,
        rounds: 0,
        last: '—',
        joined: 'Oct 2024',
        status: 'Pending',
        spend: 0,
        history: [],
        passwordSet: false,
        setupToken
      }, ...list])
      setSelId(id)
      notify(`Account created for ${f.name}! Password setup invitation sent to ${f.email}`)
      log('Member account created & invite sent', `${f.name} · ${f.email} · setup link dispatched`, 'forest')
    }
    setModal(undefined)
  }

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-hidden flex flex-col">
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-5">
        <div><h1 className="text-xl sm:text-2xl font-extrabold">Users</h1><p className="text-sm text-gray-500">{counts.All} members · {counts.Pending} pending approval</p></div>
        <div className="flex gap-2">
          <button onClick={() => downloadCSV('members.csv', [['Name', 'Email', 'Phone', 'Tier', 'Handicap', 'Rounds', 'Spend', 'Status', 'Joined'], ...rows.map((m) => [m.name, m.email, m.phone, m.tier, m.hcp, m.rounds, m.spend, m.status, m.joined])])} aria-label="Export members" className={btnSecondary + ' flex items-center gap-2'}><Icon name="download" className="w-4 h-4" />Export</button>
          <button onClick={() => setModal(null)} className={btnPrimary + ' flex-1 sm:flex-none'}>+ Add Member</button>
        </div>
      </div>

      <div className="flex gap-1 mb-4 border-b border-gray-200 overflow-x-auto no-scrollbar">
        {(['All', 'Active', 'Pending', 'Suspended'] as const).map((s) => (
          <button key={s} onClick={() => setStatus(s)}
            className={`shrink-0 px-3 sm:px-4 pb-2.5 -mb-px text-sm font-semibold border-b-2 transition ${status === s ? 'border-forest text-forest' : 'border-transparent text-gray-500 hover:text-gray-800'}`}>
            {s}<span className={`ml-1.5 px-1.5 py-0.5 rounded-md text-[11px] ${status === s ? 'bg-forest/10' : 'bg-gray-100'}`}>{counts[s]}</span>
          </button>
        ))}
      </div>

      <div className="flex flex-col sm:flex-row gap-3 mb-4">
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search by name or email" className={inputCls + ' flex-1'} />
        <div className="p-1 bg-white border border-gray-200 rounded-xl flex overflow-x-auto no-scrollbar">
          {(['All', 'Gold', 'Silver', 'Standard'] as const).map((t) => (
            <button key={t} onClick={() => setTier(t)} className={`flex-1 sm:flex-none px-3 h-8 rounded-lg text-sm font-semibold ${tier === t ? 'bg-forest text-white' : 'text-gray-500'}`}>{t}</button>
          ))}
        </div>
      </div>

      <div className="flex flex-col lg:flex-row gap-4 lg:flex-1 lg:min-h-0">
        <div className="lg:flex-1 bg-white rounded-2xl border border-gray-100 overflow-auto relative">
          {checked.length > 0 && (
            <div className="sticky top-0 z-20 flex flex-wrap items-center gap-2 sm:gap-3 px-3 sm:px-5 py-2 min-h-12 bg-forest text-white text-sm">
              <span className="font-semibold">{checked.length} selected</span>
              <button onClick={() => setMessaging(checked)} className="px-3 h-8 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">Message</button>
              <button onClick={() => { patch(checked, { status: 'Active' }); notify(`${checked.length} members activated`); log('Members activated', `${checked.length} accounts`, 'forest'); setChecked([]) }} className="px-3 h-8 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">Activate</button>
              <button onClick={() => { patch(checked, { status: 'Suspended' }); notify(`${checked.length} members suspended`); setChecked([]) }} className="px-3 h-8 rounded-lg bg-white/15 hover:bg-white/25 font-semibold">Suspend</button>
              <button onClick={() => setChecked([])} className="ml-auto text-white/70 hover:text-white">Clear</button>
            </div>
          )}
          <table className="w-full text-sm">
            <thead className={`${checked.length ? 'hidden sm:table-header-group sm:sticky sm:top-12' : 'sticky top-0'} z-10 bg-white text-xs uppercase text-gray-500 text-left border-b border-gray-100`}>
              <tr>
                <th className="p-3 pl-3 sm:pl-5 w-8"><input type="checkbox" className="accent-forest w-4 h-4" checked={allChecked}
                  onChange={() => setChecked(allChecked ? [] : rows.map((r) => r.id))} /></th>
                {th('name', 'Member')}<th className="p-3 hidden sm:table-cell">Tier</th>{th('hcp', 'Hcp', 'hidden md:table-cell')}{th('rounds', 'Rounds', 'hidden md:table-cell')}{th('spend', 'Spend', 'hidden xl:table-cell')}<th className="p-3 pr-3 sm:pr-5">Status</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((x) => (
                <tr key={x.id} onClick={() => setSelId(x.id)} className={`border-b border-gray-50 cursor-pointer ${selId === x.id ? 'bg-forest/5' : 'hover:bg-gray-50'}`}>
                  <td className="p-3 pl-3 sm:pl-5" onClick={(e) => e.stopPropagation()}>
                    <input type="checkbox" className="accent-forest w-4 h-4" checked={checked.includes(x.id)}
                      onChange={() => setChecked(checked.includes(x.id) ? checked.filter((c) => c !== x.id) : [...checked, x.id])} />
                  </td>
                  <td className="p-3"><div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-full bg-sand text-forest font-bold text-xs flex items-center justify-center shrink-0">{initials(x.name)}</div>
                    <div className="min-w-0"><p className="font-semibold truncate">{x.name}</p><p className="text-xs text-gray-400 truncate max-w-[150px] sm:max-w-none">{x.email}</p></div></div></td>
                  <td className="p-3 hidden sm:table-cell"><span className={`px-2 py-0.5 rounded-full text-xs font-semibold ${tierCls[x.tier]}`}>{x.tier}</span></td>
                  <td className="p-3 font-medium hidden md:table-cell">{x.hcp.toFixed(1)}</td>
                  <td className="p-3 hidden md:table-cell">{x.rounds}</td>
                  <td className="p-3 hidden xl:table-cell">{currSym}{x.spend.toLocaleString()}</td>
                  <td className="p-3 pr-3 sm:pr-5"><span className={`px-2 py-0.5 rounded-full text-xs font-semibold ${statusCls[x.status]}`}>{x.status}</span></td>
                </tr>
              ))}
              {rows.length === 0 && <tr><td colSpan={7} className="p-10 text-center text-gray-400">No members match your filters</td></tr>}
            </tbody>
          </table>
        </div>

        {(u || desktop) && <SidePanel sheet={!!u} onClose={() => setSelId(null)} className="lg:w-80">
          {!u ? <div className="flex-1 flex items-center justify-center text-sm text-gray-400 p-6 text-center">Select a member to see details</div> : <>
            <div className="relative p-5 bg-gradient-to-b from-forest/5 to-transparent text-center">
              <button onClick={() => setSelId(null)} aria-label="Close" className="absolute top-3 right-3 w-8 h-8 rounded-lg text-gray-400 hover:bg-gray-100 flex items-center justify-center"><Icon name="x" className="w-4 h-4" /></button>
              <div className="w-16 h-16 mx-auto rounded-full bg-forest text-white font-bold text-xl flex items-center justify-center">{initials(u.name)}</div>
              <h2 className="mt-3 font-bold text-lg">{u.name}</h2>
              <div className="flex justify-center gap-1.5 mt-2">
                <span className={`px-2 py-0.5 rounded-full text-xs font-semibold ${tierCls[u.tier]}`}>{u.tier}</span>
                <span className={`px-2 py-0.5 rounded-full text-xs font-semibold ${statusCls[u.status]}`}>{u.status}</span>
              </div>
            </div>
            <div className="flex-1 overflow-auto px-5 pb-4 space-y-4">
              <div className="grid grid-cols-3 gap-2">
                {[[u.hcp.toFixed(1), 'Handicap'], [u.rounds, 'Rounds'], [`$${(u.spend / 1000).toFixed(1)}k`, 'Spend']].map(([v, l]) => (
                  <div key={l as string} className="bg-offwhite rounded-xl py-2.5 text-center"><p className="font-extrabold">{v}</p><p className="text-[11px] text-gray-500">{l}</p></div>
                ))}
              </div>
              <dl className="text-sm space-y-1.5">
                {[['Email', u.email], ['Phone', u.phone || '—'], ['Member since', u.joined], ['Last played', u.last]].map(([k, v]) => (
                  <div key={k} className="flex justify-between gap-3"><dt className="text-gray-500">{k}</dt><dd className="font-medium truncate">{v}</dd></div>
                ))}
              </dl>
              <div>
                <h3 className="text-xs font-bold uppercase text-gray-400 mb-2">Upcoming</h3>
                {u.next ? <div className="text-sm border border-forest/20 bg-forest/5 rounded-xl p-3 font-semibold text-forest">{u.next}</div>
                  : <p className="text-sm text-gray-400">No upcoming tee times</p>}
                {waitlist.filter((w) => w.name === u.name).map((w) => (
                  <button key={w.id} onClick={() => go('Waitlist')} className="mt-2 w-full text-left text-sm border border-wait/30 bg-wait/10 rounded-xl p-3 font-semibold text-[#a85d1f]">Waitlist · {fmtDate(w.offset)} ~{fmtMin(w.min)} · {w.party}p</button>
                ))}
              </div>
              <div>
                <h3 className="text-xs font-bold uppercase text-gray-400 mb-2">Recent payments</h3>
                {txns.filter((t) => t.name === u.name).slice(0, 3).map((t) => (
                  <button key={t.id} onClick={() => go('Payments', { txnId: t.id })} className="w-full flex justify-between text-sm py-1.5 border-b border-gray-50 text-left hover:bg-gray-50">
                    <div className="min-w-0"><p className="font-semibold truncate">{t.type}</p><p className="text-xs text-gray-500">{fmtDate(t.offset)} · {t.id}</p></div>
                    <span className={`font-semibold ${t.status === 'Refunded' ? 'text-danger line-through' : ''}`}>{currSym}{t.amount}</span>
                  </button>
                ))}
                {!txns.some((t) => t.name === u.name) && <p className="text-sm text-gray-400">No recent payments</p>}

                {/* App Access & Password Setup */}
                <div className="mt-4 p-3.5 bg-gray-50 border border-gray-200/80 rounded-xl space-y-2">
                  <div className="flex justify-between items-center">
                    <span className="text-[11px] font-bold uppercase tracking-wider text-gray-500">App Security & Access</span>
                    <span className={`px-2 py-0.5 rounded text-[11px] font-bold ${u.passwordSet ? 'bg-forest/10 text-forest' : 'bg-wait/20 text-[#a85d1f]'}`}>
                      {u.passwordSet ? 'Password Active' : 'Invite Sent · Password Pending'}
                    </span>
                  </div>
                  <p className="text-xs text-gray-500 leading-relaxed">
                    {u.passwordSet
                      ? 'Member has configured their password. Authorized to sign in via email/password or Google.'
                      : 'Invitation URL sent to user email. Member must open link to create password before app sign in.'}
                  </p>
                  <div className="flex gap-2 pt-1">
                    <button
                      onClick={() => {
                        const token = u.setupToken || `invite_${u.id}`
                        const baseUrl = (typeof window !== 'undefined' ? window.location.origin : '') + '/golf-tee'
                        const url = `${baseUrl}/admin-php/set-password.php?token=${token}`
                        navigator.clipboard?.writeText(url)
                        notify(`Setup URL copied to clipboard for ${u.name}!`)
                      }}
                      className="flex-1 h-8 rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:border-forest hover:text-forest flex items-center justify-center gap-1.5"
                    >
                      <Icon name="mail" className="w-3.5 h-3.5" />
                      Copy Invite Link
                    </button>
                    <button
                      onClick={() => {
                        notify(`Invitation email re-dispatched to ${u.email}!`)
                        log('Setup invitation resent', `${u.name} · ${u.email}`, 'forest')
                      }}
                      className="px-3 h-8 rounded-lg bg-forest/10 text-forest text-xs font-semibold hover:bg-forest/20"
                    >
                      Resend Email
                    </button>
                  </div>
                </div>

                <h3 className="text-xs font-bold uppercase text-gray-400 mt-4 mb-2">Booking history</h3>
                {u.history.length ? u.history.map(([d, t, a]) => (
                  <div key={d + t} className="flex justify-between text-sm py-1.5 border-b border-gray-50 last:border-0">
                    <div><p className="font-semibold">{d}</p><p className="text-xs text-gray-500">{t}</p></div><span className="font-semibold">{a}</span>
                  </div>
                )) : <p className="text-sm text-gray-400">No bookings yet</p>}
              </div>
            </div>
            <div className="p-4 border-t border-gray-100 space-y-2">
              {u.status === 'Pending' && <button onClick={() => { patch([u.id], { status: 'Active' }); notify(`${u.name} approved`); log('Member approved', `${u.name} · ${u.tier}`, 'forest') }} className={btnPrimary + ' w-full'}>Approve Member</button>}
              <div className="grid grid-cols-3 gap-2">
                <button onClick={() => setMessaging([u.id])} className="h-10 rounded-xl border border-gray-200 text-sm font-semibold hover:border-forest">Message</button>
                <button onClick={() => setModal(u)} className="h-10 rounded-xl border border-gray-200 text-sm font-semibold hover:border-forest">Edit</button>
                <button onClick={() => { const s: Status = u.status === 'Suspended' ? 'Active' : 'Suspended'; patch([u.id], { status: s }); notify(`${u.name} ${s === 'Active' ? 'reinstated' : 'suspended'}`); log(s === 'Active' ? 'Member reinstated' : 'Member suspended', u.name, s === 'Active' ? 'forest' : 'danger') }}
                  className={`h-10 rounded-xl text-sm font-semibold ${u.status === 'Suspended' ? 'text-forest hover:bg-forest/5' : 'text-danger hover:bg-danger/5'}`}>
                  {u.status === 'Suspended' ? 'Reinstate' : 'Suspend'}</button>
              </div>
            </div>
          </>}
        </SidePanel>}
      </div>

      {modal !== undefined && <MemberModal member={modal} onClose={() => setModal(undefined)} onSave={save} />}
      {messaging && <MessageModal recipients={list.filter((m) => messaging.includes(m.id))} onClose={() => setMessaging(null)}
        onSend={(channel) => { notify(`${channel} sent to ${messaging.length} member${messaging.length > 1 ? 's' : ''}`); log(`${channel} sent`, list.filter((m) => messaging.includes(m.id)).map((m) => m.name).join(', '), 'sand'); setMessaging(null); setChecked([]) }} />}
    </main>
  )
}


function MessageModal({ recipients, onClose, onSend }: { recipients: Member[]; onClose: () => void; onSend: (channel: string) => void }) {
  const [channel, setChannel] = useState<'Email' | 'SMS'>('Email')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')
  const templates: [string, string, string][] = [
    ['Course update', 'Course update from Pine Valley', 'Hi {first}, greens are being aerated Monday — carts will be path-only.'],
    ['Event invite', "You're invited: Autumn Member Classic", 'Hi {first}, registration for the Autumn Member Classic is open. Reply to save your spot.'],
    ['Payment reminder', 'Friendly payment reminder', 'Hi {first}, you have an outstanding balance on your account.'],
  ]
  const valid = body.trim() && (channel === 'SMS' || subject.trim())
  return (
    <ModalShell wide title={`Message ${recipients.length === 1 ? recipients[0].name : `${recipients.length} members`}`} desc={recipients.map((r) => (channel === 'Email' ? r.email : r.phone)).join(', ')} onClose={onClose}
      footer={<><button onClick={onClose} className={btnGhost}>Cancel</button><button disabled={!valid} onClick={() => onSend(channel)} className={btnPrimary}>Send {channel}</button></>}>
      <div className="flex gap-2">
        {(['Email', 'SMS'] as const).map((c) => <button key={c} onClick={() => setChannel(c)} className={`px-4 h-9 rounded-xl text-sm font-semibold border ${channel === c ? 'bg-forest text-white border-forest' : 'border-gray-200 text-gray-600'}`}>{c}</button>)}
      </div>
      <div className="flex flex-wrap gap-2">
        {templates.map(([l, s, b]) => <button key={l} onClick={() => { setSubject(s); setBody(b) }} className="px-3 h-8 rounded-full bg-offwhite border border-gray-100 text-xs font-semibold text-gray-600 hover:border-forest">{l}</button>)}
      </div>
      {channel === 'Email' && <Field label="Subject"><input className={inputCls} value={subject} onChange={(e) => setSubject(e.target.value)} /></Field>}
      <Field label="Message" hint={channel === 'SMS' ? `${body.length}/160 characters` : '{first} is replaced with each member\'s first name'}>
        <textarea rows={4} className={inputCls + ' h-auto py-2.5 resize-none'} value={body} maxLength={channel === 'SMS' ? 160 : undefined} onChange={(e) => setBody(e.target.value)} />
      </Field>
    </ModalShell>
  )
}
