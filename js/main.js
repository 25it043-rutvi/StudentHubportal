/**
 * StudentHub Portal - Main Interactive JavaScript
 * Practical 4: DOM Manipulation, Event Handling, Theme Switcher,
 * Collapsible FAQ, Modal, Carousel, and Notification Banner.
 */

document.addEventListener('DOMContentLoaded', () => {
  initThemeSwitcher();
  initMobileNavigation();
  initAccordion();
  initSlider();
  initModal();
  initNotificationToast();
});

/* -------------------------------------------------------------
 * 1. Dark/Light Theme Switcher with localStorage Persistence
 * ------------------------------------------------------------- */
function initThemeSwitcher() {
  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  const storedTheme = localStorage.getItem('studenthub_theme') || 'light';
  
  // Set initial theme
  document.documentElement.setAttribute('data-theme', storedTheme);
  updateThemeButtonUI(themeToggleBtn, storedTheme);

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('studenthub_theme', newTheme);
      updateThemeButtonUI(themeToggleBtn, newTheme);
      
      showNotification('Theme Changed', `Switched to ${newTheme} mode.`, 'info');
    });
  }
}

function updateThemeButtonUI(btn, theme) {
  if (!btn) return;
  if (theme === 'dark') {
    btn.innerHTML = '☀️ Light';
    btn.setAttribute('aria-label', 'Switch to light mode');
  } else {
    btn.innerHTML = '🌙 Dark';
    btn.setAttribute('aria-label', 'Switch to dark mode');
  }
}

/* -------------------------------------------------------------
 * 2. Mobile Hamburger Navigation
 * ------------------------------------------------------------- */
function initMobileNavigation() {
  const hamburgerBtn = document.getElementById('hamburger-btn');
  const mainNav = document.getElementById('main-nav');

  if (hamburgerBtn && mainNav) {
    hamburgerBtn.addEventListener('click', () => {
      const isOpen = mainNav.classList.toggle('open');
      hamburgerBtn.setAttribute('aria-expanded', isOpen);
      hamburgerBtn.innerHTML = isOpen ? '✕' : '☰';
    });

    // Close mobile nav on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && mainNav.classList.contains('open')) {
        mainNav.classList.remove('open');
        hamburgerBtn.setAttribute('aria-expanded', false);
        hamburgerBtn.innerHTML = '☰';
      }
    });
  }
}

/* -------------------------------------------------------------
 * 3. Collapsible FAQ Accordion (Practical 4)
 * ------------------------------------------------------------- */
function initAccordion() {
  const accordionHeaders = document.querySelectorAll('.accordion-header');

  accordionHeaders.forEach(header => {
    header.addEventListener('click', () => {
      const currentItem = header.parentElement;
      const isOpen = currentItem.classList.contains('active');

      // Optional single-open behavior: close other accordion items in the same container
      const container = currentItem.parentElement;
      if (container) {
        container.querySelectorAll('.accordion-item').forEach(item => {
          item.classList.remove('active');
          const h = item.querySelector('.accordion-header');
          if (h) h.setAttribute('aria-expanded', 'false');
        });
      }

      if (!isOpen) {
        currentItem.classList.add('active');
        header.setAttribute('aria-expanded', 'true');
      } else {
        currentItem.classList.remove('active');
        header.setAttribute('aria-expanded', 'false');
      }
    });
  });
}

/* -------------------------------------------------------------
 * 4. Image / Content Slider Carousel (Practical 4)
 * ------------------------------------------------------------- */
function initSlider() {
  const sliderTrack = document.getElementById('slider-track');
  if (!sliderTrack) return;

  const slides = sliderTrack.querySelectorAll('.slide');
  const prevBtn = document.getElementById('slider-prev');
  const nextBtn = document.getElementById('slider-next');
  const dotsContainer = document.getElementById('slider-dots');

  if (slides.length === 0) return;

  let currentIndex = 0;
  let autoplayTimer = null;

  // Generate dots if dots container exists
  if (dotsContainer) {
    dotsContainer.innerHTML = '';
    slides.forEach((_, idx) => {
      const dot = document.createElement('div');
      dot.className = `dot ${idx === 0 ? 'active' : ''}`;
      dot.addEventListener('click', () => goToSlide(idx));
      dotsContainer.appendChild(dot);
    });
  }

  function updateSlidePosition() {
    sliderTrack.style.transform = `translateX(-${currentIndex * 100}%)`;
    if (dotsContainer) {
      const dots = dotsContainer.querySelectorAll('.dot');
      dots.forEach((dot, idx) => {
        dot.classList.toggle('active', idx === currentIndex);
      });
    }
  }

  function goToSlide(index) {
    currentIndex = index;
    updateSlidePosition();
    resetAutoplay();
  }

  function nextSlide() {
    currentIndex = (currentIndex + 1) % slides.length;
    updateSlidePosition();
  }

  function prevSlide() {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    updateSlidePosition();
  }

  if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetAutoplay(); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetAutoplay(); });

  function startAutoplay() {
    autoplayTimer = setInterval(nextSlide, 5000);
  }

  function resetAutoplay() {
    if (autoplayTimer) clearInterval(autoplayTimer);
    startAutoplay();
  }

  startAutoplay();
}

/* -------------------------------------------------------------
 * 5. Accessible Modal Popup (Practical 4)
 * ------------------------------------------------------------- */
function initModal() {
  const modalOverlay = document.getElementById('custom-modal');
  if (!modalOverlay) return;

  const closeBtns = modalOverlay.querySelectorAll('[data-close-modal]');
  closeBtns.forEach(btn => {
    btn.addEventListener('click', () => closeModal());
  });

  // Close when clicking outside modal box
  modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
  });

  // Close with Esc key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
      closeModal();
    }
  });
}

function openModal(title, contentHtml) {
  const modalOverlay = document.getElementById('custom-modal');
  if (!modalOverlay) return;

  const titleEl = modalOverlay.querySelector('#modal-title');
  const bodyEl = modalOverlay.querySelector('#modal-body');

  if (titleEl) titleEl.textContent = title;
  if (bodyEl) bodyEl.innerHTML = contentHtml;

  modalOverlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  const modalOverlay = document.getElementById('custom-modal');
  if (!modalOverlay) return;

  modalOverlay.classList.remove('active');
  document.body.style.overflow = '';
}

// Make openModal and closeModal globally accessible
window.openModal = openModal;
window.closeModal = closeModal;

/* -------------------------------------------------------------
 * 6. Notification Toast Banner (Practical 4)
 * ------------------------------------------------------------- */
function initNotificationToast() {
  let toast = document.getElementById('notification-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'notification-toast';
    toast.className = 'notification-banner';
    toast.innerHTML = `
      <div class="notification-content">
        <h4 id="toast-title">Notification</h4>
        <p id="toast-message">Message text</p>
      </div>
      <button class="notification-close" id="toast-close" aria-label="Dismiss">&times;</button>
    `;
    document.body.appendChild(toast);

    document.getElementById('toast-close').addEventListener('click', () => {
      toast.classList.remove('show');
    });
  }
}

function showNotification(title, message, type = 'info', duration = 4000) {
  const toast = document.getElementById('notification-toast');
  if (!toast) return;

  const titleEl = document.getElementById('toast-title');
  const msgEl = document.getElementById('toast-message');

  titleEl.textContent = title;
  msgEl.textContent = message;

  toast.className = `notification-banner show ${type}`;

  if (window.toastTimer) clearTimeout(window.toastTimer);
  window.toastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, duration);
}

window.showNotification = showNotification;
