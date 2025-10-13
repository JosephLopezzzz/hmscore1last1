'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { LogOut, Bed, Users, CreditCard, PlusCircle } from 'lucide-react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import type { ComponentType, SVGProps } from 'react';

export default function StaffDashboard() {
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);
  
  // Define types
  interface Booking {
    id: number;
    guest: string;
    room: string;
    checkIn: string;
    checkOut: string;
    status: string;
  }

  const handleNavigation = (path: string) => {
    setIsLoading(true);
    // Simulate SPA navigation
    setTimeout(() => {
      router.push(path);
      setIsLoading(false);
    }, 150);
  };

  const handleLogout = () => {
    sessionStorage.removeItem('authData');
    router.push('/');
  };


  // Define stats type
  interface StatItem {
    name: string;
    value: string;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    change: string;
    changeType: 'positive' | 'negative';
    href: string;
  }

  // Mock data
  const stats = [
    { 
      name: 'Check-ins Today', 
      value: '12', 
      icon: Users, 
      change: '+2', 
      changeType: 'positive' as const,
      href: '/dashboard/check-ins'
    },
    { 
      name: 'Available Rooms', 
      value: '24', 
      icon: Bed, 
      change: '-3', 
      changeType: 'negative',
      href: '/dashboard/rooms'
    },
    { 
      name: 'Guests In-House', 
      value: '48', 
      icon: Users, 
      change: '+8', 
      changeType: 'positive',
      href: '/dashboard/guests'
    },
    { 
      name: 'Pending Payments', 
      value: '6', 
      icon: CreditCard, 
      change: '+2', 
      changeType: 'negative',
      href: '/dashboard/payments'
    },
  ];

  const recentBookings: Booking[] = [
    { id: 1, guest: 'John Doe', room: 'Deluxe 201', checkIn: '2023-06-10', checkOut: '2023-06-15', status: 'Checked In' },
    { id: 2, guest: 'Jane Smith', room: 'Suite 305', checkIn: '2023-06-12', checkOut: '2023-06-14', status: 'Upcoming' },
  ];

  return (
    <div className="min-h-screen bg-gray-900 text-gray-100">
      {isLoading && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500" />
        </div>
      )}
      <header className="bg-gray-800 shadow-lg border-b border-gray-700">
        <div className="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
          <h1 className="text-2xl font-bold text-white">Staff Dashboard</h1>
          <div className="flex items-center space-x-4">
            <button 
              onClick={handleLogout}
              className="flex items-center text-gray-300 hover:text-white transition-colors p-2 rounded-lg hover:bg-gray-700"
              title="Logout"
            >
              <LogOut className="h-5 w-5" />
              <span className="sr-only">Logout</span>
            </button>
          </div>
        </div>
      </header>
      
      <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {stats.map((stat) => (
            <motion.div
              key={stat.name}
              className="bg-gray-800 overflow-hidden shadow-lg rounded-xl border border-gray-700 hover:border-blue-500 transition-colors cursor-pointer"
              whileHover={{ y: -2 }}
              onClick={() => handleNavigation(stat.href)}
            >
              <div className="p-6">
                <div className="flex items-center">
                  <div className="flex-shrink-0 bg-blue-600 rounded-lg p-3">
                    <stat.icon className="h-6 w-6 text-white" />
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dt className="text-sm font-medium text-gray-300 truncate">
                      {stat.name}
                    </dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-white">
                        {stat.value}
                      </div>
                      <div
                        className={`ml-2 flex items-baseline text-sm font-semibold ${
                          stat.changeType === 'positive' ? 'text-green-400' : 'text-red-400'
                        }`}
                      >
                        {stat.change}
                      </div>
                    </dd>
                  </div>
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Quick Actions */}
        <div className="bg-gray-800 shadow-lg rounded-xl border border-gray-700 mb-8">
          <div className="px-6 py-5">
            <h3 className="text-lg font-medium text-white">Quick Actions</h3>
            <div className="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
              <Link
                href="/dashboard/reservation"
                className="group flex flex-col items-center justify-center rounded-xl border border-gray-700 bg-gray-800 p-4 text-center transition-colors hover:border-blue-500 hover:bg-gray-700"
              >
                <PlusCircle className="h-8 w-8 text-blue-400 group-hover:text-blue-300" />
                <span className="mt-2 block text-sm font-medium text-gray-200 group-hover:text-white">New Reservation</span>
              </Link>
              <Link
                href="/dashboard/frontdesk/check-in"
                className="group flex flex-col items-center justify-center rounded-xl border border-gray-700 bg-gray-800 p-4 text-center transition-colors hover:border-green-500 hover:bg-gray-700"
              >
                <Users className="h-8 w-8 text-green-400 group-hover:text-green-300" />
                <span className="mt-2 block text-sm font-medium text-gray-200 group-hover:text-white">Check-in Guest</span>
              </Link>
              <Link
                href="/dashboard/room-management"
                className="group flex flex-col items-center justify-center rounded-xl border border-gray-700 bg-gray-800 p-4 text-center transition-colors hover:border-purple-500 hover:bg-gray-700"
              >
                <Bed className="h-8 w-8 text-purple-400 group-hover:text-purple-300" />
                <span className="mt-2 block text-sm font-medium text-gray-200 group-hover:text-white">Manage Rooms</span>
              </Link>
              <Link
                href="/dashboard/guest-relationship"
                className="group flex flex-col items-center justify-center rounded-xl border border-gray-700 bg-gray-800 p-4 text-center transition-colors hover:border-pink-500 hover:bg-gray-700"
              >
                <Users className="h-8 w-8 text-pink-400 group-hover:text-pink-300" />
                <span className="mt-2 block text-sm font-medium text-gray-200 group-hover:text-white">Guest Services</span>
              </Link>
            </div>
          </div>
        </div>

        {/* Recent Bookings */}
        <div className="bg-gray-800 shadow-lg rounded-xl border border-gray-700 overflow-hidden">
          <div className="px-6 py-5 border-b border-gray-700 flex justify-between items-center">
            <h3 className="text-lg font-medium text-white">Recent Bookings</h3>
            <Link
              href="/dashboard/reservations"
              className="text-sm font-medium text-blue-400 hover:text-blue-300 transition-colors"
            >
              View all bookings →
            </Link>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-700">
              <thead className="bg-gray-800">
                <tr>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                    Guest
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                    Room
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                    Dates
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                    Status
                  </th>
                  <th scope="col" className="relative px-6 py-3">
                    <span className="sr-only">Action</span>
                  </th>
                </tr>
              </thead>
              <tbody className="bg-gray-800 divide-y divide-gray-700">
                {recentBookings.map((booking: Booking) => (
                  <tr key={booking.id} className="hover:bg-gray-700/50 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                      {booking.guest}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                      {booking.room}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                      {booking.checkIn} to {booking.checkOut}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
                          booking.status === 'Checked In'
                            ? 'bg-green-900/30 text-green-300'
                            : booking.status === 'Upcoming'
                            ? 'bg-blue-900/30 text-blue-300'
                            : 'bg-gray-700 text-gray-300'
                        }`}
                      >
                        {booking.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <Link
                        href={`/dashboard/reservation/${booking.id}`}
                        className="text-blue-400 hover:text-blue-300 transition-colors"
                      >
                        View
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
        {/* Footer with view all link */}
        <div className="px-6 py-4 border-t border-gray-700 bg-gray-800 text-right">
          <Link
            href="/dashboard/reservations"
            className="text-sm font-medium text-blue-400 hover:text-blue-300 transition-colors inline-flex items-center"
          >
            View all bookings
            <span className="ml-1" aria-hidden="true">→</span>
          </Link>
        </div>
      </div>
    </div>
  );
}
