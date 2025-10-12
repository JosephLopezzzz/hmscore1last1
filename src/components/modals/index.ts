// Re-export all modals from a single entry point

// Import all modal components
import BillingModal from './BillingModal';
import CheckInOutModal from './CheckInOutModal';
import FeedbackModal from './FeedbackModal';
import HousekeepingModal from './HousekeepingModal';
import LoyaltyProgramModal from './LoyaltyProgramModal';
import MaintenanceModal from './MaintenanceModal';
import PaymentsModal from './PaymentsModal';
import ReservationModal from './ReservationModal';

// Export all components
export {
  BillingModal,
  CheckInOutModal,
  FeedbackModal,
  HousekeepingModal,
  LoyaltyProgramModal,
  MaintenanceModal,
  PaymentsModal,
  ReservationModal
};

// Export all types from the centralized types file
export type {
  BillingModalProps,
  CheckInOutModalProps,
  FeedbackModalProps,
  HousekeepingModalProps,
  LoyaltyProgramModalProps,
  MaintenanceModalProps,
  PaymentsModalProps,
  ReservationModalProps
} from './types';
