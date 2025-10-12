'use client';

import { useState, useEffect } from 'react';
import { usePathname } from 'next/navigation';
import Sidebar from '@/components/layout/sidebar';
import { motion, AnimatePresence } from 'framer-motion';
import BillingModal from '@/components/modals/BillingModal';
import HousekeepingModal from '@/components/modals/HousekeepingModal';

export default function StaffLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [isMounted, setIsMounted] = useState(false);
  const [showBillingModal, setShowBillingModal] = useState(false);
  const [showHousekeepingModal, setShowHousekeepingModal] = useState(false);

  // Add a small delay to prevent hydration mismatch
  useEffect(() => {
    setIsMounted(true);
  }, []);

  // Listen for custom events to open modals
  useEffect(() => {
    const handleBillingClick = () => setShowBillingModal(true);
    const handleHousekeepingClick = () => setShowHousekeepingModal(true);

    window.addEventListener('openBillingModal', handleBillingClick);
    window.addEventListener('openHousekeepingModal', handleHousekeepingClick);

    return () => {
      window.removeEventListener('openBillingModal', handleBillingClick);
      window.removeEventListener('openHousekeepingModal', handleHousekeepingClick);
    };
  }, []);

  if (!isMounted) {
    return null;
  }

  return (
    <div className="flex min-h-screen bg-gray-900 text-gray-100">
      <Sidebar 
        isOpen={isSidebarOpen} 
        onClose={() => setIsSidebarOpen(!isSidebarOpen)} 
        userType="staff" 
      />
      
      <div className="flex-1 flex flex-col overflow-hidden">
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-900">
          <div 
            className={`transition-all duration-300 ${
              isSidebarOpen ? 'md:ml-64' : 'md:ml-16'
            } p-4 md:p-6`}
          >
            <AnimatePresence mode="wait">
              <motion.div
                key={pathname}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                transition={{ duration: 0.2 }}
              >
                <div className="bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-700">
                  {children}
                </div>
              </motion.div>
            </AnimatePresence>
          </div>
        </main>
      </div>

      {/* Modals */}
      <BillingModal 
        isOpen={showBillingModal} 
        onClose={() => setShowBillingModal(false)} 
      />
      
      <HousekeepingModal 
        isOpen={showHousekeepingModal} 
        onClose={() => setShowHousekeepingModal(false)} 
      />
    </div>
  );
}
