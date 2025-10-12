'use client';

import { useState } from 'react';
import { 
  Home,
  Users,
  User,
  Calendar,
  Gift,
  Bed,
  Package,
  Tag,
  LayoutGrid,
  BarChart2,
  Settings,
  Bell,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  LogOut,
  Wrench,
  CreditCard,
} from 'lucide-react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

interface NavItem {
  name: string;
  href: string;
  icon: any;
  modal?: boolean;
  children?: NavItem[];
  onClick?: (e: React.MouseEvent) => void;
}

interface SidebarProps {
  isOpen: boolean;
  onClose: () => void;
  userType?: 'staff' | 'user';
}

export default function Sidebar({ isOpen, onClose, userType = 'staff' }: SidebarProps) {
  const pathname = usePathname();
  const [expandedSections, setExpandedSections] = useState<Record<string, boolean>>({
    frontoffice: true,
    guestservices: true,
    operations: true,
  });

  const handleLogout = () => {
    // TODO: Implement logout logic
    console.log('Logout clicked');
  };

  const handleBillingClick = (e: React.MouseEvent) => {
    e.preventDefault();
    window.dispatchEvent(new Event('openBillingModal'));
  };

  const handleHousekeepingClick = (e: React.MouseEvent) => {
    e.preventDefault();
    window.dispatchEvent(new Event('openHousekeepingModal'));
  };

  const toggleSection = (section: string) => (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const isActive = (path: string) => {
    return pathname === path || (pathname?.startsWith(`${path}/`) || false);
  };

  const navItems: NavItem[] = [
    { name: 'Dashboard', href: '/dashboard', icon: Home },
    { name: 'Guests', href: '/guests', icon: User },
    { name: 'Bookings', href: '/bookings', icon: Calendar },
    { name: 'Rooms', href: '/rooms', icon: Bed },
    { 
      name: 'Housekeeping', 
      href: '#', 
      icon: Wrench, 
      modal: true,
      onClick: handleHousekeepingClick
    },
    { 
      name: 'Billing', 
      href: '#', 
      icon: CreditCard, 
      modal: true,
      onClick: handleBillingClick
    },
  ];

  const renderNavItem = (item: NavItem, index: number) => (
    <li key={`${item.href}-${index}`}>
      <Link
        href={item.href}
        onClick={item.onClick}
        className={`flex items-center p-3 rounded-lg hover:bg-gray-800 transition-colors ${
          isActive(item.href) ? 'bg-gray-800 text-white' : 'text-gray-300'
        }`}
      >
        <item.icon className="w-5 h-5" />
        {isOpen && <span className="ml-3">{item.name}</span>}
      </Link>
    </li>
  );

  return (
    <div 
      className={`fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-gray-900 text-white transition-all duration-300 ease-in-out transform ${
        isOpen ? 'translate-x-0' : '-translate-x-full'
      }`}
      style={{
        boxShadow: '2px 0 8px rgba(0, 0, 0, 0.1)',
        transitionProperty: 'transform',
        transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',
        transitionDuration: '300ms'
      }}
    >
      <div className="h-full flex flex-col">
        <div className="flex items-center justify-between p-4 border-b border-gray-800">
          <div className="flex items-center">
            <div className={`${isOpen ? 'w-8 h-8' : 'w-8 h-8 mx-auto'} bg-blue-600 rounded-lg flex items-center justify-center`}>
              <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
            </div>
            {isOpen && <h1 className="ml-3 text-xl font-bold text-white whitespace-nowrap">HMS Pro</h1>}
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            aria-label={isOpen ? 'Collapse sidebar' : 'Expand sidebar'}
          >
            {isOpen ? (
              <ChevronLeft className="w-5 h-5" />
            ) : (
              <ChevronRight className="w-5 h-5" />
            )}
          </button>
        </div>

        <nav className="flex-1 overflow-y-auto py-4">
          <ul className="space-y-1 px-2">
            {navItems.map((item, index) => renderNavItem(item, index))}
          </ul>
        </nav>

        <div className="p-4 border-t border-gray-800">
          <button
            onClick={handleLogout}
            className="flex items-center w-full px-4 py-2 text-left text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition-colors"
          >
            <LogOut className="w-5 h-5 flex-shrink-0" />
            {isOpen && <span className="ml-3">Logout</span>}
          </button>
        </div>
      </div>
    </div>
  );
}
