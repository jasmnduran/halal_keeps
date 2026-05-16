/**
 * Halal Certifying Body System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modules
    initNavbar();
    initAlerts();
    initModals();
    initNotifications();
    initDropdowns();
    initFileUploads();
    initSidebar();
    initTooltips();
});

// =====================================================
// Navbar Scroll Effect
// =====================================================
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Mobile toggle
    const toggle = document.querySelector('.navbar-toggle');
    const navLinks = document.querySelector('.navbar-nav');
    if (toggle && navLinks) {
        toggle.addEventListener('click', () => {
            navLinks.classList.toggle('show');
        });
    }
}

// =====================================================
// Alerts
// =====================================================
function initAlerts() {
    document.querySelectorAll('.close-alert').forEach(btn => {
        btn.addEventListener('click', function() {
            const alert = this.closest('.alert');
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        });
    });
    
    // Auto-dismiss success alerts
    document.querySelectorAll('.alert-success').forEach(alert => {
        setTimeout(() => {
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });
}

// =====================================================
// Modals
// =====================================================
function initModals() {
    // Open modal buttons
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            openModal(modalId);
        });
    });
    
    // Close modal buttons
    document.querySelectorAll('.modal-close, [data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal();
        });
    });
    
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    document.querySelectorAll('.modal-overlay.active').forEach(modal => {
        modal.classList.remove('active');
    });
    document.body.style.overflow = '';
}

// =====================================================
// Notifications Panel
// =====================================================
function initNotifications() {
    const notifBtn = document.querySelector('.header-notification');
    const notifPanel = document.querySelector('.notification-panel');
    const closePanel = document.querySelector('.close-notif-panel');
    
    if (notifBtn && notifPanel) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpening = !notifPanel.classList.contains('open');
            notifPanel.classList.toggle('open');
            if (isOpening) loadNotifications();
        });
        
        if (closePanel) {
            closePanel.addEventListener('click', () => {
                notifPanel.classList.remove('open');
            });
        }
        
        document.addEventListener('click', function(e) {
            if (!notifPanel.contains(e.target) && !notifBtn.contains(e.target)) {
                notifPanel.classList.remove('open');
            }
        });
    }
}

function loadNotifications() {
    const list = document.getElementById('notifList');
    if (!list) return;

    fetch(BASE_URL + 'api/notifications.php?action=get&limit=15')
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.notifications.length) {
                list.innerHTML = `<div class="empty-state" style="padding:30px 10px">
                    <div class="empty-icon" style="width:50px;height:50px;font-size:1.2rem"><i class="fas fa-bell-slash"></i></div>
                    <p>No new notifications</p>
                </div>`;
                return;
            }

            // Update badge
            const badge = document.querySelector('.notif-count');
            if (badge) {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }

            const typeIcon = { success: 'check-circle', warning: 'exclamation-triangle', action_required: 'exclamation-circle', info: 'info-circle' };
            list.innerHTML = data.notifications.map(n => `
                <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}" onclick="handleNotifClick(this, '${n.link || ''}')">
                    <div class="notif-icon"><i class="fas fa-${typeIcon[n.type] || 'bell'}"></i></div>
                    <div class="notif-content">
                        <div class="notif-title">${n.title}</div>
                        <div class="notif-message">${n.message}</div>
                        <div class="notif-time" style="font-size:0.75rem;color:var(--neutral-400);margin-top:2px">${n.created_at}</div>
                    </div>
                </div>`).join('');
        })
        .catch(() => {});
}

function handleNotifClick(el, link) {
    el.classList.remove('unread');
    const id = el.getAttribute('data-id');
    if (id) markNotificationRead(id);
    if (link) window.location.href = link;
}

function markNotificationRead(id) {
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('notification_id', id);
    fetch(BASE_URL + 'api/notifications.php', {
        method: 'POST',
        body: formData
    }).catch(err => console.error('Error:', err));
}

// =====================================================
// Dropdowns
// =====================================================
function initDropdowns() {
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (trigger && menu) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });
                menu.classList.toggle('show');
            });
        }
    });
    
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    });
}

// =====================================================
// File Uploads
// =====================================================
function initFileUploads() {
    document.querySelectorAll('.file-upload').forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        const label = upload.querySelector('.file-label');
        
        if (input) {
            input.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const names = Array.from(this.files).map(f => f.name).join(', ');
                    if (label) label.textContent = names;
                    upload.style.borderColor = 'var(--primary-400)';
                    upload.style.background = 'var(--primary-50)';
                }
            });
        }
    });
}

// =====================================================
// Sidebar Mobile Toggle
// =====================================================
function initSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
}

// =====================================================
// Tooltips
// =====================================================
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = text;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.opacity = '1';
            
            this._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// =====================================================
// Toast Notifications
// =====================================================
function showToast(message, type = 'success', duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-times-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="${icons[type] || icons.info}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;opacity:0.6">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// =====================================================
// Form Validation
// =====================================================
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        removeFieldError(field);
        
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else if (field.type === 'email' && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email');
            isValid = false;
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    field.style.borderColor = 'var(--danger)';
    const error = document.createElement('div');
    error.className = 'field-error';
    error.style.color = 'var(--danger)';
    error.style.fontSize = '0.8rem';
    error.style.marginTop = '4px';
    error.textContent = message;
    field.parentElement.appendChild(error);
}

function removeFieldError(field) {
    field.style.borderColor = '';
    const error = field.parentElement.querySelector('.field-error');
    if (error) error.remove();
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// =====================================================
// AJAX Helper
// =====================================================
async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' },
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showToast('An error occurred. Please try again.', 'error');
        return null;
    }
}

// =====================================================
// Confirm Dialog
// =====================================================
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// =====================================================
// Role Card Selection
// =====================================================
function initRoleSelection() {
    document.querySelectorAll('.role-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const roleId = this.getAttribute('data-role-id');
            const roleInput = document.getElementById('selected_role');
            if (roleInput) roleInput.value = roleId;
            
            // Show/hide role-specific fields
            showRoleFields(roleId);
        });
    });
}

function showRoleFields(roleId) {
    document.querySelectorAll('.role-fields').forEach(f => f.style.display = 'none');
    const fields = document.getElementById('role-fields-' + roleId);
    if (fields) fields.style.display = 'block';
}

// Global BASE_URL
const BASE_URL = '/halal_final/';
