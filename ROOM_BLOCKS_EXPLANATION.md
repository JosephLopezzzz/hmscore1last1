# Room Blocks Functionality - Events & Conferences

## What are Room Blocks?

**Room Blocks** in the Events & Conferences system allow you to reserve multiple rooms for a specific event during its duration. This prevents these rooms from being booked by regular guests and ensures they are available for your event.

## How Room Blocks Work:

### 1. **Event Creation**
- When creating an event, you can select multiple rooms from the "Room Blocks" dropdown
- These rooms will be reserved for the entire duration of your event
- The system shows room availability status (Vacant/Occupied/Reserved) with color coding

### 2. **Room Blocking Process**
- **Pending Events**: Rooms are selected but not yet blocked
- **Confirmed Events**: When you click "Confirm & Block Rooms", the system:
  - Creates actual reservations for each selected room
  - Links these reservations to the event
  - Updates room status to "Reserved" with event name
  - Prevents double-booking during the event period

### 3. **Integration with Reservations**
- Blocked rooms appear in the main Reservations list
- They show as "Confirmed" reservations with "FULLY PAID" status
- Room status changes to "Reserved" with the event title
- These rooms are unavailable for regular bookings during the event

## Benefits:

✅ **Prevents Double-Booking**: Ensures event rooms aren't accidentally booked by guests
✅ **Room Management**: Centralized view of all blocked rooms in Reservations
✅ **Event Organization**: Clear association between events and their reserved rooms
✅ **Conflict Prevention**: System checks for conflicts before confirming events

## Usage Example:

1. **Create Event**: "Tech Conference 2025" for Oct 20-22, 2025
2. **Select Rooms**: Choose rooms 101, 102, 103, 205 for the event
3. **Confirm Event**: Click "Confirm & Block Rooms"
4. **Result**: 
   - Rooms 101, 102, 103, 205 are now reserved for Oct 20-22
   - They appear in Reservations as "Event: Tech Conference 2025"
   - Regular guests cannot book these rooms during this period
   - Rooms show as "Reserved" status in Rooms overview

## Technical Implementation:

- **Database**: Uses `event_reservations` table to link events with reservations
- **Reservations**: Creates actual reservation records for blocked rooms
- **Room Status**: Updates room status to "Reserved" with event information
- **Conflict Detection**: Prevents booking rooms that are already blocked

This system ensures your events have guaranteed room availability while maintaining integration with your existing reservation management system.
