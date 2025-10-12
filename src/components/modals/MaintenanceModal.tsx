'use client';

import { useState } from 'react';
import Modal from '@/components/ui/Modal';
import { X, Wrench, AlertTriangle, Clock, CheckCircle2, Search, Plus, Filter, MoreVertical } from 'lucide-react';

type MaintenanceStatus = 'pending' | 'in-progress' | 'completed' | 'issue';
type MaintenancePriority = 'low' | 'medium' | 'high';

import { MaintenanceModalProps } from './types';

interface MaintenanceTicket {
  id: string;
  room: string;
  issue: string;
  status: MaintenanceStatus;
  priority: MaintenancePriority;
  assignedTo: string;
  reportedAt: string;
  completedAt?: string;
}

export default function MaintenanceModal({ isOpen, onClose }: MaintenanceModalProps) {
  const [activeTab, setActiveTab] = useState<'list' | 'new'>('list');
  const [selectedTicket, setSelectedTicket] = useState<MaintenanceTicket | null>(null);
  
  // Sample data
  const [tickets, setTickets] = useState<MaintenanceTicket[]>([
    {
      id: 'MNT-1001',
      room: '4201',
      issue: 'AC not cooling properly',
      status: 'in-progress',
      priority: 'high',
      assignedTo: 'John D.',
      reportedAt: '2023-06-14T09:30:00Z'
    },
    {
      id: 'MNT-1002',
      room: '3205',
      issue: 'Leaky faucet',
      status: 'pending',
      priority: 'medium',
      assignedTo: 'Mike S.',
      reportedAt: '2023-06-15T14:20:00Z'
    },
    {
      id: 'MNT-1003',
      room: '2110',
      issue: 'TV remote not working',
      status: 'completed',
      priority: 'low',
      assignedTo: 'Sarah K.',
      reportedAt: '2023-06-15T10:15:00Z',
      completedAt: '2023-06-15T11:30:00Z'
    },
  ]);

  const PriorityBadge = ({ priority }: { priority: MaintenancePriority }) => {
    const styles = {
      low: 'bg-green-100 text-green-800',
      medium: 'bg-yellow-100 text-yellow-800',
      high: 'bg-red-100 text-red-800'
    };
    
    return (
      <span className={`text-xs px-2 py-1 rounded-full ${styles[priority]}`}>
        {priority}
      </span>
    );
  };

  const StatusBadge = ({ status }: { status: MaintenanceStatus }) => {
    const styles = {
      pending: 'bg-yellow-100 text-yellow-800',
      'in-progress': 'bg-blue-100 text-blue-800',
      completed: 'bg-green-100 text-green-800',
      issue: 'bg-red-100 text-red-800'
    };
    
    const icons = {
      pending: <Clock className="w-3 h-3 mr-1" />,
      'in-progress': <Wrench className="w-3 h-3 mr-1" />,
      completed: <CheckCircle2 className="w-3 h-3 mr-1" />,
      issue: <AlertTriangle className="w-3 h-3 mr-1" />
    };
    
    return (
      <span className={`inline-flex items-center text-xs px-2 py-1 rounded-full ${styles[status]}`}>
        {icons[status]}
        {status.replace('-', ' ')}
      </span>
    );
  };

  const TicketItem = ({ ticket, onClick }: { ticket: MaintenanceTicket; onClick: () => void }) => (
    <div 
      className="p-4 border border-gray-700 rounded-lg hover:bg-gray-800 cursor-pointer transition-colors mb-2"
      onClick={onClick}
    >
      <div className="flex justify-between items-start">
        <div>
          <h4 className="font-medium">Room {ticket.room} - {ticket.issue}</h4>
          <p className="text-sm text-gray-400">
            {new Date(ticket.reportedAt).toLocaleString()} • {ticket.assignedTo}
          </p>
        </div>
        <div className="flex space-x-2">
          <PriorityBadge priority={ticket.priority} />
          <StatusBadge status={ticket.status} />
        </div>
      </div>
    </div>
  );

  const TicketDetail = ({ ticket, onClose }: { ticket: MaintenanceTicket; onClose: () => void }) => (
    <div className="bg-gray-800 p-6 rounded-lg">
      <div className="flex justify-between items-start mb-6">
        <div>
          <h3 className="text-lg font-medium">Maintenance Ticket #{ticket.id}</h3>
          <p className="text-gray-400">Room {ticket.room}</p>
        </div>
        <button 
          onClick={onClose}
          className="text-gray-400 hover:text-white"
        >
          <X className="w-5 h-5" />
        </button>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">ISSUE</h4>
          <p className="text-white">{ticket.issue}</p>
        </div>
        
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">STATUS</h4>
          <div className="flex items-center space-x-2">
            <StatusBadge status={ticket.status} />
            <PriorityBadge priority={ticket.priority} />
          </div>
        </div>
        
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">ASSIGNED TO</h4>
          <p className="text-white">{ticket.assignedTo}</p>
        </div>
        
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">REPORTED</h4>
          <p className="text-white">{new Date(ticket.reportedAt).toLocaleString()}</p>
        </div>
        
        {ticket.completedAt && (
          <div>
            <h4 className="text-sm font-medium text-gray-400 mb-2">COMPLETED</h4>
            <p className="text-white">{new Date(ticket.completedAt).toLocaleString()}</p>
          </div>
        )}
      </div>
      
      <div className="mt-6">
        <h4 className="text-sm font-medium text-gray-400 mb-2">NOTES</h4>
        <div className="bg-gray-700 rounded-lg p-4 min-h-24">
          {ticket.status === 'completed' ? (
            <p className="text-white">Issue has been resolved. {ticket.assignedTo} confirmed the fix.</p>
          ) : (
            <p className="text-gray-400">No additional notes available.</p>
          )}
        </div>
      </div>
      
      <div className="mt-6 flex space-x-3">
        {ticket.status !== 'completed' && (
          <button className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center space-x-2">
            <CheckCircle2 className="w-4 h-4" />
            <span>Mark as Complete</span>
          </button>
        )}
        <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
          Edit Ticket
        </button>
        <button 
          className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg"
          onClick={onClose}
        >
          Back to List
        </button>
      </div>
    </div>
  );

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl">
      <div className="flex flex-col h-[80vh]">
        <div className="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
          <h2 className="text-xl font-semibold">Maintenance Management</h2>
          <button 
            onClick={onClose}
            className="text-gray-400 hover:text-white"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        {!selectedTicket ? (
          <>
            <div className="flex justify-between items-center mb-6">
              <div className="relative w-96">
                <input
                  type="text"
                  placeholder="Search maintenance tickets..."
                  className="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
              </div>
              <div className="flex space-x-2">
                <button className="flex items-center space-x-1 px-3 py-2 border border-gray-600 rounded-lg text-gray-300 hover:bg-gray-700">
                  <Filter className="w-4 h-4" />
                  <span>Filter</span>
                </button>
                <button 
                  className="flex items-center space-x-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
                  onClick={() => setActiveTab('new')}
                >
                  <Plus className="w-4 h-4" />
                  <span>New Ticket</span>
                </button>
              </div>
            </div>
            
            <div className="flex-1 overflow-y-auto">
              {activeTab === 'list' ? (
                <div className="space-y-3">
                  {tickets.map((ticket) => (
                    <TicketItem 
                      key={ticket.id}
                      ticket={ticket}
                      onClick={() => setSelectedTicket(ticket)}
                    />
                  ))}
                </div>
              ) : (
                <div className="bg-gray-800 p-6 rounded-lg border border-gray-700">
                  <h3 className="text-lg font-medium mb-6">Create New Maintenance Ticket</h3>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Room Number</label>
                      <input 
                        type="text" 
                        className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter room number"
                      />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Issue Type</label>
                      <select className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none">
                        <option>Select issue type</option>
                        <option>Air Conditioning</option>
                        <option>Plumbing</option>
                        <option>Electrical</option>
                        <option>Furniture</option>
                        <option>TV/Entertainment</option>
                        <option>Other</option>
                      </select>
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Priority</label>
                      <div className="flex space-x-2">
                        <button className="px-3 py-1.5 text-xs rounded-full bg-red-100 text-red-800">High</button>
                        <button className="px-3 py-1.5 text-xs rounded-full bg-yellow-100 text-yellow-800">Medium</button>
                        <button className="px-3 py-1.5 text-xs rounded-full bg-green-100 text-green-800">Low</button>
                      </div>
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Description</label>
                      <textarea 
                        className="w-full h-32 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Describe the issue in detail..."
                      ></textarea>
                    </div>
                    
                    <div className="flex justify-end space-x-3 pt-4">
                      <button 
                        className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg"
                        onClick={() => setActiveTab('list')}
                      >
                        Cancel
                      </button>
                      <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        Create Ticket
                      </button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </>
        ) : (
          <div className="flex-1 overflow-y-auto">
            <TicketDetail 
              ticket={selectedTicket} 
              onClose={() => setSelectedTicket(null)} 
            />
          </div>
        )}
      </div>
    </Modal>
  );
}
