// billing-report.js
// Functional Billing Report with filters + CSV export (date-range based export)

// ---- Dummy data (replace with real data later) ----
const billingData = [
  { id: 'TXN-001', guest: 'John Cruz', room: '101', method: 'Cash', amount: 6500, date: '2025-10-01', status: 'Paid' },
  { id: 'TXN-002', guest: 'Ana Lopez', room: '202', method: 'Card', amount: 4200, date: '2025-10-03', status: 'Paid' },
  { id: 'TXN-003', guest: 'Mark Reyes', room: '305', method: 'GCash', amount: 10500, date: '2025-09-28', status: 'Partial' },
  { id: 'TXN-004', guest: 'Ella Dela Cruz', room: '402', method: 'Cash', amount: 3700, date: '2025-10-06', status: 'Unpaid' },
  { id: 'TXN-005', guest: 'Leo Tan', room: '204', method: 'Bank Transfer', amount: 5000, date: '2025-10-08', status: 'Paid' },
  { id: 'TXN-006', guest: 'Grace Lim', room: '210', method: 'GCash', amount: 2800, date: '2025-10-12', status: 'Paid' }
];

// ---- Helpers ----
const $ = id => document.getElementById(id);

function parseDate(d) {
  if (!d) return null;
  // Keep YYYY-MM-DD strings for comparison
  return d;
}

function formatCurrency(val) {
  return '₱' + Number(val).toLocaleString();
}

// ---- Rendering ----
function renderTable(data) {
  const tbody = $('reportBody');
  tbody.innerHTML = '';

  if (!data || data.length === 0) {
    $('noResults').style.display = 'block';
    updateSummary([]);
    return;
  } else {
    $('noResults').style.display = 'none';
  }

  data.forEach(item => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${item.id}</td>
      <td>${item.guest}</td>
      <td>${item.room}</td>
      <td>${item.method}</td>
      <td>${formatCurrency(item.amount)}</td>
      <td>${item.date}</td>
      <td><span class="status ${item.status}">${item.status}</span></td>
    `;
    tbody.appendChild(tr);
  });

  updateSummary(data);
}

function updateSummary(data) {
  const totalRevenue = data.reduce((sum, r) => sum + (r.status === 'Paid' || r.status === 'Partial' ? r.amount : 0), 0);
  const paid = data.filter(r => r.status === 'Paid').length;
  const partial = data.filter(r => r.status === 'Partial').length;
  const unpaid = data.filter(r => r.status === 'Unpaid').length;

  $('totalRevenue').innerText = formatCurrency(totalRevenue);
  $('totalPaid').innerText = paid;
  $('totalPartial').innerText = partial;
  $('totalUnpaid').innerText = unpaid;
}

// ---- Filters ----
function applyFilters() {
  const from = parseDate($('fromDate').value);
  const to = parseDate($('toDate').value);
  const status = $('statusFilter').value;
  const method = $('methodFilter').value;
  const search = $('searchGuest').value.trim().toLowerCase();

  const filtered = billingData.filter(item => {
    // date match (inclusive)
    let matchDate = true;
    if (from) { matchDate = item.date >= from; }
    if (to) { matchDate = matchDate && item.date <= to; }

    const matchStatus = status === 'All' ? true : item.status === status;
    const matchMethod = method === 'All' ? true : item.method === method;
    const matchSearch = !search ? true : item.guest.toLowerCase().includes(search);

    return matchDate && matchStatus && matchMethod && matchSearch;
  });

  // small fade animation
  const tableCard = document.querySelector('.table-card');
  tableCard.style.opacity = '0.6';
  setTimeout(() => {
    renderTable(filtered);
    tableCard.style.opacity = '1';
  }, 120);
}

// ---- CSV Export ----
function exportCSV() {
  // Use currently visible (filtered) data
  const from = parseDate($('fromDate').value);
  const to = parseDate($('toDate').value);
  const status = $('statusFilter').value;
  const method = $('methodFilter').value;
  const search = $('searchGuest').value.trim().toLowerCase();

  const filtered = billingData.filter(item => {
    let matchDate = true;
    if (from) { matchDate = item.date >= from; }
    if (to) { matchDate = matchDate && item.date <= to; }
    const matchStatus = status === 'All' ? true : item.status === status;
    const matchMethod = method === 'All' ? true : item.method === method;
    const matchSearch = !search ? true : item.guest.toLowerCase().includes(search);
    return matchDate && matchStatus && matchMethod && matchSearch;
  });

  // Build CSV content
  const header = ['Transaction ID','Guest Name','Room','Payment Method','Amount','Date','Status'];
  const rows = filtered.map(r => [r.id, r.guest, r.room, r.method, r.amount, r.date, r.status]);

  // Summary
  const totalRevenue = filtered.reduce((sum, r) => sum + (r.status === 'Paid' || r.status === 'Partial' ? r.amount : 0), 0);
  const paid = filtered.filter(r => r.status === 'Paid').length;
  const partial = filtered.filter(r => r.status === 'Partial').length;
  const unpaid = filtered.filter(r => r.status === 'Unpaid').length;

  // CSV string
  let csv = header.join(',') + '\n';
  rows.forEach(r => {
    // escape comma and quotes if needed
    const safe = r.map(cell => `"${String(cell).replace(/"/g,'""')}"`);
    csv += safe.join(',') + '\n';
  });

  // Add blank line then summary
  csv += '\n';
  csv += `"Total Revenue","${totalRevenue}"\n`;
  csv += `"Paid Transactions","${paid}"\n`;
  csv += `"Partial Transactions","${partial}"\n`;
  csv += `"Unpaid Transactions","${unpaid}"\n`;

  // Filename with date range
  const fnameFrom = from ? from : 'all';
  const fnameTo = to ? to : 'all';
  const filename = `BillingReport_${fnameFrom}_to_${fnameTo}.csv`;

  // download
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);

  showToast('✅ Billing report exported!');
}

// ---- Toast ----
function showToast(msg) {
  const t = $('toast');
  t.innerText = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ---- Init ----
window.addEventListener('DOMContentLoaded', () => {
  renderTable(billingData);
  // wire buttons
  $('btnFilter').addEventListener('click', applyFilters);
  $('btnExport').addEventListener('click', exportCSV);
});
