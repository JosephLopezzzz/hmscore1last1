'use client';

import { usePathname } from 'next/navigation';
import { Home, Bed, Calendar, Users, CreditCard, Wrench, BarChart2, Settings } from 'lucide-react';

const moduleIcons: Record<string, any> = {
  'reservation': Calendar,
  'frontdesk': Users,
  'room-management': Bed,
  'guest-relationship': Users,
  'billing': CreditCard,
  'housekeeping': Wrench,
  'events': Calendar,
  'analytics': BarChart2,
  'settings': Settings,
  'user': Users,
  'staff': Users,
};

export default function PlaceholderPage() {
  const pathname = usePathname();
  const moduleName = pathname.split('/').pop() || 'dashboard';
  const displayName = moduleName.split('-').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
  
  const Icon = moduleIcons[moduleName] || Home;

  return (
    <div className="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <div className="text-center">
        <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-indigo-100 mb-6">
          <Icon className="h-10 w-10 text-indigo-600" />
        </div>
        <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
          {displayName} Module
        </h2>
        <p className="mt-4 text-lg leading-6 text-gray-500">
          This is a placeholder for the {displayName} module. Content coming soon.
        </p>
        <div className="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900">
              Module Features
            </h3>
            <div className="mt-5">
              <ul className="list-disc pl-5 space-y-2 text-gray-500">
                <li>Feature 1 description will go here</li>
                <li>Feature 2 description will go here</li>
                <li>Feature 3 description will go here</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
