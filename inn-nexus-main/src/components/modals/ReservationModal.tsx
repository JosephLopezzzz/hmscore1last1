'use client';

import { useState, useEffect } from 'react';
import Modal from '@/components/ui/Modal';
import { 
  X, Calendar, User, Clock, Bed, Users, CreditCard, Plus, 
  ChevronDown, Search, MoreVertical, Check, ChevronRight, 
  UserPlus, Hash, DollarSign, FileText, CheckCircle, Clock as ClockIcon
} from 'lucide-react';
import { format, addDays } from 'date-fns';

// Import types
import type { 
  ReservationModalProps, 
  RoomType, 
  PaymentMethod, 
  Customer, 
  Room, 
  Service, 
  Voucher, 
  ReservationStatus 
} from './types';

type ReservationStep = 'search' | 'customer' | 'services' | 'payment' | 'confirm';

declare global {
  namespace JSX {
    interface IntrinsicElements {
      div: React.DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement>;
      span: React.DetailedHTMLProps<React.HTMLAttributes<HTMLSpanElement>, HTMLSpanElement>;
      button: React.DetailedHTMLProps<React.ButtonHTMLAttributes<HTMLButtonElement>, HTMLButtonElement>;
      input: React.DetailedHTMLProps<React.InputHTMLAttributes<HTMLInputElement>, HTMLInputElement>;
      select: React.DetailedHTMLProps<React.SelectHTMLAttributes<HTMLSelectElement>, HTMLSelectElement>;
      option: React.DetailedHTMLProps<React.OptionHTMLAttributes<HTMLOptionElement>, HTMLOptionElement>;
      table: React.DetailedHTMLProps<React.TableHTMLAttributes<HTMLTableElement>, HTMLTableElement>;
      thead: React.DetailedHTMLProps<React.HTMLAttributes<HTMLTableSectionElement>, HTMLTableSectionElement>;
      tbody: React.DetailedHTMLProps<React.HTMLAttributes<HTMLTableSectionElement>, HTMLTableSectionElement>;
      tr: React.DetailedHTMLProps<React.HTMLAttributes<HTMLTableRowElement>, HTMLTableRowElement>;
      th: React.DetailedHTMLProps<React.ThHTMLAttributes<HTMLTableHeaderCellElement>, HTMLTableHeaderCellElement>;
      td: React.DetailedHTMLProps<React.TdHTMLAttributes<HTMLTableDataCellElement>, HTMLTableDataCellElement>;
    }
  }
}

// Mock data - in a real app, this would come from an API
const mockRooms: Room[] = [
  { 
    id: '1', 
    number: '101', 
    type: 'single', 
    price: 100, 
    maxOccupancy: 2, 
    available: true, 
    amenities: ['TV', 'WiFi', 'AC'],
    description: 'Cozy single room with standard amenities'
  },
  { 
    id: '2', 
    number: '201', 
    type: 'double', 
    price: 150, 
    maxOccupancy: 4, 
    available: true, 
    amenities: ['TV', 'WiFi', 'AC', 'Minibar'],
    description: 'Spacious double room with minibar'
  },
  { 
    id: '3', 
    number: '301', 
    type: 'deluxe', 
    price: 250, 
    maxOccupancy: 4, 
    available: true, 
    amenities: ['TV', 'WiFi', 'AC', 'Minibar', 'Balcony'],
    description: 'Luxurious deluxe room with balcony view'
  },
  { 
    id: '4', 
    number: '401', 
    type: 'suite', 
    price: 400, 
    maxOccupancy: 6, 
    available: true, 
    amenities: ['TV', 'WiFi', 'AC', 'Minibar', 'Balcony', 'Jacuzzi'],
    description: 'Premium suite with jacuzzi and city view'
  },
];

const mockServices: Service[] = [
  { 
    id: 's1', 
    name: 'Airport Transfer', 
    description: 'One way transfer to/from airport', 
    price: 50, 
    chargeType: 'per_stay' 
  },
  { 
    id: 's2', 
    name: 'Breakfast', 
    description: 'Daily breakfast buffet', 
    price: 15, 
    chargeType: 'per_person' 
  },
  { 
    id: 's3', 
    name: 'Spa Access', 
    description: 'Full day access to spa facilities', 
    price: 30, 
    chargeType: 'per_stay' 
  },
  { 
    id: 's4', 
    name: 'Laundry', 
    description: 'One load of laundry', 
    price: 20, 
    chargeType: 'per_stay' 
  },
];

