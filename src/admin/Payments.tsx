import { useState } from 'react'
import {
  type Txn, type TxnType, Avatar, Empty, Field, Icon, ModalShell, PageHeader, Pill, Segmented, SidePanel, Stat,
  btnGhost, btnPrimary, btnSecondary, downloadCSV, fmtDate, fmtMin, inputCls,
} from './shared'
import { useAdmin } from './store'

const TYPES: TxnType[] = ['Green fee', 'Pro shop', 'Food & bev', 'Event', 'Membership']
const typeColor: Record<TxnType, string> = { 'Green fee': '#1E5631', 'Pro shop': '#D4C4A8', 'Food & bev': '#F4A261', Event: '#E8C766', Membership: '#6b8f71' }
const statusPill: Record<Txn['status'], string> = { Paid: 'bg-forest/10 text-forest', Pending: 'bg-wait/20 text-[#a85d1f]', Refunded: 'bg-danger/10 text-danger' }

function RecordModal({ onClose, onSave }: { onClose: () => void; onSave: (t: Omit<Txn, 'id'>) => void }) {
  const { members, currSym } = useAdmin()
  const [name, setName] = useState('')
  const [type, setType] = useState<TxnType>('Pro shop')
  const [desc, setDesc] = useState('')
  const [amount, setAmount] = useState('')
  const [method, setMethod] = useState('Visa')
  const amt = parseFloat(amount)
  const valid = name.trim() && desc.trim() && amt > 0
  return (
    <ModalShell title="Record a payment" desc="Log a counter sale, membership fee or event entry." onClose={onClose}
      footer={<><button onClick={onClose} className={btnGhost}>Cancel</button><button disabled={!valid} onClick={() => onSave({ offset: 0, min: 660, name: name.trim(), desc: desc.trim(), type, method, amount: Math.round(amt), status: 'Paid' })} className={btnPrimary}>Record {currSym}{valid ? Math.round(amt) : 0}</button></>}>
      <Field label="Customer"><input autoFocus list="pay-names" className={inputCls} value={name} onChange={(e) => setName(e.target.value)} placeholder="Member or guest" />
        <datalist id="pay-names">{members.map((m) => <option key={m.id} value={m.name} />)}</datalist></Field>
      <div className="grid grid-cols-2 gap-4">
        <Field label="Category"><select className={inputCls} value={type} onChange={(e) => setType(e.target.value as TxnType)}>{TYPES.map((t) => <option key={t}>{t}</option>)}</select></Field>
        <Field label="Method"><select className={inputCls} value={method} onChange={(e) => setMethod(e.target.value)}>{['Visa', 'Mastercard', 'Apple Pay', 'Cash', 'Member account'].map((m) => <option key={m}>{m}</option>)}</select></Field>
      </div>
      <Field label="Description"><input className={inputCls} value={desc} onChange={(e) => setDesc(e.target.value)} placeholder="e.g. Golf glove, size M" /></Field>
      <Field label="Amount"><div className="relative"><span className="absolute left-3 top-2.5 text-gray-400 text-sm">{currSym}</span><input type="number" min="0" className={inputCls + ' pl-7'} value={amount} onChange={(e) => setAmount(e.target.value)} placeholder="0" /></div></Field>
    </ModalShell>
  )
}

