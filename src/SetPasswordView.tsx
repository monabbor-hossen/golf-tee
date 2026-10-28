import { useState } from 'react'

export default function SetPasswordView({ onLaunchApp }: { onLaunchApp?: (email: string) => void }) {
  const [tab, setTab] = useState<'email' | 'form' | 'success'>('email')
  const [name] = useState('Sofia Kim')
  const [email] = useState('sofia.k@mail.com')
  const [pwd, setPwd] = useState('')
  const [confirmPwd, setConfirmPwd] = useState('')
  const [show, setShow] = useState(false)
  const [error, setError] = useState('')

  const handleActivate = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    if (pwd.length < 6) {
      setError('Password must be at least 6 characters.')
      return
    }
    if (pwd !== confirmPwd) {
      setError('Passwords do not match. Please verify and re-enter.')
      return
    }
    setTab('success')
  }

  return (
    <div className="w-full max-w-2xl px-4 py-2">
      {/* Simulation Stepper Header */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-5 flex items-center justify-between text-xs font-semibold">
        <div className="flex items-center gap-2">
          <span className="w-6 h-6 rounded-full bg-forest text-white flex items-center justify-center font-bold">1</span>
          <span className={tab === 'email' ? 'text-forest font-bold' : 'text-gray-500'}>Email Received in Inbox</span>
        </div>
        <span className="text-gray-300">→</span>
        <div className="flex items-center gap-2">
          <span className={`w-6 h-6 rounded-full flex items-center justify-center font-bold ${tab === 'form' ? 'bg-forest text-white' : 'bg-gray-100 text-gray-400'}`}>2</span>
          <span className={tab === 'form' ? 'text-forest font-bold' : 'text-gray-500'}>Click URL & Set Password</span>
        </div>
        <span className="text-gray-300">→</span>
        <div className="flex items-center gap-2">
          <span className={`w-6 h-6 rounded-full flex items-center justify-center font-bold ${tab === 'success' ? 'bg-forest text-white' : 'bg-gray-100 text-gray-400'}`}>3</span>
          <span className={tab === 'success' ? 'text-forest font-bold' : 'text-gray-500'}>Log in to App</span>
        </div>
      </div>

      {tab === 'email' && (
        <div className="bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden">
          {/* Email Client Header bar */}
          <div className="bg-gray-100 border-b border-gray-200 px-5 py-3.5 flex items-center justify-between text-xs text-gray-600">
            <div>
              <span className="font-bold text-gray-800">From:</span> Pine Valley Club Administration &lt;noreply@pinevalley.com&gt;<br />
              <span className="font-bold text-gray-800">To:</span> {name} &lt;{email}&gt;<br />
              <span className="font-bold text-gray-800">Subject:</span> Official Invitation: Activate Your Pine Valley Account
            </div>
            <span className="text-[11px] bg-white border border-gray-200 px-2 py-1 rounded-md text-gray-500">Inbox · Today</span>
          </div>

          {/* Email Body */}
          <div className="p-6 sm:p-8 space-y-5">
            <div className="rounded-2xl bg-gradient-to-br from-forest-dark to-forest text-white p-6 text-center">
              <span className="inline-block px-3 py-1 rounded-full bg-white/20 text-[11px] font-bold uppercase tracking-wider mb-2">Member Invitation</span>
              <h2 className="text-2xl font-black">Pine Valley Golf Club</h2>
              <p className="text-xs text-white/80 mt-1">Official Member Access Portal</p>
            </div>

            <div className="space-y-3 text-sm text-gray-700 leading-relaxed">
              <p className="text-base font-bold text-gray-900">Welcome, {name}!</p>
              <p>An official membership account has been registered for you by Pine Valley Club Administration.</p>
              <p>As our club operates on an invite-only basis, public account registration is restricted. Please click the button below to set your security password and activate your mobile app access:</p>
            </div>

            <div className="text-center py-4">
              <button
                onClick={() => setTab('form')}
                className="inline-flex items-center gap-2 px-8 h-13 rounded-xl bg-forest hover:bg-forest-dark text-white font-bold text-base shadow-lg shadow-forest/25 transition active:scale-95"
              >
                Set Password & Activate Account ↗
              </button>
            </div>

            <div className="p-4 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-500 space-y-1">
              <p className="font-semibold text-gray-700">Direct Activation URL:</p>
              <p className="font-mono text-gray-600 break-all">{`${typeof window !== 'undefined' ? window.location.origin : ''}/golf-tee/admin-php/set-password.php?token=8e2ddb15d9b75c00c4671c99`}</p>
            </div>

            <div className="border-t border-gray-100 pt-4 text-xs text-gray-400">
              🔒 Note: You can also choose "Continue with Google" in the mobile app once this email is verified.
            </div>
          </div>
        </div>
      )}

      {tab === 'form' && (
        <div className="bg-white rounded-3xl shadow-xl border border-gray-200 p-6 sm:p-8 space-y-6">
          <div className="text-center">
            <div className="w-14 h-14 mx-auto mb-3 rounded-full bg-gradient-to-br from-forest-dark to-forest text-white border-2 border-sand flex items-center justify-center font-bold text-xl shadow-md shadow-forest/20">
              PV
            </div>
            <h2 className="text-2xl font-extrabold text-gray-900">Set Account Password</h2>
            <p className="text-xs text-gray-500 mt-1">Pine Valley Golf Club · Member Security Portal</p>
          </div>

          <div className="p-4 rounded-2xl bg-forest/5 border border-forest/20 flex items-center gap-3">
            <div className="w-11 h-11 rounded-full bg-sand text-forest font-bold flex items-center justify-center">SK</div>
            <div>
              <p className="text-sm font-bold text-gray-900">{name}</p>
              <p className="text-xs text-gray-500">{email} · Standard Membership</p>
            </div>
          </div>

          <form onSubmit={handleActivate} className="space-y-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">New Password</label>
              <div className="relative flex items-center">
                <input
                  type={show ? 'text' : 'password'}
                  value={pwd}
                  onChange={(e) => setPwd(e.target.value)}
                  placeholder="At least 6 characters"
                  className="w-full h-12 pl-4 pr-12 rounded-xl border border-gray-200 text-sm outline-none focus:border-forest"
                  required
                />
                <button type="button" onClick={() => setShow(!show)} className="absolute right-3.5 text-xs font-semibold text-gray-400 hover:text-gray-600">
                  {show ? 'Hide' : 'Show'}
                </button>
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Confirm Password</label>
              <input
                type={show ? 'text' : 'password'}
                value={confirmPwd}
                onChange={(e) => setConfirmPwd(e.target.value)}
                placeholder="Re-enter password"
                className="w-full h-12 px-4 rounded-xl border border-gray-200 text-sm outline-none focus:border-forest"
                required
              />
            </div>

            {error && (
              <div className="p-3 bg-danger/10 border border-danger/20 rounded-xl text-danger text-xs font-medium">
                {error}
              </div>
            )}

            <div className="pt-2">
              <button
                type="submit"
                className="w-full h-12 rounded-xl bg-forest hover:bg-forest-dark text-white font-bold text-sm shadow-md shadow-forest/25 transition active:scale-[0.99]"
              >
                Save Password & Activate Account
              </button>
            </div>
          </form>

          <p className="text-center text-xs text-gray-400">
            Need help? Contact the club front desk at (555) 301-2200.
          </p>
        </div>
      )}

      {tab === 'success' && (
        <div className="bg-white rounded-3xl shadow-xl border border-gray-200 p-8 text-center space-y-6">
          <div className="w-16 h-16 mx-auto rounded-full bg-forest/10 text-forest flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={3} className="w-8 h-8">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>

          <div>
            <h2 className="text-2xl font-black text-gray-900">Account Activated!</h2>
            <p className="text-sm text-gray-500 mt-1">Your password has been set. You can now access the Pine Valley Golf App.</p>
          </div>

          <div className="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-left text-xs text-gray-600 space-y-2">
            <p className="font-bold text-gray-900">Your Login Credentials:</p>
            <p>• <strong>Email:</strong> {email}</p>
            <p>• <strong>Password:</strong> •••••••• (configured)</p>
            <p>• <strong>Google Login:</strong> Permitted for this verified email address</p>
          </div>

          <div className="flex gap-3">
            <button
              onClick={() => onLaunchApp?.(email)}
              className="flex-1 h-12 rounded-xl bg-forest hover:bg-forest-dark text-white font-bold text-sm shadow-lg shadow-forest/20 transition active:scale-[0.99]"
            >
              Open Mobile App & Log In 🚀
            </button>
            <button
              onClick={() => setTab('email')}
              className="h-12 px-5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50"
            >
              Restart Demo
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
