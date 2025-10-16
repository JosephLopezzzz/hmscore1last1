<div id="eventModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
  <div class="bg-card text-card-foreground border border-border rounded-lg w-full max-w-3xl mx-4 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-xl font-semibold">Event / Conference</h3>
      <button id="closeEventModal" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div id="eventView" class="hidden">
      <div id="eventDetail" class="space-y-2"></div>
      <div class="mt-4 flex gap-2">
        <button id="confirmEventBtn" class="h-9 px-3 rounded-md bg-primary text-primary-foreground">Confirm & Block Rooms</button>
        <button id="deleteEventBtn" class="h-9 px-3 rounded-md border hover:bg-muted">Cancel Event</button>
      </div>
    </div>
    <form id="eventForm" class="space-y-3">
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-muted-foreground">Title</label>
          <input id="evtTitle" class="h-10 w-full rounded-md border bg-background px-3 text-sm" required />
        </div>
        <div>
          <label class="text-xs text-muted-foreground">Organizer Name</label>
          <input id="evtOrg" class="h-10 w-full rounded-md border bg-background px-3 text-sm" required />
        </div>
        <div>
          <label class="text-xs text-muted-foreground">Organizer Contact</label>
          <input id="evtContact" class="h-10 w-full rounded-md border bg-background px-3 text-sm" />
        </div>
        <div>
          <label class="text-xs text-muted-foreground">Attendees Expected</label>
          <input id="evtAttendees" type="number" min="0" class="h-10 w-full rounded-md border bg-background px-3 text-sm" />
        </div>
        <div>
          <label class="text-xs text-muted-foreground">Start</label>
          <input id="evtStart" type="datetime-local" class="h-10 w-full rounded-md border bg-background px-3 text-sm" required />
        </div>
        <div>
          <label class="text-xs text-muted-foreground">End</label>
          <input id="evtEnd" type="datetime-local" class="h-10 w-full rounded-md border bg-background px-3 text-sm" required />
        </div>
        <div>
          <label class="text-xs text-muted-foreground">Setup Type</label>
          <select id="evtSetup" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
            <option value="theatre">Theatre</option>
            <option value="classroom">Classroom</option>
            <option value="u-shape">U-Shape</option>
            <option value="boardroom">Boardroom</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-muted-foreground">Price Estimate (₱)</label>
          <input id="evtPrice" type="number" step="0.01" min="0" class="h-10 w-full rounded-md border bg-background px-3 text-sm" />
        </div>
      </div>
      <div>
        <label class="text-xs text-muted-foreground">Description / Notes</label>
        <textarea id="evtDesc" rows="2" class="w-full rounded-md border bg-background px-3 text-sm"></textarea>
      </div>
      <div>
        <label class="text-xs text-muted-foreground">Rooms to block (comma-separated room IDs)</label>
        <input id="evtRooms" class="h-10 w-full rounded-md border bg-background px-3 text-sm" placeholder="e.g., 101,102,203" />
      </div>
      <div class="flex gap-2 pt-2">
        <button type="submit" class="flex-1 h-10 rounded-md bg-primary text-primary-foreground">Save Event</button>
        <button type="button" id="cancelEventForm" class="flex-1 h-10 rounded-md border hover:bg-muted">Cancel</button>
      </div>
      <p id="evtError" class="text-danger text-sm hidden"></p>
    </form>
  </div>
</div>

<script>
  (function(){
    const modal = document.getElementById('eventModal');
    const closeBtn = document.getElementById('closeEventModal');
    const form = document.getElementById('eventForm');
    const view = document.getElementById('eventView');
    const detail = document.getElementById('eventDetail');
    const errorEl = document.getElementById('evtError');
    const confirmBtn = document.getElementById('confirmEventBtn');
    const deleteBtn = document.getElementById('deleteEventBtn');
    const cancelFormBtn = document.getElementById('cancelEventForm');

    function showModal(){ modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function hideModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }

    window.addEventListener('open-event-form', () => {
      form.reset(); errorEl.classList.add('hidden');
      view.classList.add('hidden'); form.classList.remove('hidden');
      showModal();
    });

    window.addEventListener('open-event-detail', async (e) => {
      const id = e.detail.id;
      const res = await fetch('api/index.php/events/' + id);
      const json = await res.json();
      const evt = json.data;
      if (!evt) return;
      form.classList.add('hidden'); view.classList.remove('hidden');
      detail.innerHTML = `
        <div class="grid md:grid-cols-2 gap-3">
          <div><p class="text-sm text-muted-foreground">Title</p><p class="font-medium">${evt.title}</p></div>
          <div><p class="text-sm text-muted-foreground">Organizer</p><p class="font-medium">${evt.organizer_name}</p></div>
          <div><p class="text-sm text-muted-foreground">Date</p><p class="font-medium">${new Date(evt.start_datetime).toLocaleString()} - ${new Date(evt.end_datetime).toLocaleString()}</p></div>
          <div><p class="text-sm text-muted-foreground">Setup</p><p class="font-medium">${evt.setup_type}</p></div>
          <div><p class="text-sm text-muted-foreground">Attendees</p><p class="font-medium">${evt.attendees_expected ?? 0}</p></div>
          <div><p class="text-sm text-muted-foreground">Rooms</p><p class="font-medium">${(evt.room_blocks||[]).join(', ')}</p></div>
        </div>
        <p class="mt-2">${evt.description||''}</p>
      `;
      confirmBtn.onclick = async () => {
        await fetch('api/index.php/events/'+id+'/confirm', { method: 'POST' });
        hideModal(); location.reload();
      };
      deleteBtn.onclick = async () => {
        if (!confirm('Cancel/delete this event?')) return;
        await fetch('api/index.php/events/'+id, { method: 'DELETE' });
        hideModal(); location.reload();
      };
      showModal();
    });

    closeBtn.addEventListener('click', hideModal);
    cancelFormBtn.addEventListener('click', hideModal);

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      errorEl.classList.add('hidden');
      const payload = {
        title: document.getElementById('evtTitle').value.trim(),
        organizer_name: document.getElementById('evtOrg').value.trim(),
        organizer_contact: document.getElementById('evtContact').value.trim(),
        start_datetime: document.getElementById('evtStart').value,
        end_datetime: document.getElementById('evtEnd').value,
        attendees_expected: Number(document.getElementById('evtAttendees').value||0),
        setup_type: document.getElementById('evtSetup').value,
        price_estimate: Number(document.getElementById('evtPrice').value||0),
        description: document.getElementById('evtDesc').value,
        room_blocks: document.getElementById('evtRooms').value.split(',').map(s=>parseInt(s.trim(),10)).filter(n=>!isNaN(n)),
        status: 'pending'
      };
      if (!payload.title || !payload.organizer_name || !payload.start_datetime || !payload.end_datetime) {
        errorEl.textContent = 'Please complete required fields'; errorEl.classList.remove('hidden'); return;
      }
      if (new Date(payload.start_datetime) >= new Date(payload.end_datetime)) {
        errorEl.textContent = 'Start must be before End'; errorEl.classList.remove('hidden'); return;
      }
      const resp = await fetch('api/index.php/events', { method: 'POST', body: JSON.stringify(payload) });
      const json = await resp.json();
      if (!json.ok) { errorEl.textContent = json.message || 'Failed to create event'; errorEl.classList.remove('hidden'); return; }
      hideModal(); location.reload();
    });
  })();
</script>


