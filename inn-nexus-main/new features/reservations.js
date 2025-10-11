// Elements
const openModalBtn = document.getElementById("openModalBtn");
const modal = document.getElementById("reservationModal");
const cancelModalBtn = document.getElementById("cancelModalBtn");
const reservationForm = document.getElementById("reservationForm");
const toast = document.getElementById("toast");

// 🪟 Open modal
openModalBtn.addEventListener("click", () => {
  modal.style.display = "flex";
});

// ❌ Close modal (cancel button)
cancelModalBtn.addEventListener("click", () => {
  modal.style.display = "none";
});

// ❌ Close modal when clicking outside content
window.addEventListener("click", (e) => {
  if (e.target === modal) {
    modal.style.display = "none";
  }
});

// ✅ Handle form submit
reservationForm.addEventListener("submit", (e) => {
  e.preventDefault();
  modal.style.display = "none";

  // Show success toast
  toast.classList.add("show");
  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);

  // (Optional) Add new reservation dynamically here
});
