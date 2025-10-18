'use client';

import { useState } from 'react';
import { 
  Home,
  Users,
  User,
  Calendar,
  Bed,
  Wrench,
  CreditCard,
  ChevronLeft,
  ChevronRight,
  LogOut,
  Building,
  FileText,
  ShoppingBag,
  Package,
  BarChart2,
  Settings,
  Gift,
  Star,
  Tag,
  Bell,
  MessageSquare,
  ClipboardList,
  Layers,
  TrendingUp,
  PieChart,
  FileBarChart2,
  Sliders,
  Calendar as CalendarIcon,
  ShoppingCart,
  Zap,
  MapPin,
  ClipboardCheck,
  Clock,
  FileSearch,
  FilePlus,
  Users as UsersIcon,
  UserPlus,
  UserCheck,
  UserCog,
  UserX,
  UserPlus2,
  UserMinus,
  UserCog2,
  UserCheck2,
  UserX2,
  UserPlus2 as UserAdd,
  UserMinus2,
  UserCog as UserSettings,
  UserCheck as UserVerified,
  UserX as UserBlocked,
  UserPlus as UserAdd2,
  UserMinus as UserRemove,
  UserCog2 as UserConfig,
  UserCheck2 as UserApproved,
  UserX2 as UserRejected,
  UserPlus2 as UserInvite,
  UserMinus2 as UserBan,
  UserCog as UserEdit,
  UserCheck as UserActive,
  UserX as UserInactive,
  UserPlus as NewUser,
  UserMinus as DeleteUser,
  UserCog as ManageUser,
  UserCheck as VerifiedUser,
  UserX as BlockedUser,
  UserPlus as AddUser,
  UserMinus as RemoveUser,
  UserCog as UserManagement,
  UserCheck as ActiveUser,
  UserX as InactiveUser,
  Truck,
  Megaphone,
  Mail,
  Globe,
  TrendingDown,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

interface NavItem {
  name: string;
  href: string;
  icon: React.ComponentType<{ className?: string }>;
  modal?: boolean;
  onClick?: (e: React.MouseEvent) => void;
}

interface NavSection {
  name: string;
  icon: React.ComponentType<{ className?: string }>;
  items: NavItem[];
  hidden?: boolean;
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

  // Navigation sections
  const navigationSections: NavSection[] = [
    {
      name: 'Front Desk',
      icon: ClipboardCheck,
      items: [
        { name: 'Dashboard', href: '/dashboard', icon: Home },
        { 
          name: 'Reservations', 
          href: '#', 
          icon: Calendar,
          modal: true,
          onClick: () => window.dispatchEvent(new Event('openReservationModal'))
        },
        { 
          name: 'Check-in/Check-out', 
          href: '#',
          icon: Clock,
          modal: true,
          onClick: () => window.dispatchEvent(new Event('openCheckInOutModal'))
        },
        { name: 'Room Assignment', href: '/frontdesk/room-assignment', icon: MapPin },
      ]
    },
    {
      name: 'Room Management',
      icon: Bed,
      items: [
        { name: 'Rooms', href: '/rooms', icon: Bed },
        { 
          name: 'Housekeeping', 
          href: '#', 
          icon: Wrench, 
          modal: true,
          onClick: handleHousekeepingClick
        },
        { 
          name: 'Maintenance', 
          href: '#',
          icon: Wrench,
          modal: true,
          onClick: () => window.dispatchEvent(new Event('openMaintenanceModal'))
        },
        { name: 'Room Types', href: '/rooms/types', icon: Layers },
      ]
    },
    {
      name: 'Guest Relationship',
      icon: UsersIcon,
      items: [
        { name: 'Guests', href: '/guests', icon: User },
        { 
          name: 'Loyalty Program', 
          href: '#',
          icon: Star,
          modal: true,
          onClick: () => window.dispatchEvent(new Event('openLoyaltyModal'))
        },
        { 
          name: 'Feedback', 
          href: '#',
          icon: MessageSquare,
          modal: true,
          onClick: () => window.dispatchEvent(new Event('openFeedbackModal'))
        },
      ]
    },
    {
      name: 'Inventory',
      icon: Package,
      items: [
        { name: 'Stock', href: '/inventory/stock', icon: Package },
        { name: 'Suppliers', href: '/inventory/suppliers', icon: Truck },
        { name: 'Purchase Orders', href: '/inventory/orders', icon: ShoppingBag },
      ]
    },
    {
      name: 'Events',
      icon: CalendarIcon,
      items: [
        { name: 'Conferences', href: '/events/conferences', icon: Users },
        { name: 'Bookings', href: '/events/bookings', icon: Calendar },
        { name: 'Facilities', href: '/events/facilities', icon: Building },
      ]
    },
    {
      name: 'Billing',
      icon: CreditCard,
      items: [
        { 
          name: 'Invoices', 
          href: '#',
          icon: FileText,
          modal: true,
          onClick: handleBillingClick
        },
        { 
          name: 'Payments', 
          href: '#',
          icon: CreditCard,
          modal: true,
          onClick: () => window.dispatchEvent(new Event('openPaymentsModal'))
        },
        { name: 'Expenses', href: '/billing/expenses', icon: TrendingDown },
      ]
    },
    {
      name: 'Hotel Marketing',
      icon: Zap,
      items: [
        { name: 'Campaigns', href: '/marketing/campaigns', icon: Megaphone },
        { name: 'Promotions', href: '/marketing/promotions', icon: Tag },
        { name: 'Email Marketing', href: '/marketing/email', icon: Mail },
      ]
    },
    {
      name: 'Channel Management',
      icon: Sliders,
      hidden: true,
      items: [
        { name: 'OTA Connections', href: '/channels/ota', icon: Globe },
        { name: 'Rate Management', href: '/channels/rates', icon: Tag },
        { name: 'Availability', href: '/channels/availability', icon: Calendar },
      ]
    },
    {
      name: 'Analytics',
      icon: BarChart2,
      items: [
        { name: 'Dashboard', href: '/analytics/dashboard', icon: PieChart },
        { name: 'Reports', href: '/analytics/reports', icon: FileBarChart2 },
        { name: 'Insights', href: '/analytics/insights', icon: TrendingUp },
      ]
    },
    {
      name: 'Administration',
      icon: Settings,
      items: [
        { name: 'Users', href: '/admin/users', icon: Users },
        { name: 'Roles', href: '/admin/roles', icon: UserCog },
        { name: 'Settings', href: '/admin/settings', icon: Settings },
      ]
    }
  ];

  const renderNavItem = (item: NavItem) => {
    const isActiveItem = isActive(item.href);
    return (
      <li key={item.href} className="px-2 py-1">
        <Link
          href={item.href}
          onClick={item.onClick}
          className={`flex items-center p-3 rounded-lg hover:bg-gray-800 transition-colors ${
            isActiveItem ? 'bg-gray-800 text-white' : 'text-gray-300'
          }`}
          title={!isOpen ? item.name : undefined}
        >
          <item.icon className="w-5 h-5 flex-shrink-0" />
          <span className={`${isOpen ? 'ml-3 opacity-100' : 'w-0 opacity-0 overflow-hidden'} transition-all duration-200 whitespace-nowrap`}>
            {item.name}
          </span>
        </Link>
      </li>
    );
  };

  const renderSection = (section: NavSection) => {
    // Skip hidden sections
    if (section.hidden) return null;
    
    const isSectionExpanded = expandedSections[section.name] ?? true;
    
    return (
      <div key={section.name} className="mb-1">
        <button
          onClick={() => toggleSection(section.name)}
          className={`w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800 transition-colors text-left ${
            isSectionExpanded ? 'text-white' : 'text-gray-300'
          }`}
        >
          <div className="flex items-center">
            <section.icon className="w-5 h-5 flex-shrink-0" />
            {isOpen && <span className="ml-3">{section.name}</span>}
          </div>
          {isOpen && (
            <ChevronRight 
              className={`w-4 h-4 transition-transform ${isSectionExpanded ? 'transform rotate-90' : ''}`} 
            />
          )}
        </button>
        
        <div 
          className={`overflow-hidden transition-all duration-200 ${
            isSectionExpanded ? 'max-h-96' : 'max-h-0'
          }`}
        >
          <ul className={`pl-${isOpen ? '8' : '2'} py-1`}>
            {section.items.map((item) => renderNavItem(item))}
          </ul>
        </div>
      </div>
    );
  };

  return (
    <div 
      className={`fixed inset-y-0 left-0 z-50 flex flex-col bg-gray-900 text-white transition-all duration-300 ease-in-out ${
        isOpen ? 'w-64' : 'w-16'
      }`}
      style={{
        boxShadow: '2px 0 8px rgba(0, 0, 0, 0.1)',
        transitionProperty: 'width',
        transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',
        transitionDuration: '300ms'
      }}
    >
      <div className="h-full flex flex-col">
        <div className="flex items-center justify-between p-4 border-b border-gray-800">
          <div className="flex items-center">
            <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
            </div>
            <h1 className={`ml-3 text-xl font-bold text-white whitespace-nowrap transition-all duration-200 ${
              isOpen ? 'opacity-100 ml-3' : 'opacity-0 w-0 overflow-hidden'
            }`}>
              HMS Pro
            </h1>
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

        <nav className="flex-1 overflow-y-auto py-4 px-2">
          {navigationSections.map((section) => renderSection(section))}
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
