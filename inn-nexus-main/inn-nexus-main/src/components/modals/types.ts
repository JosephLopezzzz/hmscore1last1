// Billing
export interface BillingModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Check In/Out
export interface CheckInOutModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Feedback
export interface FeedbackModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Housekeeping
export interface HousekeepingModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Loyalty Program
export interface LoyaltyProgramModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Maintenance
export interface MaintenanceModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Payments
export interface PaymentsModalProps {
  isOpen: boolean;
  onClose: () => void;
}

// Common Types
export type RoomType = 'single' | 'double' | 'deluxe' | 'suite';
export type PaymentMethod = 'cash' | 'credit_card' | 'debit_card' | 'bank_transfer' | 'voucher';
export type ReservationStatus = 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled';

export interface Customer {
  id: string;
  name: string;
  email: string;
  phone: string;
  address?: string;
  loyaltyPoints?: number;
}

export interface Room {
  id: string;
  number: string;
  type: RoomType;
  price: number;
  maxOccupancy: number;
  available: boolean;
  amenities: string[];
  description?: string;
}

export interface Service {
  id: string;
  name: string;
  description: string;
  price: number;
  chargeType: 'per_stay' | 'per_night' | 'per_person';
}

export interface Voucher {
  id: string;
  code: string;
  discountType: 'percentage' | 'fixed';
  discountValue: number;
  validUntil: Date;
  isUsed: boolean;
}

export interface ReservationItem {
  id: string;
  customer: Customer;
  room: Room;
  checkIn: Date;
  checkOut: Date;
  adults: number;
  children: number;
  status: ReservationStatus;
  services: Array<{ service: Service; quantity: number }>;
  voucher?: Voucher;
  totalAmount: number;
  paidAmount: number;
  paymentMethod?: PaymentMethod;
  specialRequests?: string;
  createdAt: Date;
  updatedAt: Date;
}

// Reservation
export interface ReservationModalProps {
  isOpen: boolean;
  onClose: () => void;
  onReserve?: (reservation: Omit<ReservationItem, 'id' | 'createdAt' | 'updatedAt'>) => void;
  onCheckIn?: (reservationId: string) => void;
  onCheckOut?: (reservationId: string) => void;
  onCancel?: (reservationId: string) => void;
  onPay?: (reservationId: string, amount: number, method: PaymentMethod) => void;
}
