'use client';

import { useState } from 'react';
import Modal from '@/components/ui/Modal';
import { X, CreditCard, DollarSign, CheckCircle2, AlertCircle, Search, Plus, Filter, Download, Printer, MoreVertical, ArrowUpDown } from 'lucide-react';

import { PaymentsModalProps } from './types';

type PaymentStatus = 'pending' | 'completed' | 'failed' | 'refunded';
type PaymentMethod = 'credit_card' | 'debit_card' | 'cash' | 'bank_transfer' | 'other';

interface Payment {
  id: string;
  invoiceNumber: string;
  guestName: string;
  roomNumber: string;
  amount: number;
  date: string;
  status: PaymentStatus;
  method: PaymentMethod;
  processedBy: string;
  notes?: string;
}

export default function PaymentsModal({ isOpen, onClose }: PaymentsModalProps) {
  const [activeTab, setActiveTab] = useState<'transactions' | 'new'>('transactions');
  const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
  
  // Sample data
  const payments: Payment[] = [
    {
      id: 'PAY-1001',
      invoiceNumber: 'INV-2023-001',
      guestName: 'John Smith',
      roomNumber: '420',
      amount: 1250.75,
      date: '2023-06-15T14:30:00Z',
      status: 'completed',
      method: 'credit_card',
      processedBy: 'Sarah K.',
      notes: 'Paid in full'
    },
    {
      id: 'PAY-1002',
      invoiceNumber: 'INV-2023-002',
      guestName: 'Emma Wilson',
      roomNumber: '315',
      amount: 980.50,
      date: '2023-06-16T09:15:00Z',
      status: 'pending',
      method: 'bank_transfer',
      processedBy: 'Mike D.',
      notes: 'Awaiting bank confirmation'
    },
    {
      id: 'PAY-1003',
      invoiceNumber: 'INV-2023-003',
      guestName: 'Robert Chen',
      roomNumber: '207',
      amount: 1560.25,
      date: '2023-06-14T18:20:00Z',
      status: 'failed',
      method: 'credit_card',
      processedBy: 'John D.',
      notes: 'Card declined'
    },
    {
      id: 'PAY-1004',
      invoiceNumber: 'INV-2023-004',
      guestName: 'Lisa Wong',
      roomNumber: '512',
      amount: 2300.00,
      date: '2023-06-13T11:45:00Z',
      status: 'refunded',
      method: 'credit_card',
      processedBy: 'Sarah K.',
      notes: 'Full refund for cancellation'
    },
  ];

  const StatusBadge = ({ status }: { status: PaymentStatus }) => {
    const styles = {
      pending: 'bg-yellow-100 text-yellow-800',
      completed: 'bg-green-100 text-green-800',
      failed: 'bg-red-100 text-red-800',
      refunded: 'bg-blue-100 text-blue-800'
    };
    
    const labels = {
      pending: 'Pending',
      completed: 'Completed',
      failed: 'Failed',
      refunded: 'Refunded'
    };
    
    return (
      <span className={`text-xs px-2 py-1 rounded-full ${styles[status]}`}>
        {labels[status]}
      </span>
    );
  };

  const MethodBadge = ({ method }: { method: PaymentMethod }) => {
    const styles = {
      credit_card: 'bg-purple-100 text-purple-800',
      debit_card: 'bg-indigo-100 text-indigo-800',
      cash: 'bg-green-100 text-green-800',
      bank_transfer: 'bg-blue-100 text-blue-800',
      other: 'bg-gray-100 text-gray-800'
    };
    
    const labels = {
      credit_card: 'Credit Card',
      debit_card: 'Debit Card',
      cash: 'Cash',
      bank_transfer: 'Bank Transfer',
      other: 'Other'
    };
    
    return (
      <span className={`text-xs px-2 py-1 rounded-full ${styles[method]}`}>
        {labels[method]}
      </span>
    );
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2
    }).format(amount);
  };

  const PaymentItem = ({ payment, onClick }: { payment: Payment; onClick: () => void }) => (
    <tr 
      className="border-b border-gray-700 hover:bg-gray-800 cursor-pointer"
      onClick={onClick}
    >
      <td className="px-6 py-4 whitespace-nowrap">
        <div className="text-sm font-medium text-white">{payment.invoiceNumber}</div>
        <div className="text-xs text-gray-400">{payment.id}</div>
      </td>
      <td className="px-6 py-4 whitespace-nowrap">
        <div className="text-sm text-white">{payment.guestName}</div>
        <div className="text-xs text-gray-400">Room {payment.roomNumber}</div>
      </td>
      <td className="px-6 py-4 whitespace-nowrap">
        <div className="text-sm font-medium text-white">{formatCurrency(payment.amount)}</div>
      </td>
      <td className="px-6 py-4 whitespace-nowrap">
        <StatusBadge status={payment.status} />
      </td>
      <td className="px-6 py-4 whitespace-nowrap">
        <MethodBadge method={payment.method} />
      </td>
      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
        {new Date(payment.date).toLocaleDateString()}
      </td>
    </tr>
  );

  const PaymentDetail = ({ payment, onClose }: { payment: Payment; onClose: () => void }) => (
    <div className="bg-gray-800 p-6 rounded-lg">
      <div className="flex justify-between items-start mb-6">
        <div>
          <h3 className="text-lg font-medium">Payment Details</h3>
          <p className="text-gray-400">Invoice: {payment.invoiceNumber}</p>
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
          <h4 className="text-sm font-medium text-gray-400 mb-2">PAYMENT INFORMATION</h4>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-gray-400">Payment ID:</span>
              <span className="text-white">{payment.id}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-400">Invoice Number:</span>
              <span className="text-white">{payment.invoiceNumber}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-400">Date:</span>
              <span className="text-white">{new Date(payment.date).toLocaleString()}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-400">Status:</span>
              <StatusBadge status={payment.status} />
            </div>
            <div className="flex justify-between">
              <span className="text-gray-400">Payment Method:</span>
              <MethodBadge method={payment.method} />
            </div>
          </div>
        </div>
        
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">GUEST INFORMATION</h4>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-gray-400">Guest Name:</span>
              <span className="text-white">{payment.guestName}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-400">Room Number:</span>
              <span className="text-white">{payment.roomNumber}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-400">Processed By:</span>
              <span className="text-white">{payment.processedBy}</span>
            </div>
          </div>
          
          <div className="mt-6 p-4 bg-gray-700 rounded-lg">
            <div className="text-2xl font-bold text-center text-white">
              {formatCurrency(payment.amount)}
            </div>
            <div className="text-center text-gray-400 text-sm mt-1">
              Total Amount
            </div>
          </div>
        </div>
      </div>
      
      {payment.notes && (
        <div className="mb-6">
          <h4 className="text-sm font-medium text-gray-400 mb-2">NOTES</h4>
          <div className="bg-gray-700 p-3 rounded-lg">
            <p className="text-white">{payment.notes}</p>
          </div>
        </div>
      )}
      
      <div className="mt-6 flex justify-between pt-4 border-t border-gray-700">
        <button 
          className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg"
          onClick={onClose}
        >
          Back to List
        </button>
        <div className="flex space-x-3">
          <button className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center">
            <Printer className="w-4 h-4 mr-2" />
            <span>Print Receipt</span>
          </button>
          <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            Process Refund
          </button>
        </div>
      </div>
    </div>
  );

  const NewPaymentForm = () => (
    <div className="bg-gray-800 p-6 rounded-lg border border-gray-700">
      <h3 className="text-lg font-medium mb-6">Record New Payment</h3>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Guest Name</label>
          <input 
            type="text" 
            className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter guest name"
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Room Number</label>
          <input 
            type="text" 
            className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter room number"
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Invoice Number</label>
          <input 
            type="text" 
            className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter invoice number"
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Amount</label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <DollarSign className="h-4 w-4 text-gray-400" />
            </div>
            <input 
              type="number" 
              step="0.01"
              className="w-full bg-gray-700 border border-gray-600 rounded-lg pl-10 pr-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="0.00"
            />
          </div>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Payment Method</label>
          <select className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Select payment method</option>
            <option value="credit_card">Credit Card</option>
            <option value="debit_card">Debit Card</option>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="other">Other</option>
          </select>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Status</label>
          <select className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>
        
        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-300 mb-1">Notes</label>
          <textarea 
            className="w-full h-24 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Add any notes about this payment..."
          ></textarea>
        </div>
      </div>
      
      <div className="mt-8 flex justify-end space-x-3">
        <button 
          className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg"
          onClick={() => setActiveTab('transactions')}
        >
          Cancel
        </button>
        <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center">
          <CheckCircle2 className="w-4 h-4 mr-2" />
          <span>Record Payment</span>
        </button>
      </div>
    </div>
  );

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl">
      <div className="flex flex-col h-[80vh]">
        <div className="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
          <h2 className="text-xl font-semibold">Payments Management</h2>
          <button 
            onClick={onClose}
            className="text-gray-400 hover:text-white"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        {selectedPayment ? (
          <PaymentDetail 
            payment={selectedPayment} 
            onClose={() => setSelectedPayment(null)} 
          />
        ) : (
          <>
            <div className="flex justify-between items-center mb-6">
              <div className="relative w-96">
                <input
                  type="text"
                  placeholder="Search payments..."
                  className="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
              </div>
              
              <div className="flex space-x-2">
                <button className="flex items-center space-x-1 px-3 py-2 border border-gray-600 rounded-lg text-gray-300 hover:bg-gray-700">
                  <Filter className="w-4 h-4" />
                  <span className="text-sm">Filter</span>
                </button>
                <button className="flex items-center space-x-1 px-3 py-2 border border-gray-600 rounded-lg text-gray-300 hover:bg-gray-700">
                  <Download className="w-4 h-4" />
                  <span className="text-sm">Export</span>
                </button>
                <button 
                  className="flex items-center space-x-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
                  onClick={() => setActiveTab('new')}
                >
                  <Plus className="w-4 h-4" />
                  <span>New Payment</span>
                </button>
              </div>
            </div>
            
            <div className="flex-1 overflow-y-auto">
              {activeTab === 'transactions' ? (
                <div className="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                  <table className="min-w-full divide-y divide-gray-700">
                    <thead className="bg-gray-800">
                      <tr>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                          <div className="flex items-center">
                            Invoice
                            <ArrowUpDown className="ml-1 h-3 w-3" />
                          </div>
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                          <div className="flex items-center">
                            Guest
                            <ArrowUpDown className="ml-1 h-3 w-3" />
                          </div>
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                          <div className="flex items-center">
                            Amount
                            <ArrowUpDown className="ml-1 h-3 w-3" />
                          </div>
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                          <div className="flex items-center">
                            Status
                            <ArrowUpDown className="ml-1 h-3 w-3" />
                          </div>
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                          <div className="flex items-center">
                            Method
                            <ArrowUpDown className="ml-1 h-3 w-3" />
                          </div>
                        </th>
                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                          <div className="flex items-center">
                            Date
                            <ArrowUpDown className="ml-1 h-3 w-3" />
                          </div>
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-gray-800 divide-y divide-gray-700">
                      {payments.map((payment) => (
                        <PaymentItem 
                          key={payment.id}
                          payment={payment}
                          onClick={() => setSelectedPayment(payment)}
                        />
                      ))}
                    </tbody>
                  </table>
                  
                  <div className="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
                    <div className="text-sm text-gray-400">
                      Showing <span className="font-medium">1</span> to <span className="font-medium">4</span> of <span className="font-medium">24</span> results
                    </div>
                    <div className="flex space-x-2">
                      <button className="px-3 py-1 border border-gray-600 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700">
                        Previous
                      </button>
                      <button className="px-3 py-1 bg-blue-600 text-white rounded-md text-sm font-medium">
                        1
                      </button>
                      <button className="px-3 py-1 border border-gray-600 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700">
                        2
                      </button>
                      <button className="px-3 py-1 border border-gray-600 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700">
                        Next
                      </button>
                    </div>
                  </div>
                </div>
              ) : (
                <NewPaymentForm />
              )}
            </div>
            
            <div className="mt-4 pt-4 border-t border-gray-700 flex justify-between items-center">
              <div className="text-sm text-gray-400">
                Total Collected: <span className="text-white font-medium">{formatCurrency(6091.50)}</span>
              </div>
              <div className="flex space-x-4">
                <div className="text-sm">
                  <div className="text-green-400">Completed: {formatCurrency(3530.75)}</div>
                  <div className="text-yellow-400">Pending: {formatCurrency(980.50)}</div>
                </div>
                <div className="text-sm">
                  <div className="text-red-400">Failed: {formatCurrency(1560.25)}</div>
                  <div className="text-blue-400">Refunded: {formatCurrency(2300.00)}</div>
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </Modal>
  );
}
