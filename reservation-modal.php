<!-- Empty Reservation Modal -->
<div id="reservationModal" class="fixed inset-0 bg-black/50 z-50 hidden">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-4 border-b">
        <h2 class="text-lg font-semibold">New Reservation</h2>
        <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">
          <i data-lucide="x" class="h-5 w-5"></i>
        </button>
      </div>

      <!-- Modal Body - Empty -->
      <div class="p-6">
        <p class="text-gray-500 text-center">Modal is empty</p>
      </div>

      <!-- Modal Footer -->
      <div class="flex justify-end p-4 border-t">
        <button id="closeModalFooterBtn" class="px-3 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('reservationModal');
  const openModalBtn = document.getElementById('openModalBtn');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const closeModalFooterBtn = document.getElementById('closeModalFooterBtn');

  // Function to open modal
  function openModal() {
    modal.classList.remove('hidden');
  }

  // Function to close modal
  function closeModal() {
    modal.classList.add('hidden');
  }

  // Event listeners
  if (openModalBtn) {
    openModalBtn.addEventListener('click', openModal);
  }

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeModal);
  }

  if (closeModalFooterBtn) {
    closeModalFooterBtn.addEventListener('click', closeModal);
  }

  // Close modal when clicking outside
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeModal();
    }
  });
});
</script>