const ReservationModal: React.FC<ReservationModalProps> = ({ 
  isOpen, 
  onClose, 
  onReserve,
  onCheckIn,
  onCheckOut,
  onPay
}) => {
  // Form state
  const [activeTab, setActiveTab] = useState<'new' | 'existing'>('new');
  const [step, setStep] = useState<'search' | 'customer' | 'services' | 'payment' | 'confirm'>('search');
  
  // Search criteria
  const [checkInDate, setCheckInDate] = useState<Date>(new Date());
  const [checkOutDate, setCheckOutDate] = useState<Date>(addDays(new Date(), 1));
  const [adults, setAdults] = useState<number>(2);
  const [children, setChildren] = useState<number>(0);
  const [roomType, setRoomType] = useState<RoomType | 'all'>('all');
  
  // Customer info
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [showNewCustomerForm, setShowNewCustomerForm] = useState<boolean>(false);
  const [newCustomer, setNewCustomer] = useState<Omit<Customer, 'id'>>({ 
    name: '', 
    email: '', 
    phone: '',
    address: ''
  });
  
  // Room selection
  const [availableRooms, setAvailableRooms] = useState<Room[]>([]);
  const [selectedRoom, setSelectedRoom] = useState<Room | null>(null);
  
  // Services
  const [selectedServices, setSelectedServices] = useState<{service: Service, quantity: number}[]>([]);
  
  // Voucher
  const [voucherCode, setVoucherCode] = useState<string>('');
  const [voucher, setVoucher] = useState<Voucher | null>(null);
  const [voucherError, setVoucherError] = useState<string | null>(null);
  
  // Payment
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('credit_card');
  const [specialRequests, setSpecialRequests] = useState<string>('');
  
  // Calculate totals
  const calculateTotal = () => {
    if (!selectedRoom) return 0;
    
    const nights = Math.ceil((checkOutDate.getTime() - checkInDate.getTime()) / (1000 * 60 * 60 * 24));
    let total = selectedRoom.price * nights;
    
    // Add services
    selectedServices.forEach(item => {
      if (item.service.chargeType === 'per_stay') {
        total += item.service.price * item.quantity;
      } else if (item.service.chargeType === 'per_night') {
        total += item.service.price * nights * item.quantity;
      } else {
        total += item.service.price * (adults + children) * item.quantity;
      }
    });
    
    // Apply voucher
    if (voucher) {
      if (voucher.discountType === 'percentage') {
        total = total * (1 - voucher.discountValue / 100);
      } else {
        total = Math.max(0, total - voucher.discountValue);
      }
    }
    
    return Math.round(total * 100) / 100;
  };
  
  const totalAmount = calculateTotal();
  
  // Mock search for available rooms
  useEffect(() => {
    // In a real app, this would be an API call
    const filteredRooms = mockRooms.filter(room => {
      if (roomType !== 'all' && room.type !== roomType) return false;
      return room.available;
    });
    
    setAvailableRooms(filteredRooms);
    if (filteredRooms.length > 0 && !selectedRoom) {
      setSelectedRoom(filteredRooms[0]);
    }
  }, [roomType, checkInDate, checkOutDate]);
  
  // Mock customer search
  useEffect(() => {
    if (searchTerm.length > 2) {
      // In a real app, this would be an API call
      setTimeout(() => {
        setCustomers([
          { id: '1', name: 'John Doe', email: 'john@example.com', phone: '+1234567890' },
          { id: '2', name: 'Jane Smith', email: 'jane@example.com', phone: '+1987654321' },
        ].filter(c => 
          c.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
          c.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
          c.phone.includes(searchTerm)
        ));
      }, 500);
    } else {
      setCustomers([]);
    }
  }, [searchTerm]);
  
  const handleReserve = () => {
    if (!selectedCustomer || !selectedRoom) return;
    
    const reservation: Omit<ReservationItem, 'id' | 'createdAt' | 'updatedAt'> = {
      customer: selectedCustomer,
      room: selectedRoom,
      checkIn: checkInDate,
      checkOut: checkOutDate,
      adults,
      children,
      status: 'confirmed',
      services: selectedServices,
      voucher: voucher || undefined,
      totalAmount,
      paidAmount: 0,
      paymentMethod: undefined,
      specialRequests: specialRequests || undefined,
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    onReserve?.(reservation);
    onClose();
  };
  
  const handleCheckIn = (reservationId: string) => {
    onCheckIn?.(reservationId);
  };
  
  const handleCheckOut = (reservationId: string) => {
    onCheckOut?.(reservationId);
  };
  
  const handlePay = (reservationId: string) => {
    onPay?.(reservationId, totalAmount, paymentMethod);
  };
  
  const applyVoucher = () => {
    // In a real app, this would validate the voucher with an API
    if (voucherCode === 'WELCOME10') {
      setVoucher({
        id: 'v1',
        code: voucherCode,
        discountType: 'percentage',
        discountValue: 10,
        validUntil: addDays(new Date(), 30),
        isUsed: false
      });
      setVoucherError(null);
    } else if (voucherCode === 'SAVE50') {
      setVoucher({
        id: 'v2',
        code: voucherCode,
        discountType: 'fixed',
        discountValue: 50,
        validUntil: addDays(new Date(), 60),
        isUsed: false
      });
      setVoucherError(null);
    } else {
      setVoucherError('Invalid or expired voucher code');
      setVoucher(null);
    }
  };
  
  const addService = (service: Service) => {
    setSelectedServices(prev => {
      const existing = prev.find(item => item.service.id === service.id);
      if (existing) {
        return prev.map(item => 
          item.service.id === service.id 
            ? { ...item, quantity: item.quantity + 1 } 
            : item
        );
      }
      return [...prev, { service, quantity: 1 }];
    });
  };
  
  const removeService = (serviceId: string) => {
    setSelectedServices(prev => 
      prev.filter(item => item.service.id !== serviceId)
    );
  };
  
  const updateServiceQuantity = (serviceId: string, quantity: number) => {
    if (quantity < 1) return;
    
    setSelectedServices(prev => 
      prev.map(item => 
        item.service.id === serviceId 
          ? { ...item, quantity } 
          : item
      )
    );
  };
  
  const renderSearchStep = (): React.ReactNode => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Check-in</label>
          <div className="relative">
            <input
              type="date"
              className="w-full bg-gray-800 border border-gray-700 rounded-md py-2 px-3 text-white"
              value={format(checkInDate, 'yyyy-MM-dd')}
              onChange={(e) => setCheckInDate(new Date(e.target.value))}
              min={format(new Date(), 'yyyy-MM-dd')}
            />
            <Calendar className="absolute right-3 top-2.5 h-5 w-5 text-gray-400" />
          </div>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Check-out</label>
          <div className="relative">
            <input
              type="date"
              className="w-full bg-gray-800 border border-gray-700 rounded-md py-2 px-3 text-white"
              value={format(checkOutDate, 'yyyy-MM-dd')}
              onChange={(e) => setCheckOutDate(new Date(e.target.value))}
              min={format(addDays(checkInDate, 1), 'yyyy-MM-dd')}
            />
            <Calendar className="absolute right-3 top-2.5 h-5 w-5 text-gray-400" />
          </div>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Adults</label>
          <div className="flex">
            <button 
              className="bg-gray-700 text-white px-3 py-1 rounded-l-md hover:bg-gray-600"
              onClick={() => setAdults(prev => Math.max(1, prev - 1))}
            >
              -
            </button>
            <div className="bg-gray-800 px-4 py-1 flex items-center justify-center">
              {adults}
            </div>
            <button 
              className="bg-gray-700 text-white px-3 py-1 rounded-r-md hover:bg-gray-600"
              onClick={() => setAdults(prev => prev + 1)}
            >
              +
            </button>
          </div>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-300 mb-1">Children</label>
          <div className="flex">
            <button 
              className="bg-gray-700 text-white px-3 py-1 rounded-l-md hover:bg-gray-600"
              onClick={() => setChildren(prev => Math.max(0, prev - 1))}
              disabled={children === 0}
            >
              -
            </button>
            <div className="bg-gray-800 px-4 py-1 flex items-center justify-center">
              {children}
            </div>
            <button 
              className="bg-gray-700 text-white px-3 py-1 rounded-r-md hover:bg-gray-600"
              onClick={() => setChildren(prev => prev + 1)}
            >
              +
            </button>
          </div>
        </div>
        
        <div className="md:col-span-2">
          <label className="block text-sm font-medium text-gray-300 mb-1">Room Type</label>
          <select
            className="w-full bg-gray-800 border border-gray-700 rounded-md py-2 px-3 text-white"
            value={roomType}
            onChange={(e) => setRoomType(e.target.value as RoomType | 'all')}
          >
            <option value="all">All Types</option>
            <option value="single">Single Room</option>
            <option value="double">Double Room</option>
            <option value="deluxe">Deluxe Room</option>
            <option value="suite">Suite</option>
          </select>
        </div>
      </div>
      
      <div className="mt-6">
        <h3 className="text-lg font-medium text-white mb-3">Available Rooms</h3>
        <div className="space-y-3">
          {availableRooms.length > 0 ? (
            availableRooms.map(room => (
              <div 
                key={room.id}
                className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                  selectedRoom?.id === room.id 
                    ? 'border-blue-500 bg-blue-900/20' 
                    : 'border-gray-700 hover:border-gray-600'
                }`}
                onClick={() => setSelectedRoom(room)}
              >
                <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-medium text-white">Room {room.number} - {room.type.charAt(0).toUpperCase() + room.type.slice(1)}</h4>
                    <p className="text-sm text-gray-300">Max {room.maxOccupancy} guests • ${room.price}/night</p>
                    <div className="flex flex-wrap gap-1 mt-2">
                      {room.amenities.map(amenity => (
                        <span key={amenity} className="text-xs bg-gray-700 text-gray-200 px-2 py-0.5 rounded">
                          {amenity}
                        </span>
                      ))}
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-xl font-semibold text-white">
                      ${room.price * Math.ceil((checkOutDate.getTime() - checkInDate.getTime()) / (1000 * 60 * 60 * 24))}
                    </div>
                    <div className="text-xs text-gray-400">total</div>
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-8 text-gray-400">
              No rooms available for the selected dates and filters.
            </div>
          )}
        </div>
      </div>
      
      <div className="flex justify-end mt-6">
        <button
          type="button"
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          onClick={() => setStep('customer')}
          disabled={!selectedRoom}
        >
          Next: Guest Information
          <ChevronRight className="ml-2 h-4 w-4" />
        </button>
      </div>
    </div>
  );
  
  const renderCustomerStep = (): React.ReactNode => (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium text-white mb-4">Guest Information</h3>
        
        {!selectedCustomer ? (
          <>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search className="h-5 w-5 text-gray-400" />
              </div>
              <input
                type="text"
                className="block w-full pl-10 pr-3 py-2 border border-gray-700 rounded-md leading-5 bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                placeholder="Search for existing guest..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
            
            {customers.length > 0 && (
              <div className="mt-2 border border-gray-700 rounded-md overflow-hidden">
                {customers.map(customer => (
                  <div 
                    key={customer.id}
                    className="p-3 hover:bg-gray-800 cursor-pointer flex justify-between items-center"
                    onClick={() => setSelectedCustomer(customer)}
                  >
                    <div>
                      <div className="font-medium text-white">{customer.name}</div>
                      <div className="text-sm text-gray-400">{customer.email} • {customer.phone}</div>
                    </div>
                    <ChevronRight className="h-5 w-5 text-gray-400" />
                  </div>
                ))}
              </div>
            )}
            
            <div className="mt-4">
              <button
                type="button"
                className="inline-flex items-center text-sm text-blue-400 hover:text-blue-300"
                onClick={() => setShowNewCustomerForm(true)}
              >
                <UserPlus className="h-4 w-4 mr-1" />
                Create New Guest Profile
              </button>
            </div>
            
            {showNewCustomerForm && (
              <div className="mt-6 p-4 bg-gray-800 rounded-lg border border-gray-700">
                <h4 className="font-medium text-white mb-4">New Guest Information</h4>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                    <input
                      type="text"
                      className="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white"
                      value={newCustomer.name}
                      onChange={(e) => setNewCustomer({...newCustomer, name: e.target.value})}
                      placeholder="John Doe"
                    />
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Email</label>
                      <input
                        type="email"
                        className="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white"
                        value={newCustomer.email}
                        onChange={(e) => setNewCustomer({...newCustomer, email: e.target.value})}
                        placeholder="john@example.com"
                      />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                      <input
                        type="tel"
                        className="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white"
                        value={newCustomer.phone}
                        onChange={(e) => setNewCustomer({...newCustomer, phone: e.target.value})}
                        placeholder="+1234567890"
                      />
                    </div>
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Address</label>
                    <textarea
                      className="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white"
                      rows={2}
                      value={newCustomer.address}
                      onChange={(e) => setNewCustomer({...newCustomer, address: e.target.value})}
                      placeholder="123 Main St, City, Country"
                    />
                  </div>
                  
                  <div className="flex justify-end space-x-3 pt-2">
                    <button
                      type="button"
                      className="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white"
                      onClick={() => setShowNewCustomerForm(false)}
                    >
                      Cancel
                    </button>
                    <button
                      type="button"
                      className="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      onClick={() => {
                        // In a real app, this would save to the database first
                        const customer: Customer = {
                          ...newCustomer,
                          id: `cust-${Date.now()}`,
                          loyaltyPoints: 0
                        };
                        setSelectedCustomer(customer);
                        setShowNewCustomerForm(false);
                      }}
                      disabled={!newCustomer.name || !newCustomer.email || !newCustomer.phone}
                    >
                      Save Guest
                    </button>
                  </div>
                </div>
              </div>
            )}
          </>
        ) : (
          <div className="p-4 bg-gray-800 rounded-lg border border-gray-700">
            <div className="flex justify-between items-start">
              <div>
                <h4 className="font-medium text-white">{selectedCustomer.name}</h4>
                <p className="text-sm text-gray-300">{selectedCustomer.email}</p>
                <p className="text-sm text-gray-400">{selectedCustomer.phone}</p>
                {selectedCustomer.address && (
                  <p className="text-sm text-gray-400 mt-1">{selectedCustomer.address}</p>
                )}
                {selectedCustomer.loyaltyPoints !== undefined && (
                  <div className="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    {selectedCustomer.loyaltyPoints} Loyalty Points
                  </div>
                )}
              </div>
              <button
                type="button"
                className="text-sm text-blue-400 hover:text-blue-300"
                onClick={() => setSelectedCustomer(null)}
              >
                Change
              </button>
            </div>
          </div>
        )}
      </div>
      
      <div className="flex justify-between pt-4">
        <button
          type="button"
          className="inline-flex items-center px-4 py-2 border border-gray-700 text-sm font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          onClick={() => setStep('search')}
        >
          <ChevronRight className="h-4 w-4 transform rotate-180 mr-1" />
          Back
        </button>
        
        <button
          type="button"
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          onClick={() => setStep('services')}
          disabled={!selectedCustomer}
        >
          Next: Additional Services
          <ChevronRight className="ml-2 h-4 w-4" />
        </button>
      </div>
    </div>
  );
  
  const renderServicesStep = (): React.ReactNode => (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium text-white mb-4">Additional Services</h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {mockServices.map(service => (
            <div key={service.id} className="p-4 border border-gray-700 rounded-lg">
              <div className="flex justify-between">
                <div>
                  <h4 className="font-medium text-white">{service.name}</h4>
                  <p className="text-sm text-gray-300">{service.description}</p>
                  <div className="mt-2 text-blue-400 font-medium">
                    ${service.price} {service.chargeType === 'per_stay' ? 'per stay' : service.chargeType === 'per_night' ? 'per night' : 'per person'}
                  </div>
                </div>
                <button
                  type="button"
                  className="h-8 px-3 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md flex items-center"
                  onClick={() => addService(service)}
                >
                  <Plus className="h-4 w-4 mr-1" /> Add
                </button>
              </div>
            </div>
          ))}
        </div>
        
        {selectedServices.length > 0 && (
          <div className="mt-6">
            <h4 className="font-medium text-white mb-3">Selected Services</h4>
            <div className="border border-gray-700 rounded-md overflow-hidden">
              {selectedServices.map(({ service, quantity }) => (
                <div key={service.id} className="p-3 border-b border-gray-700 last:border-b-0 flex justify-between items-center">
                  <div>
                    <div className="font-medium text-white">{service.name}</div>
                    <div className="text-sm text-gray-400">
                      ${service.price} × {quantity} {service.chargeType === 'per_stay' ? 'stay' : service.chargeType === 'per_night' ? 'nights' : 'persons'}
                    </div>
                  </div>
                  <div className="flex items-center">
                    <div className="flex items-center mr-4">
                      <button
                        type="button"
                        className="w-6 h-6 flex items-center justify-center border border-gray-600 rounded-l bg-gray-700 text-white hover:bg-gray-600"
                        onClick={() => updateServiceQuantity(service.id, quantity - 1)}
                      >
                        -
                      </button>
                      <div className="w-10 text-center bg-gray-800 h-6 flex items-center justify-center border-t border-b border-gray-600">
                        {quantity}
                      </div>
                      <button
                        type="button"
                        className="w-6 h-6 flex items-center justify-center border border-gray-600 rounded-r bg-gray-700 text-white hover:bg-gray-600"
                        onClick={() => updateServiceQuantity(service.id, quantity + 1)}
                      >
                        +
                      </button>
                    </div>
                    <button
                      type="button"
                      className="text-red-400 hover:text-red-300"
                      onClick={() => removeService(service.id)}
                    >
                      <X className="h-5 w-5" />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
        
        <div className="mt-6">
          <h4 className="font-medium text-white mb-2">Special Requests</h4>
          <textarea
            className="w-full bg-gray-800 border border-gray-700 rounded-md py-2 px-3 text-white"
            rows={3}
            placeholder="Any special requests or additional information?"
            value={specialRequests}
            onChange={(e) => setSpecialRequests(e.target.value)}
          />
        </div>
        
        <div className="mt-6">
          <h4 className="font-medium text-white mb-2">Voucher Code</h4>
          <div className="flex">
            <input
              type="text"
              className="flex-1 bg-gray-800 border border-gray-700 rounded-l-md py-2 px-3 text-white"
              placeholder="Enter voucher code"
              value={voucherCode}
              onChange={(e) => setVoucherCode(e.target.value)}
            />
            <button
              type="button"
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-r-md"
              onClick={applyVoucher}
            >
              Apply
            </button>
          </div>
          {voucherError && (
            <p className="mt-1 text-sm text-red-400">{voucherError}</p>
          )}
          {voucher && (
            <div className="mt-2 p-2 bg-green-900/30 border border-green-800 text-green-400 text-sm rounded">
              Voucher applied: {voucher.code} ({voucher.discountValue}{voucher.discountType === 'percentage' ? '%' : '$'} off)
            </div>
          )}
        </div>
      </div>
      
      <div className="flex justify-between pt-4">
        <button
          type="button"
          className="inline-flex items-center px-4 py-2 border border-gray-700 text-sm font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          onClick={() => setStep('customer')}
        >
          <ChevronRight className="h-4 w-4 transform rotate-180 mr-1" />
          Back
        </button>
        
        <button
          type="button"
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          onClick={() => setStep('payment')}
        >
          Next: Review & Pay
          <ChevronRight className="ml-2 h-4 w-4" />
        </button>
      </div>
    </div>
  );
  
  const renderPaymentStep = (): React.ReactNode => {
    const nights = Math.ceil((checkOutDate.getTime() - checkInDate.getTime()) / (1000 * 60 * 60 * 24));
    const roomTotal = selectedRoom ? selectedRoom.price * nights : 0;
    
    let servicesTotal = 0;
    selectedServices.forEach(item => {
      if (item.service.chargeType === 'per_stay') {
        servicesTotal += item.service.price * item.quantity;
      } else if (item.service.chargeType === 'per_night') {
        servicesTotal += item.service.price * nights * item.quantity;
      } else {
        servicesTotal += item.service.price * (adults + children) * item.quantity;
      }
    });
    
    const subtotal = roomTotal + servicesTotal;
    const discount = voucher ? 
      (voucher.discountType === 'percentage' 
        ? subtotal * (voucher.discountValue / 100) 
        : voucher.discountValue) 
      : 0;
    
    return (
      <div className="space-y-6">
        <div>
          <h3 className="text-lg font-medium text-white mb-4">Review & Payment</h3>
          
          <div className="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div className="p-4 border-b border-gray-700">
              <h4 className="font-medium text-white">Reservation Details</h4>
            </div>
            
            <div className="p-4 border-b border-gray-700">
              <div className="flex justify-between mb-2">
                <span className="text-gray-400">Room</span>
                <span className="font-medium text-white">{selectedRoom?.type.charAt(0).toUpperCase() + selectedRoom?.type.slice(1)} Room</span>
              </div>
              <div className="flex justify-between text-sm text-gray-400">
                <span>{format(checkInDate, 'MMM d, yyyy')} - {format(checkOutDate, 'MMM d, yyyy')} ({nights} nights)</span>
                <span>${selectedRoom?.price}/night</span>
              </div>
            </div>
            
            {selectedServices.length > 0 && (
              <div className="p-4 border-b border-gray-700">
                <h5 className="font-medium text-white mb-2">Services</h5>
                {selectedServices.map(({ service, quantity }) => (
                  <div key={service.id} className="flex justify-between text-sm mb-1">
                    <span className="text-gray-400">{service.name} × {quantity}</span>
                    <span className="text-gray-300">
                      ${service.price * quantity} 
                      {service.chargeType === 'per_night' && `× ${nights} nights`}
                      {service.chargeType === 'per_person' && `× ${adults + children} persons`}
                    </span>
                  </div>
                ))}
              </div>
            )}
            
            {voucher && (
              <div className="p-4 border-b border-gray-700 bg-green-900/20">
                <div className="flex justify-between">
                  <span className="text-green-400">Voucher Applied ({voucher.code})</span>
                  <span className="text-green-400">-${discount.toFixed(2)}</span>
                </div>
              </div>
            )}
            
            <div className="p-4">
              <div className="flex justify-between text-lg font-medium text-white">
                <span>Total</span>
                <span>${totalAmount.toFixed(2)}</span>
              </div>
              <p className="text-xs text-gray-400 mt-1">Including all taxes and fees</p>
            </div>
          </div>
          
          <div className="mt-6">
            <h4 className="font-medium text-white mb-3">Payment Method</h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              {['credit_card', 'debit_card', 'bank_transfer', 'cash', 'voucher'].map((method) => (
                <div 
                  key={method}
                  className={`p-3 border rounded-lg cursor-pointer transition-colors ${
                    paymentMethod === method 
                      ? 'border-blue-500 bg-blue-900/20' 
                      : 'border-gray-700 hover:border-gray-600'
                  }`}
                  onClick={() => setPaymentMethod(method as PaymentMethod)}
                >
                  <div className="flex items-center">
                    <div className={`w-5 h-5 rounded-full border flex items-center justify-center mr-2 ${
                      paymentMethod === method 
                        ? 'border-blue-500 bg-blue-500' 
                        : 'border-gray-600'
                    }`}>
                      {paymentMethod === method && <Check className="h-3 w-3 text-white" />}
                    </div>
                    <span className="capitalize">
                      {method === 'credit_card' ? 'Credit Card' : 
                       method === 'debit_card' ? 'Debit Card' : 
                       method === 'bank_transfer' ? 'Bank Transfer' : 
                       method === 'voucher' ? 'Voucher' : 'Cash'}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
          
          {specialRequests && (
            <div className="mt-6 p-4 bg-gray-800 rounded-lg border border-gray-700">
              <h4 className="font-medium text-white mb-2">Special Requests</h4>
              <p className="text-gray-300">{specialRequests}</p>
            </div>
          )}
        </div>
        
        <div className="flex justify-between pt-4">
          <button
            type="button"
            className="inline-flex items-center px-4 py-2 border border-gray-700 text-sm font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            onClick={() => setStep('services')}
          >
            <ChevronRight className="h-4 w-4 transform rotate-180 mr-1" />
            Back
          </button>
          
          <div className="space-x-3">
            <button
              type="button"
              className="inline-flex items-center px-4 py-2 border border-gray-700 text-sm font-medium rounded-md text-white bg-gray-800 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              onClick={() => {
                // In a real app, this would save as a draft
                alert('Reservation saved as draft');
              }}
            >
              Save as Draft
            </button>
            
            <button
              type="button"
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              onClick={handleReserve}
            >
              <CheckCircle className="h-4 w-4 mr-1" />
              Confirm Reservation
            </button>
          </div>
        </div>
      </div>
    );
  };
  
  const renderExistingReservations = (): React.ReactNode => (
    <div className="space-y-4">
      <div className="relative">
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Search className="h-5 w-5 text-gray-400" />
        </div>
        <input
          type="text"
          className="block w-full pl-10 pr-3 py-2 border border-gray-700 rounded-md leading-5 bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          placeholder="Search reservations..."
        />
      </div>
      
      <div className="border border-gray-700 rounded-md overflow-hidden">
        {/* In a real app, this would be mapped from actual reservations */}
        <div className="p-4 border-b border-gray-700 hover:bg-gray-800/50 cursor-pointer">
          <div className="flex justify-between items-start">
            <div>
              <div className="font-medium text-white">John Doe</div>
              <div className="text-sm text-gray-400">Room 101 • 2 Adults, 1 Child</div>
              <div className="flex items-center mt-1 text-sm text-gray-400">
                <Calendar className="h-3.5 w-3.5 mr-1" />
                <span>Sep 20 - Sep 25, 2023 (5 nights)</span>
              </div>
            </div>
            <div className="text-right">
              <div className="font-medium text-white">$750.00</div>
              <div className="text-xs px-2 py-0.5 bg-blue-900/50 text-blue-300 rounded-full inline-block mt-1">
                Confirmed
              </div>
            </div>
          </div>
          <div className="flex justify-end mt-3 space-x-2">
            <button
              type="button"
              className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
              onClick={() => handleCheckIn('1')}
            >
              <ClockIcon className="h-3 w-3 mr-1" /> Check In
            </button>
            <button
              type="button"
              className="inline-flex items-center px-3 py-1.5 border border-gray-600 text-xs font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700"
              onClick={() => alert('View details')}
            >
              <FileText className="h-3 w-3 mr-1" /> View
            </button>
          </div>
        </div>
        
        <div className="p-4 border-b border-gray-700 hover:bg-gray-800/50 cursor-pointer">
          <div className="flex justify-between items-start">
            <div>
              <div className="font-medium text-white">Jane Smith</div>
              <div className="text-sm text-gray-400">Room 201 • 2 Adults</div>
              <div className="flex items-center mt-1 text-sm text-gray-400">
                <Calendar className="h-3.5 w-3.5 mr-1" />
                <span>Sep 22 - Sep 24, 2023 (2 nights)</span>
              </div>
            </div>
            <div className="text-right">
              <div className="font-medium text-white">$450.00</div>
              <div className="text-xs px-2 py-0.5 bg-yellow-900/50 text-yellow-300 rounded-full inline-block mt-1">
                Pending Payment
              </div>
            </div>
          </div>
          <div className="flex justify-end mt-3 space-x-2">
            <button
              type="button"
              className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
              onClick={() => {
                setPaymentMethod('credit_card');
                alert('Processing payment...');
              }}
            >
              <DollarSign className="h-3 w-3 mr-1" /> Pay Now
            </button>
            <button
              type="button"
              className="inline-flex items-center px-3 py-1.5 border border-gray-600 text-xs font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700"
              onClick={() => alert('View details')}
            >
              <FileText className="h-3 w-3 mr-1" /> View
            </button>
          </div>
        </div>
        
        <div className="p-4 hover:bg-gray-800/50 cursor-pointer">
          <div className="flex justify-between items-start">
            <div>
              <div className="font-medium text-white">Robert Johnson</div>
              <div className="text-sm text-gray-400">Room 301 • 1 Adult</div>
              <div className="flex items-center mt-1 text-sm text-gray-400">
                <Calendar className="h-3.5 w-3.5 mr-1" />
                <span>Sep 18 - Sep 20, 2023 (2 nights)</span>
              </div>
            </div>
            <div className="text-right">
              <div className="font-medium text-white">$500.00</div>
              <div className="text-xs px-2 py-0.5 bg-green-900/50 text-green-300 rounded-full inline-block mt-1">
                Checked In
              </div>
            </div>
          </div>
          <div className="flex justify-end mt-3 space-x-2">
            <button
              type="button"
              className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700"
              onClick={() => handleCheckOut('3')}
            >
              <ClockIcon className="h-3 w-3 mr-1" /> Check Out
            </button>
            <button
              type="button"
              className="inline-flex items-center px-3 py-1.5 border border-gray-600 text-xs font-medium rounded-md text-gray-300 bg-gray-800 hover:bg-gray-700"
              onClick={() => alert('View details')}
            >
              <FileText className="h-3 w-3 mr-1" /> View
            </button>
          </div>
        </div>
      </div>
    </div>
  );
  
  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl">
      <div className="flex flex-col h-[80vh]">
        <div className="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
          <h2 className="text-xl font-semibold">
            {activeTab === 'new' ? 'New Reservation' : 'Manage Reservations'}
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
              activeTab === 'new'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('new')}
          >
            New Reservation
          </button>
          <button
            type="button"
            className={`px-6 py-3 font-medium ${
              activeTab === 'existing'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('existing')}
          >
            Existing Reservations
          </button>
        </div>
        
        <div className="flex-1 overflow-y-auto pr-2 -mr-2">
          {activeTab === 'new' ? (
            <div className="pr-2">
              {step === 'search' && renderSearchStep()}
              {step === 'customer' && renderCustomerStep()}
              {step === 'services' && renderServicesStep()}
              {step === 'payment' && renderPaymentStep()}
            </div>
          ) : (
            renderExistingReservations()
          )}
        </div>
      </div>
    </Modal>
  );
          {activeTab === 'new' ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-4">
                <h3 className="text-lg font-medium">Guest Information</h3>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                    <input 
                      type="text" 
                      className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white"
                      placeholder="John Doe"
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Email</label>
                      <input 
                        type="email" 
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white"
                        placeholder="john@example.com"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                      <input 
                        type="tel" 
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white"
                        placeholder="+1 (555) 000-0000"
                      />
                    </div>
                  </div>
                </div>
                
                <h3 className="text-lg font-medium mt-6">Stay Details</h3>
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Check-in</label>
                      <div className="relative">
                        <input 
                          type="date" 
                          className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white"
                        />
                        <Calendar className="absolute right-3 top-2.5 h-4 w-4 text-gray-400" />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Check-out</label>
                      <div className="relative">
                        <input 
                          type="date" 
                          className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white"
                        />
                        <Calendar className="absolute right-3 top-2.5 h-4 w-4 text-gray-400" />
                      </div>
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Adults</label>
                      <div className="relative">
                        <select className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white appearance-none">
                          {[1, 2, 3, 4, 5, 6].map(num => (
                            <option key={num} value={num}>{num} {num === 1 ? 'Adult' : 'Adults'}</option>
                          ))}
                        </select>
                        <ChevronDown className="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Children</label>
                      <div className="relative">
                        <select className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white appearance-none">
                          {[0, 1, 2, 3, 4].map(num => (
                            <option key={num} value={num}>{num} {num === 1 ? 'Child' : 'Children'}</option>
                          ))}
                        </select>
                        <ChevronDown className="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="space-y-4">
                <h3 className="text-lg font-medium">Room Selection</h3>
                <div className="space-y-4">
                  <div className="border border-gray-700 rounded-lg p-4">
                    <div className="flex justify-between items-start">
                      <div>
                        <h4 className="font-medium">Deluxe King Room</h4>
                        <p className="text-sm text-gray-400">1 King Bed, Ocean View</p>
                      </div>
                      <span className="text-blue-400 font-medium">$299/night</span>
                    </div>
                    <div className="mt-4 flex items-center justify-between">
                      <div className="flex items-center space-x-2">
                        <Bed className="h-4 w-4 text-gray-400" />
                        <span className="text-sm text-gray-300">1 Room</span>
                      </div>
                      <button className="text-blue-400 hover:text-blue-300 text-sm font-medium">
                        Change Room
                      </button>
                    </div>
                  </div>
                  
                  <div className="bg-gray-800 rounded-lg p-4">
                    <h4 className="font-medium mb-3">Price Summary</h4>
                    <div className="space-y-2 text-sm">
                      <div className="flex justify-between">
                        <span className="text-gray-400">3 nights x $299.00</span>
                        <span>$897.00</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-400">Taxes & Fees</span>
                        <span>$143.52</span>
                      </div>
                      <div className="border-t border-gray-700 my-2"></div>
                      <div className="flex justify-between font-medium">
                        <span>Total</span>
                        <span>$1,040.52</span>
                      </div>
                    </div>
                    
                    <button className="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg flex items-center justify-center space-x-2">
                      <CreditCard className="h-4 w-4" />
                      <span>Book Now</span>
                    </button>
                    
                    <p className="text-xs text-gray-400 mt-3 text-center">
                      No credit card required to book. Pay at the property.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <div className="relative w-64">
                  <input
                    type="text"
                    placeholder="Search reservations..."
                    className="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                </div>
                <div className="flex space-x-2">
                  <select className="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm">
                    <option>All Status</option>
                    <option>Confirmed</option>
                    <option>Checked-in</option>
                    <option>Checked-out</option>
                    <option>Cancelled</option>
                  </select>
                  <button className="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm flex items-center space-x-1">
                    <Plus className="h-4 w-4" />
                    <span>New Reservation</span>
                  </button>
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
                        Check-in
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                        Check-out
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                        Status
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
                              <div className="text-sm text-gray-400">john@example.com</div>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-white">Deluxe King</div>
                          <div className="text-sm text-gray-400">#4201</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-white">Jun 15, 2023</div>
                          <div className="text-sm text-gray-400">3:00 PM</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-white">Jun 18, 2023</div>
                          <div className="text-sm text-gray-400">12:00 PM</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900 text-green-300">
                            Confirmed
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <button className="text-blue-400 hover:text-blue-300 mr-3">View</button>
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
          </div>
        </div>
      ) : (
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <div className="relative w-64">
              <input
                type="text"
                placeholder="Search reservations..."
                className="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
            </div>
            <div className="flex space-x-2">
              <select className="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm">
                <option>All Status</option>
                <option>Confirmed</option>
                <option>Checked-in</option>
                <option>Checked-out</option>
                <option>Cancelled</option>
              </select>
              <button className="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm flex items-center space-x-1">
                <Plus className="h-4 w-4" />
                <span>New Reservation</span>
              </button>
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
                    Check-in
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Check-out
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Status
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
                          <div className="text-sm text-gray-400">john@example.com</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-white">Deluxe King</div>
                      <div className="text-sm text-gray-400">#4201</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-white">Jun 15, 2023</div>
                      <div className="text-sm text-gray-400">3:00 PM</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-white">Jun 18, 2023</div>
                      <div className="text-sm text-gray-400">12:00 PM</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900 text-green-300">
                        Confirmed
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <button className="text-blue-400 hover:text-blue-300 mr-3">View</button>
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
      )}
    </div>
  </div>
</Modal>
);

export default ReservationModal;
