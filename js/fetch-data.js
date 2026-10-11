/**
 * StudentHub Portal - External JSON Data Rendering
 * Practical 6: Fetch API, Dynamic Rendering, Search, Filter, Sort, and Pagination
 */

class DataRenderer {
  constructor() {
    this.eventsData = [];
    this.filteredEvents = [];
    this.currentPage = 1;
    this.itemsPerPage = 4;
    this.currentCategory = 'All';
    this.searchTerm = '';
    this.sortBy = 'date-asc';
  }

  async initEventsCatalog() {
    const container = document.getElementById('events-container');
    if (!container) return;

    this.showLoading(container);

    try {
      // Determine path depending on whether inside pages/ or root
      const jsonPath = window.location.pathname.includes('/pages/') ? '../data/events.json' : 'data/events.json';
      const response = await fetch(jsonPath);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      this.eventsData = await response.json();
      this.filteredEvents = [...this.eventsData];

      this.setupEventListeners();
      this.applyFilters();
    } catch (error) {
      console.error('Fetch error:', error);
      this.showError(container, 'Failed to load events. Please check your data connection or JSON file path.');
    }
  }

  setupEventListeners() {
    const searchInput = document.getElementById('event-search');
    const categorySelect = document.getElementById('event-category');
    const sortSelect = document.getElementById('event-sort');

    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        this.searchTerm = e.target.value.toLowerCase().trim();
        this.currentPage = 1;
        this.applyFilters();
      });
    }

    if (categorySelect) {
      categorySelect.addEventListener('change', (e) => {
        this.currentCategory = e.target.value;
        this.currentPage = 1;
        this.applyFilters();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', (e) => {
        this.sortBy = e.target.value;
        this.applyFilters();
      });
    }
  }

  applyFilters() {
    let result = [...this.eventsData];

    // 1. Category Filter
    if (this.currentCategory !== 'All') {
      result = result.filter(item => item.category === this.currentCategory);
    }

    // 2. Search Query Filter
    if (this.searchTerm) {
      result = result.filter(item => 
        item.title.toLowerCase().includes(this.searchTerm) ||
        item.description.toLowerCase().includes(this.searchTerm) ||
        item.venue.toLowerCase().includes(this.searchTerm)
      );
    }

    // 3. Sorting
    result.sort((a, b) => {
      if (this.sortBy === 'date-asc') {
        return new Date(a.date) - new Date(b.date);
      } else if (this.sortBy === 'date-desc') {
        return new Date(b.date) - new Date(a.date);
      } else if (this.sortBy === 'title-asc') {
        return a.title.localeCompare(b.title);
      } else if (this.sortBy === 'seats-desc') {
        return b.seats - a.seats;
      }
      return 0;
    });

    this.filteredEvents = result;
    this.renderEvents();
    this.renderPagination();
  }

  renderEvents() {
    const container = document.getElementById('events-container');
    if (!container) return;

    if (this.filteredEvents.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: var(--bg-card); border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
          <div style="font-size: 2.5rem; margin-bottom: 10px;">🔍</div>
          <h3>No Events Found</h3>
          <p style="color: var(--text-muted);">Try adjusting your search query or filter criteria.</p>
        </div>
      `;
      return;
    }

    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const paginatedItems = this.filteredEvents.slice(startIndex, startIndex + this.itemsPerPage);

    container.innerHTML = paginatedItems.map(event => `
      <article class="card event-card" data-id="${event.id}">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
          <span class="badge ${this.getCategoryBadgeClass(event.category)}">${event.category}</span>
          <span class="badge ${event.status === 'Open' ? 'badge-success' : 'badge-danger'}">${event.status}</span>
        </div>
        <h3>${this.escapeHtml(event.title)}</h3>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 14px;">
          📅 <strong>Date:</strong> ${event.date} | ⏰ ${event.time}<br>
          📍 <strong>Venue:</strong> ${this.escapeHtml(event.venue)}
        </p>
        <p style="flex: 1;">${this.escapeHtml(event.description)}</p>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 14px;">
          <small style="color: var(--text-muted);">👥 ${event.registered} / ${event.seats} Registered</small>
          <button class="btn btn-primary btn-sm" onclick="eventRenderer.viewEventDetails(${event.id})">
            View Details
          </button>
        </div>
      </article>
    `).join('');
  }

  renderPagination() {
    const paginationContainer = document.getElementById('events-pagination');
    if (!paginationContainer) return;

    const totalPages = Math.ceil(this.filteredEvents.length / this.itemsPerPage);
    if (totalPages <= 1) {
      paginationContainer.innerHTML = '';
      return;
    }

    let html = `
      <button ${this.currentPage === 1 ? 'disabled' : ''} onclick="eventRenderer.changePage(${this.currentPage - 1})">
        &laquo; Prev
      </button>
    `;

    for (let i = 1; i <= totalPages; i++) {
      html += `
        <button class="${i === this.currentPage ? 'active' : ''}" onclick="eventRenderer.changePage(${i})">
          ${i}
        </button>
      `;
    }

    html += `
      <button ${this.currentPage === totalPages ? 'disabled' : ''} onclick="eventRenderer.changePage(${this.currentPage + 1})">
        Next &raquo;
      </button>
    `;

    paginationContainer.innerHTML = html;
  }

  changePage(newPage) {
    this.currentPage = newPage;
    this.renderEvents();
    this.renderPagination();
    window.scrollTo({ top: 300, behavior: 'smooth' });
  }

  viewEventDetails(eventId) {
    const event = this.eventsData.find(e => e.id === eventId);
    if (!event) return;

    const contentHtml = `
      <div style="margin-bottom: 16px;">
        <span class="badge ${this.getCategoryBadgeClass(event.category)}">${event.category}</span>
        <span class="badge ${event.status === 'Open' ? 'badge-success' : 'badge-danger'}" style="margin-left: 8px;">${event.status}</span>
      </div>
      <p style="margin-bottom: 12px; font-size: 1.05rem;">${this.escapeHtml(event.description)}</p>
      <div style="background: var(--bg-subtle); padding: 14px; border-radius: var(--radius-sm); margin-bottom: 16px;">
        <p><strong>📅 Date:</strong> ${event.date}</p>
        <p><strong>⏰ Time:</strong> ${event.time}</p>
        <p><strong>📍 Venue:</strong> ${this.escapeHtml(event.venue)}</p>
        <p><strong>🏛️ Organizer:</strong> ${this.escapeHtml(event.organizer || 'Student Committee')}</p>
        <p><strong>👥 Availability:</strong> ${event.registered} seats booked out of ${event.seats}</p>
      </div>
      ${event.status === 'Open' ? `
        <a href="register.html" class="btn btn-primary btn-block">Register for this Event</a>
      ` : `
        <p style="color: var(--danger); font-weight: 600;">Registrations are closed for this event.</p>
      `}
    `;

    if (window.openModal) {
      window.openModal(event.title, contentHtml);
    } else {
      alert(`${event.title}\n${event.description}`);
    }
  }

  getCategoryBadgeClass(category) {
    switch (category) {
      case 'Technical': return 'badge-primary';
      case 'Cultural': return 'badge-warning';
      case 'Sports': return 'badge-danger';
      case 'Workshop': return 'badge-success';
      default: return 'badge-primary';
    }
  }

  showLoading(container) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
        <div style="font-size: 2rem;">⏳</div>
        <p style="color: var(--text-muted); margin-top: 10px;">Loading events from JSON...</p>
      </div>
    `;
  }

  showError(container, message) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: var(--danger-bg); border-radius: var(--radius-md); color: var(--danger);">
        <h4>Error Loading Data</h4>
        <p>${message}</p>
      </div>
    `;
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

// Global instance
const eventRenderer = new DataRenderer();
window.eventRenderer = eventRenderer;

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('events-container')) {
    eventRenderer.initEventsCatalog();
  }
});
