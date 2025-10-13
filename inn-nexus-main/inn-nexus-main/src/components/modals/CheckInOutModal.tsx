'use client';

import { useState } from 'react';
import Modal from '@/components/ui/Modal';
import { X, User, CreditCard, CheckCircle, Clock, Search, ChevronDown, MoreVertical } from 'lucide-react';

import { CheckInOutModalProps } from './types';

// Export the component as default to match the import in index.ts
export default function CheckInOutModal({ isOpen, onClose }: CheckInOutModalProps) {
  const [activeTab, setActiveTab] = useState<'checkin' | 'checkout'>('checkin');
  
  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl">
      <div className="flex flex-col h-[80vh]">
        <div className="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
          <h2 className="text-xl font-semibold">
            {activeTab === 'checkin' ? 'Check-in' : 'Check-out'} Management
          </h2>
          <button 
            onClick={onClose}
            className="text-gray-400 hover:text-white"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        <div className="flex border-b border-gray-700 mb-6">
          <button
            type="button"
            className={`px-6 py-3 font-medium ${
              activeTab === 'checkin'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('checkin')}
          >
            Check-in
          </button>
          <button
            type="button"
            className={`px-6 py-3 font-medium ${
              activeTab === 'checkout'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('checkout')}
          >
            Check-out
          </button>
        </div>
        
        <div className="flex-1 overflow-y-auto">
          <div className="mb-6">
            <div className="flex justify-between items-center mb-4">
              <div className="relative w-96">
                <input
                  type="text"
                  placeholder={`Search ${activeTab === 'checkin' ? 'arrivals' : 'departures'}...`}
                  className="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
              </div>
              <div className="flex space-x-2">
                <select className="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm">
                  <option>Today</option>
                  <option>Tomorrow</option>
                  <option>This Week</option>
                  <option>Custom Range</option>
                </select>
              </div>
            </div>
            
            <div className="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
              <table className="min-w-full divide-y divide-gray-700">
                <thead className="bg-gray-800">
                  <tr>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                      Guest
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                      Room
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                      {activeTab === 'checkin' ? 'Arrival' : 'Departure'}
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                      Status
                    </th>
                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                      Balance
                    </th>
                    <th scope="col" className="relative px-6 py-3">
                      <span className="sr-only">Actions</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-gray-800 divide-y divide-gray-700">
                  {[1, 2, 3].map((item) => (
                    <tr key={item} className="hover:bg-gray-750">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="flex-shrink-0 h-10 w-10 rounded-full bg-gray-700 flex items-center justify-center">
                            <User className="h-5 w-5 text-gray-400" />
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-medium text-white">John Doe</div>
                            <div className="text-sm text-gray-400">Reservation #R{1000 + item}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-white">Deluxe King</div>
                        <div className="text-sm text-gray-400">#420{item}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-white">
                          {activeTab === 'checkin' ? 'Jun 15, 2023' : 'Jun 18, 2023'}
                        </div>
                        <div className="text-sm text-gray-400">
                          {activeTab === 'checkin' ? '3:00 PM' : '12:00 PM'}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                          activeTab === 'checkin' 
                            ? 'bg-blue-900 text-blue-300' 
                            : 'bg-purple-900 text-purple-300'
                        }`}>
                          {activeTab === 'checkin' ? 'Expected' : 'Scheduled'}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-white">
                        ${(1000 + item * 50).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button className="text-blue-400 hover:text-blue-300 mr-3">
                          {activeTab === 'checkin' ? 'Check In' : 'Check Out'}
                        </button>
                        <button className="text-gray-400 hover:text-gray-300">
                          <MoreVertical className="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
          
          {activeTab === 'checkin' && (
            <div className="bg-gray-800 rounded-lg border border-gray-700 p-6">
              <h3 className="text-lg font-medium mb-4">Quick Check-in</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-1">Reservation #</label>
                  <div className="relative">
                    <input
                      type="text"
                      placeholder="Enter reservation number"
                      className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <Search className="absolute right-3 top-2.5 h-4 w-4 text-gray-400" />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-1">Last Name</label>
                  <input
                    type="text"
                    placeholder="Guest last name"
                    className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="flex items-end">
                  <button className="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg flex items-center justify-center space-x-2">
                    <Search className="h-4 w-4" />
                    <span>Find Reservation</span>
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </Modal>
  );
}
