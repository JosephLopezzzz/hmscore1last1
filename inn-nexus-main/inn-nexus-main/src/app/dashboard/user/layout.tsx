'use client';

import { useState } from 'react';
import Sidebar from '@/components/layout/sidebar';

export default function UserLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  return (
    <div className="flex h-screen bg-gray-50">
      <Sidebar 
        isOpen={isSidebarOpen} 
        onToggle={() => setIsSidebarOpen(!isSidebarOpen)} 
        userType="user" 
      />
      
      <div className="flex-1 flex flex-col overflow-hidden">
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
          <div className={`transition-all duration-300 ${isSidebarOpen ? 'ml-64' : 'ml-20'} p-6`}>
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}
