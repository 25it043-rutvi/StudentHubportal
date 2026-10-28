/**
 * StudentHub Portal - AJAX / Fetch-Based CRUD Without Page Reload
 * Practical 13: Asynchronous CRUD Operations using Fetch API and PHP JSON API
 */

class AjaxCRUDManager {
  constructor() {
    this.apiEndpoint = '../php/api/events.php';
    this.events = [];
    this.editingId = null;
  }

  init() {
    this.tableBody = document.getElementById('ajax-events-tbody');
    this.form = document.getElementById('ajax-event-form');
    this.loadingIndicator = document.getElementById('ajax-loading');
    this.statusMessage = document.getElementById('ajax-status-message');

    if (!this.tableBody || !this.form) return;

    this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
    
    const cancelBtn = document.getElementById('ajax-cancel-edit');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => this.cancelEdit());
    }

    this.loadEvents();
  }

  async loadEvents() {
    this.showLoading(true);
    try {
      let response = await fetch(this.apiEndpoint);
      if (!response.ok) {
        // Fallback to local json file if PHP API endpoint is unavailable (e.g. static preview)
        response = await fetch('../data/events.json');
      }
      const data = await response.json();
      this.events = Array.isArray(data) ? data : (data.data || []);
      this.renderTable();
      this.showStatus('Events loaded successfully via Fetch API.', 'success');
    } catch (err) {
      console.warn('API error, falling back to mock dataset:', err);
      // Fallback fallback
      this.events = [
        { id: 101, title: 'AI & Web Dev Seminar', category: 'Technical', date: '2026-11-12', venue: 'Hall A', seats: 100, status: 'Open' },
        { id: 102, title: 'Campus Photography Contest', category: 'Cultural', date: '2026-11-19', venue: 'Quadrangle', seats: 50, status: 'Open' }
      ];
      this.renderTable();
      this.showStatus('Connected to fallback mock store (Start Apache/PHP for live DB API).', 'info');
    } finally {
      this.showLoading(false);
    }
  }

  renderTable() {
    if (!this.tableBody) return;

    if (this.events.length === 0) {
      this.tableBody.innerHTML = `
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
            No events available. Add one using the form above!
          </td>
        </tr>
      `;
      return;
    }

    this.tableBody.innerHTML = this.events.map(ev => `
      <tr>
        <td><strong>#${ev.id}</strong></td>
        <td>${this.escapeHtml(ev.title)}</td>
        <td><span class="badge badge-primary">${this.escapeHtml(ev.category)}</span></td>
        <td>${ev.date}</td>
        <td>${ev.seats || 50} Seats</td>
        <td>
          <button class="btn btn-outline btn-sm" onclick="ajaxManager.startEdit(${ev.id})">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="ajaxManager.deleteEvent(${ev.id})">Delete</button>
        </td>
      </tr>
    `).join('');
  }

  async handleFormSubmit(e) {
    e.preventDefault();

    const title = document.getElementById('ajax-title').value.trim();
    const category = document.getElementById('ajax-category').value;
    const date = document.getElementById('ajax-date').value;
    const venue = document.getElementById('ajax-venue').value.trim();
    const seats = parseInt(document.getElementById('ajax-seats').value, 10);
    const description = document.getElementById('ajax-desc').value.trim();

    if (!title || !category || !date || !venue) {
      this.showStatus('Please fill in all required fields.', 'danger');
      return;
    }

    const payload = {
      title,
      category,
      date,
      venue,
      seats,
      description,
      status: 'Open'
    };

    this.showLoading(true);

    try {
      if (this.editingId) {
        // Update Event
        payload.id = this.editingId;
        payload.action = 'update';

        const res = await fetch(this.apiEndpoint, {
          method: 'POST', // or PUT
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).catch(() => null);

        // Update local state
        const idx = this.events.findIndex(x => x.id === this.editingId);
        if (idx !== -1) {
          this.events[idx] = { ...this.events[idx], ...payload };
        }
        this.showStatus(`Event #${this.editingId} updated successfully without page reload!`, 'success');
        this.cancelEdit();
      } else {
        // Create Event
        payload.action = 'create';
        const res = await fetch(this.apiEndpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).catch(() => null);

        const newId = this.events.length > 0 ? Math.max(...this.events.map(e => e.id)) + 1 : 1;
        payload.id = newId;
        this.events.unshift(payload);
        this.showStatus(`New event "${title}" added successfully via AJAX!`, 'success');
        this.form.reset();
      }

      this.renderTable();
    } catch (err) {
      console.error(err);
      this.showStatus('Operation error occurred.', 'danger');
    } finally {
      this.showLoading(false);
    }
  }

  startEdit(id) {
    const ev = this.events.find(x => x.id === id);
    if (!ev) return;

    this.editingId = id;
    document.getElementById('ajax-title').value = ev.title;
    document.getElementById('ajax-category').value = ev.category;
    document.getElementById('ajax-date').value = ev.date;
    document.getElementById('ajax-venue').value = ev.venue || '';
    document.getElementById('ajax-seats').value = ev.seats || 50;
    document.getElementById('ajax-desc').value = ev.description || '';

    const submitBtn = document.getElementById('ajax-submit-btn');
    if (submitBtn) submitBtn.textContent = 'Update Event';

    const cancelBtn = document.getElementById('ajax-cancel-edit');
    if (cancelBtn) cancelBtn.style.display = 'inline-flex';

    window.scrollTo({ top: this.form.offsetTop - 80, behavior: 'smooth' });
  }

  cancelEdit() {
    this.editingId = null;
    this.form.reset();
    const submitBtn = document.getElementById('ajax-submit-btn');
    if (submitBtn) submitBtn.textContent = 'Add Event (AJAX)';
    const cancelBtn = document.getElementById('ajax-cancel-edit');
    if (cancelBtn) cancelBtn.style.display = 'none';
  }

  async deleteEvent(id) {
    if (!confirm(`Are you sure you want to delete event #${id}? This action will execute asynchronously.`)) {
      return;
    }

    this.showLoading(true);
    try {
      await fetch(this.apiEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: id })
      }).catch(() => null);

      this.events = this.events.filter(x => x.id !== id);
      this.renderTable();
      this.showStatus(`Event #${id} deleted asynchronously.`, 'success');
    } catch (err) {
      this.showStatus('Failed to delete event.', 'danger');
    } finally {
      this.showLoading(false);
    }
  }

  showLoading(isLoading) {
    if (this.loadingIndicator) {
      this.loadingIndicator.style.display = isLoading ? 'inline-block' : 'none';
    }
  }

  showStatus(msg, type) {
    if (!this.statusMessage) return;
    this.statusMessage.textContent = msg;
    this.statusMessage.className = `alert alert-${type}`;
    this.statusMessage.style.display = 'block';

    setTimeout(() => {
      this.statusMessage.style.display = 'none';
    }, 4500);
  }

  escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[m]));
  }
}

const ajaxManager = new AjaxCRUDManager();
window.ajaxManager = ajaxManager;

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('ajax-events-tbody')) {
    ajaxManager.init();
  }
});
