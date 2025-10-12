'use client';

import { motion } from 'framer-motion';
import { LogOut, User } from 'lucide-react';
import { useRouter } from 'next/navigation';

export default function UserDashboard() {
  const router = useRouter();

  const handleLogout = () => {
    sessionStorage.removeItem('authData');
    router.push('/');
  };

  return (
    <div className="min-h-screen bg-gray-900">
      <header className="bg-gray-800 shadow">
        <div className="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
          <h1 className="text-2xl font-bold text-white">User Dashboard</h1>
          <div className="flex items-center space-x-4">
            <button 
              onClick={handleLogout}
              className="flex items-center text-gray-300 hover:text-white transition-colors"
              title="Logout"
            >
              <LogOut className="h-5 w-5 mr-1" />
              <span className="sr-only">Logout</span>
            </button>
          </div>
        </div>
      </header>
      
      <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div className="px-4 py-6 sm:px-0">
          <div className="border-2 border-dashed border-gray-700 rounded-lg p-6 text-center">
            <User className="mx-auto h-12 w-12 text-blue-400 mb-4" />
            <h3 className="text-lg font-medium text-white mb-2">Welcome to User Dashboard</h3>
            <p className="text-gray-400">This is the user dashboard. More features coming soon.</p>
          </div>
        </div>
      </main>
    </div>
  );
}
