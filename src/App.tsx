import { useState } from 'react'
import AdminApp from './admin/AdminApp'
import MobileApp from './mobile/MobileApp'
import SetPasswordView from './SetPasswordView'

export default function App() {
  const [view, setView] = useState<'mobile' | 'admin' | 'set-password'>('mobile')

  return (
    <div className="min-h-screen bg-[#e9ece6] pt-4 lg:py-8 lg:px-6 flex flex-col items-center gap-4 lg:gap-8">
      <div className="p-1 bg-white rounded-xl shadow-sm flex flex-wrap gap-1 justify-center">
        {[
          ['mobile', 'Mobile App'],
          ['admin', 'Admin Dashboard'],
          ['set-password', 'Email Link & Password Setup']
        ].map(([v, label]) => (
          <button
            key={v}
            onClick={() => setView(v as any)}
            className={`px-4 sm:px-5 h-9 rounded-lg text-sm font-semibold transition ${
              view === v ? 'bg-forest text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {view === 'mobile' ? (
        <MobileApp />
      ) : view === 'admin' ? (
        <AdminApp />
      ) : (
        <SetPasswordView onLaunchApp={() => setView('mobile')} />
      )}
    </div>
  )
}