export default function PaymentsPage() {
  const { txns, setTxns, addTxn, focus, members, go, toast, log, currSym } = useAdmin()
  const [status, setStatus] = useState<'All' | Txn['status']>('All')
  const [type, setType] = useState<'All' | TxnType>('All')
  const [range, setRange] = useState<'today' | 'yesterday' | 'all'>('all')
  const [q, setQ] = useState('')
  const [selId, setSelId] = useState<string | null>(focus?.txnId ?? null)
  const [confirmRefund, setConfirmRefund] = useState(false)
  const [recording, setRecording] = useState(false)

  const inRange = (t: Txn) => range === 'all' || (range === 'today' ? t.offset === 0 : t.offset === -1)
  const base = txns.filter((t) => inRange(t) && (type === 'All' || t.type === type) && (t.name + t.desc + t.id).toLowerCase().includes(q.toLowerCase()))
  const rows = base.filter((t) => status === 'All' || t.status === status)
  const sum = (l: Txn[]) => l.reduce((a, t) => a + t.amount, 0)
  const paid = base.filter((t) => t.status === 'Paid'), pending = base.filter((t) => t.status === 'Pending'), refunded = base.filter((t) => t.status === 'Refunded')
  const counts = { All: base.length, Paid: paid.length, Pending: pending.length, Refunded: refunded.length }
  const sel = txns.find((t) => t.id === selId)
  const byType = TYPES.map((t) => [t, sum(paid.filter((x) => x.type === t))] as const).filter(([, v]) => v > 0)
  const byMethod = Object.entries(paid.reduce<Record<string, number>>((a, t) => ({ ...a, [t.method]: (a[t.method] ?? 0) + t.amount }), {})).sort((a, b) => b[1] - a[1])
  const paidTotal = sum(paid)

  const patch = (id: string, p: Partial<Txn>) => setTxns((l) => l.map((t) => (t.id === id ? { ...t, ...p } : t)))
  const pick = (id: string | null) => { setSelId(id); setConfirmRefund(false) }

  return (
    <main className="flex-1 min-w-0 p-4 sm:p-6 lg:overflow-hidden flex flex-col">
      <PageHeader title="Payments" subtitle="Green fees, pro shop, food & beverage, events and memberships">
        <button onClick={() => downloadCSV('payments.csv', [['ID', 'Date', 'Time', 'Customer', 'Description', 'Category', 'Method', 'Amount', 'Status'], ...rows.map((t) => [t.id, fmtDate(t.offset), fmtMin(t.min), t.name, t.desc, t.type, t.method, t.amount, t.status])])} className={btnSecondary + ' flex items-center gap-2'}><Icon name="download" className="w-4 h-4" />Export</button>
        <button onClick={() => setRecording(true)} className={btnPrimary + ' flex-1 sm:flex-none'}>+ Record payment</button>
      </PageHeader>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Stat highlight label="Collected" value={`${currSym}${paidTotal.toLocaleString()}`} sub={`${paid.length} transactions`} />
        <Stat label="Pending" value={`${currSym}${sum(pending).toLocaleString()}`} sub={`${pending.length} awaiting payment`} tone={pending.length ? 'wait' : 'gray'} />
        <Stat label="Refunded" value={`${currSym}${sum(refunded).toLocaleString()}`} sub={`${refunded.length} refunds`} tone={refunded.length ? 'danger' : 'gray'} />
        <Stat label="Average ticket" value={`${currSym}${paid.length ? Math.round(paidTotal / paid.length) : 0}`} sub="Paid transactions" />
      </div>

      <div className="flex flex-wrap items-center gap-3 mb-3">
        <div className="relative w-full sm:flex-1 sm:max-w-xs">
          <Icon name="search" className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
          <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search customer, item or ID" className={inputCls + ' pl-9'} />
        </div>
        <Segmented value={status} onChange={setStatus} options={(['All', 'Paid', 'Pending', 'Refunded'] as const).map((s) => [s, <>{s} <span className="opacity-60 text-[11px]">{counts[s]}</span></>])} />
        <select value={type} onChange={(e) => setType(e.target.value as 'All' | TxnType)} className={inputCls + ' w-full sm:w-40'}><option value="All">All categories</option>{TYPES.map((t) => <option key={t}>{t}</option>)}</select>
        <div className="xl:ml-auto"><Segmented value={range} onChange={setRange} options={[['today', 'Today'], ['yesterday', 'Yesterday'], ['all', 'Last 3 days']]} /></div>
      </div>

      <div className="flex flex-col lg:flex-row gap-4 lg:flex-1 lg:min-h-0">
        <div className="max-h-[70vh] lg:max-h-none lg:flex-1 bg-white rounded-2xl border border-gray-100 overflow-auto">
          {rows.length === 0 ? <Empty icon="card" title="No transactions">Try a different filter or search.</Empty> : (
            <table className="w-full text-sm">
              <thead className="sticky top-0 z-10 bg-white text-xs uppercase text-gray-500 text-left border-b border-gray-100">
                <tr><th className="p-3 pl-5 hidden sm:table-cell">Transaction</th><th className="p-3 pl-4 sm:pl-3">Customer</th><th className="p-3 hidden md:table-cell">Category</th><th className="p-3 hidden xl:table-cell">Method</th><th className="p-3 text-right">Amount</th><th className="p-3 pr-4 sm:pr-5">Status</th></tr>
              </thead>
              <tbody>
                {rows.map((t) => (
                  <tr key={t.id} onClick={() => pick(t.id)} className={`border-b border-gray-50 cursor-pointer ${selId === t.id ? 'bg-forest/5' : 'hover:bg-gray-50'}`}>
                    <td className="p-3 pl-5 hidden sm:table-cell"><p className="font-semibold font-mono text-xs">{t.id}</p><p className="text-xs text-gray-400">{fmtDate(t.offset)} · {fmtMin(t.min)}</p></td>
                    <td className="p-3 pl-4 sm:pl-3 max-w-0 w-full sm:w-auto sm:max-w-none"><p className="font-semibold truncate">{t.name}</p><p className="text-xs text-gray-400 truncate sm:max-w-[220px]">{t.desc}</p><p className="sm:hidden text-[11px] text-gray-400">{fmtDate(t.offset)} · {fmtMin(t.min)}</p></td>
                    <td className="p-3 hidden md:table-cell"><span className="flex items-center gap-1.5 text-gray-600"><i className="w-2 h-2 rounded-full" style={{ background: typeColor[t.type] }} />{t.type}</span></td>
                    <td className="p-3 text-gray-500 hidden xl:table-cell">{t.method}</td>
                    <td className={`p-3 text-right font-bold ${t.status === 'Refunded' ? 'text-danger' : ''}`}>{t.status === 'Refunded' ? '−' : ''}{currSym}{t.amount.toLocaleString()}</td>
                    <td className="p-3 pr-4 sm:pr-5"><Pill cls={statusPill[t.status]}>{t.status}</Pill></td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <SidePanel sheet={!!sel} onClose={() => pick(null)} className="lg:w-72">
          {sel ? (() => {
            const member = members.find((m) => m.name === sel.name)
            return <>
              <div className="p-5 border-b border-dashed border-gray-200">
                <div className="flex justify-between items-start">
                  <div><p className="text-xs font-mono text-gray-400">{sel.id}</p><p className={`text-3xl font-extrabold mt-1 ${sel.status === 'Refunded' ? 'text-danger' : ''}`}>{sel.status === 'Refunded' ? '−' : ''}{currSym}{sel.amount.toLocaleString()}</p></div>
                  <button onClick={() => pick(null)} aria-label="Close" className="w-8 h-8 rounded-lg text-gray-400 hover:bg-gray-100 flex items-center justify-center"><Icon name="x" className="w-4 h-4" /></button>
                </div>
                <div className="mt-2"><Pill cls={statusPill[sel.status]}>{sel.status}</Pill></div>
              </div>
              <div className="flex-1 overflow-auto p-5 space-y-4 text-sm">
                <button disabled={!member} onClick={() => member && go('Users', { memberId: member.id })} className="flex items-center gap-3 text-left group disabled:cursor-default">
                  <Avatar name={sel.name} /><div><p className={`font-bold ${member ? 'group-hover:underline' : ''}`}>{sel.name}</p><p className="text-xs text-gray-500">{member ? `${member.tier} member` : 'Guest'}</p></div>
                </button>
                <dl className="space-y-2">
                  {[['Item', sel.desc], ['Category', sel.type], ['Method', sel.method], ['Date', `${fmtDate(sel.offset)} · ${fmtMin(sel.min)}`]].map(([k, v]) => (
                    <div key={k} className="flex justify-between gap-3"><dt className="text-gray-500 shrink-0">{k}</dt><dd className="font-medium text-right">{v}</dd></div>
                  ))}
                </dl>
              </div>
              <div className="p-4 border-t border-gray-100 space-y-2">
                {sel.status === 'Pending' && <button onClick={() => { patch(sel.id, { status: 'Paid' }); toast(`${sel.id} marked as paid`); log('Payment received', `${currSym}${sel.amount} · ${sel.name}`, 'forest') }} className={btnPrimary + ' w-full'}>Mark as paid</button>}
                <button onClick={() => toast(`Receipt emailed to ${member?.email ?? sel.name}`)} className={btnSecondary + ' w-full flex items-center justify-center gap-2'}><Icon name="mail" className="w-4 h-4" />Email receipt</button>
                {sel.status === 'Paid' && (confirmRefund ? (
                  <div className="rounded-xl bg-danger/5 border border-danger/20 p-3 space-y-2">
                    <p className="text-sm font-semibold text-danger">Refund {currSym}{sel.amount} to {sel.method}?</p>
                    <div className="grid grid-cols-2 gap-2">
                      <button onClick={() => setConfirmRefund(false)} className="h-9 rounded-lg border border-gray-200 bg-white text-sm font-semibold">Keep</button>
                      <button onClick={() => { patch(sel.id, { status: 'Refunded' }); setConfirmRefund(false); toast(`Refunded ${currSym}${sel.amount}`); log('Refund issued', `${currSym}${sel.amount} · ${sel.name}`, 'danger') }} className="h-9 rounded-lg bg-danger text-white text-sm font-semibold">Refund</button>
                    </div>
                  </div>
                ) : <button onClick={() => setConfirmRefund(true)} className="w-full h-10 rounded-xl text-danger text-sm font-semibold hover:bg-danger/5 flex items-center justify-center gap-2"><Icon name="refund" className="w-4 h-4" />Issue refund</button>)}
              </div>
            </>
          })() : (
            <div className="p-5 overflow-auto">
              <h2 className="font-bold">Revenue breakdown</h2>
              <p className="text-xs text-gray-500 mb-4">Paid transactions in this view</p>
              <div className="h-3 rounded-full overflow-hidden flex mb-3">{byType.map(([t, v]) => <div key={t} style={{ flex: v, background: typeColor[t] }} />)}</div>
              <div className="space-y-2 mb-6">
                {byType.map(([t, v]) => (
                  <button key={t} onClick={() => setType(t)} className="w-full flex items-center text-sm hover:bg-gray-50 rounded-lg px-1 py-0.5">
                    <i className="w-2.5 h-2.5 rounded-sm mr-2" style={{ background: typeColor[t] }} /><span className="flex-1 text-left text-gray-600">{t}</span>
                    <span className="font-semibold">{currSym}{v.toLocaleString()}</span><span className="w-10 text-right text-xs text-gray-400">{Math.round((v / Math.max(1, paidTotal)) * 100)}%</span>
                  </button>
                ))}
              </div>
              <h3 className="text-xs font-bold uppercase text-gray-400 mb-2">By payment method</h3>
              {byMethod.map(([m, v]) => (
                <div key={m} className="mb-2">
                  <div className="flex justify-between text-sm"><span className="text-gray-600">{m}</span><span className="font-semibold">{currSym}{v.toLocaleString()}</span></div>
                  <div className="h-1.5 mt-1 rounded-full bg-gray-100"><div className="h-full rounded-full bg-forest" style={{ width: `${(v / Math.max(1, paidTotal)) * 100}%` }} /></div>
                </div>
              ))}
            </div>
          )}
        </SidePanel>
      </div>

      {recording && <RecordModal onClose={() => setRecording(false)} onSave={(t) => {
        const id = addTxn(t); setRecording(false); pick(id); toast(`Recorded ${currSym}${t.amount} · ${t.type}`); log('Payment received', `${currSym}${t.amount} · ${t.name} · ${t.type}`, 'forest')
      }} />}
    </main>
  )
}
