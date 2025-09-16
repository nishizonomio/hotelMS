/* =========================================================
   crm.js — Single-file CRM JS (refactored & ready-to-paste)
   ========================================================= */

const API_BASE_URL = 'api/';

/* --------------------------
   App State
---------------------------*/
let guests = [];
let campaigns = [];
let feedback = [];
let complaints = [];
let loyaltyPrograms = [];

let currentFeedbackType = 'all';
let currentComplaintType = 'all';

let editingGuestId = null;
let editingCampaignId = null;
let editingComplaintId = null;

let guestChartInstance = null;
let loyaltyChartInstance = null;

/* --------------------------
   Boot
---------------------------*/
document.addEventListener('DOMContentLoaded', async () => {
  attachStaticListeners();
  await loadAll();
});

/* --------------------------
   One-shot loader
---------------------------*/
async function loadAll() {
  try {
    const [
      dashRes,
      guestsRes,
      campaignsRes,
      feedbackRes,
      complaintsRes,
      loyaltyRes
    ] = await Promise.all([
      apiRequest('dashboard.php'),
      apiRequest('guests.php'),
      apiRequest('campaigns.php'),
      apiRequest('feedback.php?type=all'),
      apiRequest('complaints.php'),
      apiRequest('loyalty.php')

    ]);

    const stats = dashRes?.data || {};
    guests = guestsRes?.data || [];
    campaigns = campaignsRes?.data || [];
    feedback = feedbackRes?.data || [];
    complaints = complaintsRes?.data || [];
    loyaltyPrograms = loyaltyRes?.data || [];

    updateStatCards(stats);
    initializeCharts(stats);

    renderGuests();
    renderCampaigns();
    renderFeedback();
    renderComplaints();
    renderPrograms();

    await loadGuestOptions(); // prepare select options for complaint form

    showNotification('Dashboard loaded');
  } catch (err) {
    console.error('loadAll failed:', err);
    showNotification('Failed to load data', 'error');
  }
}

/* --------------------------
   Helpers / API
---------------------------*/
async function safeParseJSON(response) {
  const text = await response.text();
  try {
    return JSON.parse(text);
  } catch {
    return { success: false, error: 'Invalid JSON response', raw: text };
  }
}

async function apiRequest(endpoint, method = 'GET', data = null) {
  try {
    const config = { method, headers: {} };
    if (method !== 'GET') {
      config.headers['Content-Type'] = 'application/json';
      if (data !== null) config.body = JSON.stringify(data);
    }
    const url = API_BASE_URL + endpoint;
    const resp = await fetch(url, config);
    const result = await safeParseJSON(resp);

    if (!resp.ok) {
      const msg = result?.error || result?.message || `Request failed (${resp.status})`;
      throw new Error(msg);
    }
    return result;
  } catch (e) {
    console.error('API Error:', e);
    showNotification(e.message || 'API error', 'error');
    throw e;
  }
}

function showNotification(message, type = 'success') {
  let n = document.getElementById('notification');
  if (!n) {
    n = document.createElement('div');
    n.id = 'notification';
    n.style.cssText = `
      position: fixed; top: 20px; right: 20px; padding: 12px 24px;
      border-radius: 8px; color: white; font-weight: 500; z-index: 9999;
      opacity: 0; transition: opacity .3s ease;`;
    document.body.appendChild(n);
  }
  n.textContent = message;
  n.style.backgroundColor =
    type === 'success' ? '#10b981' :
    type === 'error'   ? '#ef4444' :
    type === 'warning' ? '#f59e0b' : '#6b7280';
  n.style.opacity = '1';
  clearTimeout(n._hideTimeout);
  n._hideTimeout = setTimeout(() => (n.style.opacity = '0'), 3000);
}

/* --------------------------
   Navigation
---------------------------*/
function showSection(sectionName) { 
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));

  const target = document.getElementById(sectionName);
  if (!target) return console.warn('Section not found:', sectionName);
  target.classList.add('active');

  document.querySelectorAll('.menu-item').forEach(item => {
    const on = item.getAttribute('onclick') || '';
    if (on.includes(`'${sectionName}'`) || on.includes(`"${sectionName}"`)) {
      item.classList.add('active');
    }
  });

  if (sectionName === 'dashboard') {
    loadDashboardData();
  } else if (sectionName === 'loyalty') {
    loadLoyaltyPrograms();
  } else if (sectionName === 'feedback') {
    loadFeedback();
  } else if (sectionName === 'complaints') {
    loadComplaints();
    loadComplaintStats();
  }
}

/* --------------------------
   Dashboard
---------------------------*/
async function loadDashboardData() {
  try {
    const res = await apiRequest('dashboard.php');
    const stats = res?.data || {};
    updateStatCards(stats);
    initializeCharts(stats);
  } catch (e) { console.error(e); }
}

function updateStatCards(stats) {
  const cards = document.querySelectorAll('.stat-card');
  if (cards[0]) cards[0].querySelector('h3').textContent = stats.total_guests ?? 0;
  if (cards[1]) cards[1].querySelector('h3').textContent = stats.loyalty_members ?? 0;
  if (cards[2]) cards[2].querySelector('h3').textContent = stats.active_campaigns ?? 0;
  if (cards[3]) cards[3].querySelector('h3').textContent = stats.avg_rating ?? '0.0';
}

function initializeCharts(stats) {
  try { guestChartInstance?.destroy(); loyaltyChartInstance?.destroy(); } catch {}

  const gctx = document.getElementById('guestChart');
  if (gctx && typeof Chart !== 'undefined' && Array.isArray(stats.guest_trends)) {
    guestChartInstance = new Chart(gctx, {
      type: 'line',
      data: {
        labels: stats.guest_trends.map(t => t.month),
        datasets: [{
          label: 'Guests',
          data: stats.guest_trends.map(t => t.count),
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59,130,246,.1)',
          borderWidth: 2, tension: .4
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  }

  const lctx = document.getElementById('loyaltyChart');
  if (lctx && typeof Chart !== 'undefined' && Array.isArray(stats.loyalty_distribution)) {
    
    const tierColors = {
      bronze: '#cd7f32',
      silver: '#c0c0c0',
      gold:   '#ffd700',
      platinum: '#4f46e5'
    };

    const labels = stats.loyalty_distribution.map(l => 
      (l.tier || '').replace(/^./, c => c.toUpperCase())
    );
    const values = stats.loyalty_distribution.map(l => l.members_count || 0);
    const bgColors = stats.loyalty_distribution.map(l => tierColors[l.tier] || '#999999');

    loyaltyChartInstance = new Chart(lctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: bgColors
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#fff',
              font: { size: 14 }
            }
          }
        }
      }
    });
  }
}

/* --------------------------
   Guests - FIXED
---------------------------*/
async function loadGuests() {
  try {
    const res = await apiRequest('guests.php');
    guests = res?.data || [];
    renderGuests();
    await loadGuestOptions(); // For complaint form dropdown
    showNotification('Guests loaded successfully');
  } catch (e) { 
    console.error('Load guests error:', e);
    showNotification('Failed to load guests', 'error');
  }
}

function renderGuests() {
  const list = document.getElementById('guestsList');
  if (!list) return;
  list.innerHTML = '';

  if (!guests || guests.length === 0) {
    list.innerHTML = '<p style="color: white; text-align: center; padding: 40px;">No guests found. Add your first guest!</p>';
    return;
  }

  guests.forEach(guest => {
    const avatarText = (guest.name || '').split(' ').map(n => n?.[0] || '').join('').toUpperCase() || 'G';
    // Handle both guest_id and id fields from database
    const guestId = guest.guest_id || guest.id;
    
    const card = document.createElement('div');
    card.className = 'guest-card';
    card.innerHTML = `
      <div class="guest-header">
        <div class="guest-info">
          <div class="guest-avatar">${avatarText}</div>
          <div>
            <div class="guest-name">${escapeHtml(guest.name || '—')}</div>
            <span class="loyalty-badge ${guest.loyalty_tier || ''}">${(guest.loyalty_tier || 'unknown').toUpperCase()}</span>
          </div>
        </div>
      </div>
      <div class="guest-details">
        <div class="guest-detail"><span>📧</span><span>${escapeHtml(guest.email || '—')}</span></div>
        <div class="guest-detail"><span>📞</span><span>${escapeHtml(guest.phone || '—')}</span></div>
        <div class="guest-detail"><span>📍</span><span>${escapeHtml(guest.location || 'Unknown')}</span></div>
        ${guest.notes ? `<div class="guest-detail"><span>📝</span><span>${escapeHtml(guest.notes)}</span></div>` : ''}
      </div>
      <div class="guest-actions">
        <button class="btn btn-secondary" onclick="editGuest(${guestId})" style="margin-right: 8px; padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">✏️ Edit</button>
        <button class="btn btn-primary " onclick="deleteGuest(${guestId})" style="margin-right: 8px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">🗑️ Delete</button>
      </div>`;
    list.appendChild(card);
  });
}

async function filterGuests() {
  const term = document.getElementById('guestSearch')?.value.trim() || '';
  try {
    const res = await apiRequest(`guests.php?search=${encodeURIComponent(term)}`);
    guests = res?.data || [];
    renderGuests();
    await loadGuestOptions();
    showNotification(`Found ${guests.length} guests`);
  } catch (e) { 
    console.error('Filter guests error:', e);
    showNotification('Failed to filter guests', 'error');
  }
}

/* --------------------------
   Add Guest Modal & Functions
---------------------------*/

function showAddGuestModal() {
  // Reset form completely
  const form = document.getElementById('addGuestForm');
  if (form) form.reset();
  
  // Show modal
  const modal = document.getElementById('addGuestModal');
  if (modal) {
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
  }
  
  // Focus on first input
  setTimeout(() => {
    const firstInput = document.getElementById('guestName');
    if (firstInput) firstInput.focus();
  }, 100);
}

async function addGuest(event) {
  event.preventDefault();
  
  // Get form data
  const guestData = {
    name: document.getElementById('guestName')?.value.trim() || '',
    email: document.getElementById('guestEmail')?.value.trim() || '',
    phone: document.getElementById('guestPhone')?.value.trim() || '',
    loyalty_tier: document.getElementById('guestLoyalty')?.value || '',
    location: document.getElementById('guestLocation')?.value.trim() || 'Unknown',
    notes: document.getElementById('guestNotes')?.value.trim() || ''
  };
  
  // Validation
  if (!guestData.name) {
    showNotification('Full name is required', 'error');
    document.getElementById('guestName')?.focus();
    return;
  }
  
  if (!guestData.email) {
    showNotification('Email address is required', 'error');
    document.getElementById('guestEmail')?.focus();
    return;
  }
  
  if (!isValidEmail(guestData.email)) {
    showNotification('Please enter a valid email address', 'error');
    document.getElementById('guestEmail')?.focus();
    return;
  }
  
  if (!guestData.phone) {
    showNotification('Phone number is required', 'error');
    document.getElementById('guestPhone')?.focus();
    return;
  }
  
  if (!guestData.loyalty_tier) {
    showNotification('Please select a loyalty tier', 'error');
    document.getElementById('guestLoyalty')?.focus();
    return;
  }

  try {
    const response = await apiRequest('guests.php', 'POST', guestData);
    
    if (response.success) {
      await loadGuests();
      closeModal('addGuestModal');
      showNotification('Guest added successfully!');
    } else {
      throw new Error(response.error || 'Failed to add guest');
    }
  } catch (e) { 
    console.error('Add guest error:', e);
    showNotification(e.message || 'Failed to add guest', 'error');
  }
}

/* --------------------------
   Edit Guest Modal & Functions
---------------------------*/

function editGuest(id) {
  // Find guest using flexible ID matching
  const guest = guests.find(g => {
    const guestId = g.guest_id || g.id;
    return Number(guestId) === Number(id);
  });
  
  if (!guest) {
    console.error('Guest not found with ID:', id);
    showNotification('Guest not found', 'error');
    return;
  }
  
  // Store the ID for updating
  editingGuestId = id;
  
  // Populate form fields
  document.getElementById('editGuestName').value = guest.name || '';
  document.getElementById('editGuestEmail').value = guest.email || '';
  document.getElementById('editGuestPhone').value = guest.phone || '';
  document.getElementById('editGuestLoyalty').value = guest.loyalty_tier || '';
  document.getElementById('editGuestLocation').value = guest.location || '';
  document.getElementById('editGuestNotes').value = guest.notes || '';
  
  // Show modal
  const modal = document.getElementById('editGuestModal');
  if (modal) {
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
  }
  
  // Focus on first input
  setTimeout(() => {
    const firstInput = document.getElementById('editGuestName');
    if (firstInput) firstInput.focus();
  }, 100);
}

async function updateGuest(event) {
  event.preventDefault();
  
  if (!editingGuestId) {
    showNotification('No guest selected for editing', 'error');
    return;
  }
  
  // Get form data
  const data = {
    id: editingGuestId,
    name: document.getElementById('editGuestName')?.value.trim() || '',
    email: document.getElementById('editGuestEmail')?.value.trim() || '',
    phone: document.getElementById('editGuestPhone')?.value.trim() || '',
    loyalty_tier: document.getElementById('editGuestLoyalty')?.value || '',
    location: document.getElementById('editGuestLocation')?.value.trim() || 'Unknown',
    notes: document.getElementById('editGuestNotes')?.value.trim() || ''
  };
  
  // Validation
  if (!data.name) {
    showNotification('Full name is required', 'error');
    document.getElementById('editGuestName')?.focus();
    return;
  }
  
  if (!data.email) {
    showNotification('Email address is required', 'error');
    document.getElementById('editGuestEmail')?.focus();
    return;
  }
  
  if (!isValidEmail(data.email)) {
    showNotification('Please enter a valid email address', 'error');
    document.getElementById('editGuestEmail')?.focus();
    return;
  }
  
  if (!data.phone) {
    showNotification('Phone number is required', 'error');
    document.getElementById('editGuestPhone')?.focus();
    return;
  }
  
  if (!data.loyalty_tier) {
    showNotification('Please select a loyalty tier', 'error');
    document.getElementById('editGuestLoyalty')?.focus();
    return;
  }
  
  try {
    const response = await apiRequest('guests.php', 'PUT', data);
    
    if (response.success) {
      await loadGuests();
      closeModal('editGuestModal');
      editingGuestId = null;
      showNotification('Guest updated successfully!');
    } else {
      throw new Error(response.error || 'Failed to update guest');
    }
  } catch (e) { 
    console.error('Update guest error:', e);
    showNotification(e.message || 'Failed to update guest', 'error');
  }
}

/* --------------------------
   Delete Guest Function
---------------------------*/

async function deleteGuest(id) {
  const guest = guests.find(g => {
    const guestId = g.guest_id || g.id;
    return Number(guestId) === Number(id);
  });
  
  const guestName = guest?.name || 'this guest';
  
  if (!confirm(`Are you sure you want to delete ${guestName}? This action cannot be undone.`)) {
    return;
  }
  
  try {
    const response = await apiRequest('guests.php', 'DELETE', { id });
    
    if (response.success) {
      await loadGuests();
      showNotification('Guest deleted successfully!');
    } else {
      throw new Error(response.error || 'Failed to delete guest');
    }
  } catch (e) { 
    console.error('Delete guest error:', e);
    showNotification(e.message || 'Failed to delete guest', 'error');
  }
}

/* --------------------------
   Modal Management
---------------------------*/

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  
  modal.classList.remove('active');
  modal.setAttribute('aria-hidden', 'true');
  
  // Reset forms and states
  if (modalId === 'addGuestModal') {
    const form = document.getElementById('addGuestForm');
    if (form) form.reset();
  } else if (modalId === 'editGuestModal') {
    const form = document.getElementById('editGuestForm');
    if (form) form.reset();
    editingGuestId = null;
  }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
  if (event.target.classList.contains('modal') && event.target.classList.contains('active')) {
    const modalId = event.target.id;
    closeModal(modalId);
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const activeModal = document.querySelector('.modal.active');
    if (activeModal) {
      closeModal(activeModal.id);
    }
  }
});

/* --------------------------
   Helper Functions
---------------------------*/

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function escapeHtml(text) {
  if (typeof text !== 'string') return text;
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Load guest options for complaint form dropdown
async function loadGuestOptions() {
  try {
    if (!Array.isArray(guests) || !guests.length) {
      const res = await apiRequest('guests.php');
      guests = res?.data || [];
    }

    const select = document.getElementById('complaintGuestId');
    if (!select) return;

    select.innerHTML = '';
    const noneOpt = document.createElement('option');
    noneOpt.value = '';
    noneOpt.textContent = '-- Select guest (or leave blank to type name) --';
    select.appendChild(noneOpt);

    guests.forEach(g => {
      const opt = document.createElement('option');
      const guestId = g.guest_id || g.id;
      opt.value = guestId;
      opt.textContent = `${g.name} (${g.email || 'no email'})`;
      select.appendChild(opt);
    });
  } catch (err) {
    console.error('Failed to load guests for complaint select:', err);
  }
}

/* --------------------------
   Initialize Guest Management
---------------------------*/

// Auto-load guests when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  // Load guests if we're on the guests section
  const guestsSection = document.getElementById('guests');
  if (guestsSection) {
    loadGuests();
  }
});

/* --------------------------
   Expose functions to global scope
------

/* --------------------------
   Campaigns - FIXED
---------------------------*/
async function loadCampaigns() {
  try {
    campaigns = (await apiRequest('campaigns.php'))?.data || [];
    renderCampaigns();
  } catch (e) {
    console.error(e);
  }
}

function renderCampaigns() {
  const wrap = document.getElementById('campaignsList');
  if (!wrap) return;
  wrap.innerHTML = '';

  if (!campaigns.length) {
    wrap.innerHTML = '<p style="color:white;text-align:center;padding:40px;">No campaigns available.</p>';
    updateCampaignStats();
    return;
  }

  campaigns.forEach(c => {
    const div = document.createElement('div');
    div.className = 'campaign-card';
    div.innerHTML = `
      <div class="campaign-header">
        <div class="campaign-info">
          <div class="campaign-icon">📧</div>
          <div class="campaign-details">
            <h3>${escapeHtml(c.name || '—')}</h3>
            <div class="campaign-meta">
              <span class="status-badge ${c.status || ''}">
                ${(c.status || 'draft').replace(/^./, m=>m.toUpperCase())}
              </span>
              <span>Target: ${escapeHtml(c.target_audience || '—')}</span>
              <span>Type: ${escapeHtml(c.type || '—')}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="campaign-content">
        <p><strong>Description:</strong> ${escapeHtml(c.description || 'No description')}</p>
        <div class="campaign-stats">
          <div class="campaign-stat"><h4>${c.sent_count || 0}</h4><p>Sent</p></div>
          <div class="campaign-stat"><h4>${c.open_rate || 0}%</h4><p>Opened</p></div>
          <div class="campaign-stat"><h4>${c.click_rate || 0}%</h4><p>Clicked</p></div>
        </div>
      </div>
      <div class="campaign-actions">
        <button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="margin-right: 8px; padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">✏️ Edit</button>
        <button class="btn btn-primary" onclick="viewCampaign(${c.id})" style="margin-right: 8px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">👁️ View</button>
        <button class="btn btn-danger" onclick="deleteCampaign(${c.id})" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">🗑️ Delete</button>
      </div>`;
    wrap.appendChild(div);
  });

  updateCampaignStats();
}

function updateCampaignStats() {
  const totalSent = campaigns.reduce((sum, c) => sum + (Number(c.sent_count) || 0), 0);
  const totalOpened = campaigns.reduce((sum, c) =>
    sum + Math.round(((c.open_rate || 0) / 100) * (c.sent_count || 0)), 0);
  const totalClicked = campaigns.reduce((sum, c) =>
    sum + Math.round(((c.click_rate || 0) / 100) * (c.sent_count || 0)), 0);

  const clickRate = totalSent > 0 ? ((totalClicked / totalSent) * 100).toFixed(1) : 0;

  if (document.getElementById('statTotalSent'))
    document.getElementById('statTotalSent').textContent = totalSent;
  if (document.getElementById('statOpened'))
    document.getElementById('statOpened').textContent = totalOpened;
  if (document.getElementById('statClicked'))
    document.getElementById('statClicked').textContent = totalClicked;
  if (document.getElementById('statClickRate'))
    document.getElementById('statClickRate').textContent = clickRate + '%';
}

function showCreateCampaignModal() {
  document.getElementById('createCampaignForm')?.reset();
  editingCampaignId = null;

  document.getElementById('createCampaignTitle').textContent = "Create New Campaign";
  document.getElementById('campaignSaveBtn').style.display = "inline-block";
  document.getElementById('campaignCancelBtn').textContent = "Cancel";
  document.getElementById('campaignExtraStats').style.display = "none";

  toggleCampaignFields(true);

  document.getElementById('createCampaignModal')?.classList.add('active');
}

function editCampaign(id) {
  const c = campaigns.find(x => Number(x.id) === Number(id));
  if (!c) return showNotification('Campaign not found', 'error');
  
  editingCampaignId = id;

  document.getElementById('campaignName').value = c.name || '';
  document.getElementById('campaignDescription').value = c.description || '';
  document.getElementById('campaignType').value = c.type || '';
  document.getElementById('campaignAudience').value = c.target_audience || '';
  document.getElementById('campaignMessage').value = c.message || '';

  const validStatuses = ['draft','scheduled','active','completed'];
  const safeStatus = validStatuses.includes(c.status) ? c.status : 'draft';
  document.getElementById('campaignStatus').value = safeStatus;

  document.getElementById('campaignSchedule').value = c.schedule || '';

  document.getElementById('createCampaignTitle').textContent = "Edit Campaign";
  document.getElementById('campaignSaveBtn').style.display = "inline-block";
  document.getElementById('campaignCancelBtn').textContent = "Cancel";
  document.getElementById('campaignExtraStats').style.display = "none";

  toggleCampaignFields(true);
  document.getElementById('createCampaignModal')?.classList.add('active');
}

async function createCampaign(e) {
  e?.preventDefault();
  
  const data = {
    name: document.getElementById('campaignName')?.value.trim() || '',
    description: document.getElementById('campaignDescription')?.value.trim() || '',
    type: document.getElementById('campaignType')?.value || '',
    target_audience: document.getElementById('campaignAudience')?.value || '',
    message: document.getElementById('campaignMessage')?.value.trim() || '',
    status: document.getElementById('campaignStatus')?.value || 'draft',
    schedule: document.getElementById('campaignSchedule')?.value || null
  };

  const validStatuses = ['draft','scheduled','active','completed'];
  if (!validStatuses.includes(data.status)) {
    data.status = 'draft';
  }

  if (!data.name || !data.type || !data.target_audience || !data.message) {
    return showNotification('Please fill in all required campaign fields', 'error');
  }

  try {
    if (editingCampaignId) {
      data.id = editingCampaignId;
      await apiRequest('campaigns.php', 'PUT', data);
      showNotification('Campaign updated successfully!');
      editingCampaignId = null;
    } else {
      await apiRequest('campaigns.php', 'POST', data);
      showNotification('Campaign created successfully!');
    }
    
    await loadCampaigns();
    closeModal('createCampaignModal');
    document.getElementById('createCampaignForm')?.reset();
    
  } catch (err) {
    console.error(err);
    showNotification('Failed to save campaign', 'error');
  }
}

async function deleteCampaign(id) {
  if (!confirm('Are you sure you want to delete this campaign?')) return;
  try {
    await apiRequest('campaigns.php', 'DELETE', { id });
    await loadCampaigns();
    showNotification('Campaign deleted successfully!');
  } catch (e) { 
    console.error(e); 
  }
}

function viewCampaign(id) {
  const c = campaigns.find(x => Number(x.id) === Number(id));
  if (!c) return showNotification('Campaign not found', 'error');
  
  editingCampaignId = null;

  document.getElementById('campaignName').value = c.name || '';
  document.getElementById('campaignDescription').value = c.description || '';
  document.getElementById('campaignType').value = c.type || '';
  document.getElementById('campaignAudience').value = c.target_audience || '';
  document.getElementById('campaignMessage').value = c.message || '';
  document.getElementById('campaignStatus').value = c.status || 'draft';
  document.getElementById('campaignSchedule').value = c.schedule || '';

  document.getElementById('createCampaignTitle').textContent = "View Campaign";
  document.getElementById('campaignSaveBtn').style.display = "none";
  document.getElementById('campaignCancelBtn').textContent = "Close";
  document.getElementById('campaignExtraStats').style.display = "block";

  toggleCampaignFields(false);
  document.getElementById('createCampaignModal')?.classList.add('active');
}

function toggleCampaignFields(enable) {
  const fields = [
    'campaignName',
    'campaignDescription', 
    'campaignType',
    'campaignAudience',
    'campaignMessage',
    'campaignStatus',
    'campaignSchedule'
  ];

  fields.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.disabled = !enable;
  });
}

/* --------------------------
   Feedback - FIXED
---------------------------*/
async function loadFeedback() {
  try {
    feedback = (await apiRequest('feedback.php?type=all'))?.data || [];
    renderFeedback();
    updateFeedbackStats();
  } catch (e) { console.error(e); }
}

function renderFeedback() {
  const list = document.getElementById('feedbackList');
  if (!list) return;
  list.innerHTML = '';

  feedback = feedback.map(f => ({
    ...f,
    type: f.type && String(f.type).trim() !== '' ? f.type : 'review'
  }));

  let filtered = feedback;
  if (currentFeedbackType !== 'all') {
    filtered = feedback.filter(f => f.type === currentFeedbackType);
  }

  if (!filtered.length) {
    list.innerHTML = '<p style="color: white; text-align: center; padding: 40px;">No feedback available.</p>';
    updateFeedbackStats();
    return;
  }

  filtered.forEach(item => {
    const avatar = (item.guest_name || '')
      .split(' ')
      .map(n => n?.[0] || '')
      .join('')
      .toUpperCase() || 'G';

    const card = document.createElement('div');
    card.className = 'feedback-card';
    card.innerHTML = `
      <div class="feedback-header">
        <div class="feedback-avatar">${avatar}</div>
        <div class="feedback-info">
          <h3>${escapeHtml(item.guest_name || '—')}</h3>
          <div class="feedback-meta">
            <span class="feedback-type ${item.type || ''}">${(item.type || '').toUpperCase()}</span>
            <span>${item.created_at ? new Date(item.created_at).toLocaleDateString() : ''}</span>
          </div>
        </div>
      </div>
      <div class="stars">${generateStars(item.rating || 0)}</div>
      <div class="feedback-message">${escapeHtml(item.comment || item.message || '')}</div>
      ${
        item.reply
          ? `<div class="feedback-reply" style="background:#f0f9ff;padding:12px;border-radius:8px;margin-top:12px;border-left:4px solid #3b82f6;"><strong>Reply:</strong> ${escapeHtml(item.reply)}</div>`
          : ''
      }
      <div class="feedback-actions">
        <button class="btn btn-secondary" onclick="replyToFeedback(${item.id})" style="margin-right: 8px; padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">💬 Reply</button>
        <button class="btn btn-success" onclick="updateFeedbackStatus(${item.id}, 'approved')" style="margin-right: 8px; padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">✅ Approve</button>
        <button class="btn btn-danger" onclick="deleteFeedback(${item.id})" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">🗑️ Delete</button>
      </div>`;
    list.appendChild(card);
  });

  updateFeedbackStats();
}

function updateFeedbackStats() {
  const totalReviews = feedback.filter(f => f.type === 'review').length;
  const totalServiceFeedback = feedback.filter(f => f.type === 'service_feedback').length;

  const rated = feedback.filter(f => f.rating && !isNaN(f.rating));
  const avgRating = rated.length
    ? (rated.reduce((sum, f) => sum + Number(f.rating), 0) / rated.length).toFixed(1)
    : 0;

  const totalFeedback = feedback.length;
  const resolved = feedback.filter(f => f.status === 'approved' || f.status === 'rejected').length;
  const resolutionRate = totalFeedback > 0 ? Math.round((resolved / totalFeedback) * 100) : 0;

  if (document.getElementById('averageRating')) document.getElementById('averageRating').textContent = avgRating;
  if (document.getElementById('totalReviews')) document.getElementById('totalReviews').textContent = totalReviews;
  if (document.getElementById('totalServiceFeedback')) document.getElementById('totalServiceFeedback').textContent = totalServiceFeedback;

  if (document.getElementById('statAverageRating')) document.getElementById('statAverageRating').textContent = avgRating;
  if (document.getElementById('statTotalReviews')) document.getElementById('statTotalReviews').textContent = totalReviews;
  if (document.getElementById('statServiceFeedback')) document.getElementById('statServiceFeedback').textContent = totalServiceFeedback;
  if (document.getElementById('statResolutionRate')) document.getElementById('statResolutionRate').textContent = resolutionRate + '%';
}

function generateStars(rating) {
  let s = '';
  for (let i = 1; i <= 5; i++) s += i <= rating ? '⭐' : '☆';
  return s;
}

function showFeedbackType(type) {
  currentFeedbackType = type;
  document.querySelectorAll('#feedback .tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('#feedback .tab-btn').forEach(b => {
    const on = b.getAttribute('onclick') || '';
    if (on.includes(`'${type}'`) || on.includes(`"${type}"`)) b.classList.add('active');
  });
  renderFeedback();
}

async function replyToFeedback(id) {
  const item = feedback.find(f => Number(f.id) === Number(id));
  if (!item) return;
  const reply = prompt(`Reply to ${item.guest_name || 'Guest'}:`);
  if (!reply?.trim()) return;

  try {
    await apiRequest('feedback.php', 'PUT', { id, reply: reply.trim(), status: 'approved' });
    await loadFeedback();
    showNotification('Reply sent successfully!');
  } catch (e) { console.error(e); }
}

async function updateFeedbackStatus(id, status) {
  try {
    await apiRequest('feedback.php', 'PUT', { id, status });
    await loadFeedback();
    updateFeedbackStats();
    showNotification(`Feedback ${status} successfully!`);
  } catch (e) {
    console.error(e);
    showNotification('Failed to update feedback', 'error');
  }
}

async function deleteFeedback(id) {
  if (!confirm('Are you sure you want to delete this feedback?')) return;
  try {
    await apiRequest('feedback.php', 'DELETE', { id });
    await loadFeedback();
    updateFeedbackStats();
    showNotification('Feedback deleted successfully!');
  } catch (e) {
    console.error(e);
    showNotification('Failed to delete feedback', 'error');
  }
}

/* --------------------------
   Complaints - Updated for guest_id - FIXED
---------------------------*/
async function loadComplaints() {
  try {
    complaints = (await apiRequest('complaints.php'))?.data || [];
    renderComplaints();
  } catch (e) { console.error(e); }
}

async function loadComplaintStats() {
  try {
    const res = await apiRequest('complaints.php?stats=1');
    if (!res?.data) return;

    const stats = res.data;
    const setIf = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

    setIf('statTotalComplaints', stats.total_complaints);
    setIf('statResolvedComplaints', stats.resolved_complaints);
    setIf('statActiveComplaints', stats.active_complaints);
    setIf('statSuggestions', stats.total_suggestions);
    setIf('statCompliments', stats.total_compliments);
    setIf('statResolutionRate', stats.resolution_rate + '%');
  } catch (err) {
    console.error("Failed to load complaint stats:", err);
  }
}

function renderComplaints() {
  const list = document.getElementById('complaintsList');
  if (!list) return;
  list.innerHTML = '';

  const filtered = currentComplaintType === 'all'
    ? complaints
    : complaints.filter(c => c.status === currentComplaintType);

  if (!filtered.length) {
    list.innerHTML = '<p style="color: white; text-align: center; padding: 40px;">No complaints available.</p>';
    updateComplaintStats();
    return;
  }

  filtered.forEach(item => {
    const avatar = (item.guest_name || '')
      .split(' ')
      .map(n => n?.[0] || '')
      .join('')
      .toUpperCase() || 'G';

    const card = document.createElement('div');
    card.className = 'feedback-card';
    card.innerHTML = `
      <div class="feedback-header">
        <div class="feedback-avatar">${avatar}</div>
        <div class="feedback-info">
          <h3>${escapeHtml(item.guest_name || '—')}</h3>
          <div class="feedback-meta">
            <span class="status-badge ${item.status || ''}">
              ${(item.status || '').replace(/^./, m => m.toUpperCase())}
            </span>
            <span>${item.created_at ? new Date(item.created_at).toLocaleDateString() : ''}</span>
          </div>
        </div>
      </div>
      <div class="feedback-rating">
        ${item.rating ? generateStars(item.rating) : 'No Rating'}
      </div>
      <div class="feedback-message">
        ${escapeHtml(item.comment || item.message || '')}
      </div>
      <div class="feedback-actions">
        <button class="btn btn-secondary" onclick="editComplaint(${item.id})" style="margin-right: 8px; padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">✏️ Edit Status</button>
        <button class="btn btn-primary" onclick="replyToComplaint(${item.id})" style="margin-right: 8px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">💬 Respond</button>
        <button class="btn btn-danger" onclick="deleteComplaint(${item.id})" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">🗑️ Delete</button>
      </div>`;
    list.appendChild(card);
  });

  updateComplaintStats();
}

async function loadGuestOptions() {
  try {
    if (!Array.isArray(guests) || !guests.length) {
      const res = await apiRequest('guests.php');
      guests = res?.data || [];
    }

    const select = document.getElementById('complaintGuestId');
    if (!select) return;

    select.innerHTML = '';
    const noneOpt = document.createElement('option');
    noneOpt.value = '';
    noneOpt.textContent = '-- Select guest (or leave blank to type name) --';
    select.appendChild(noneOpt);

    guests.forEach(g => {
      const opt = document.createElement('option');
      opt.value = g.guest_id || g.id;
      opt.textContent = `${g.name} (${g.email || 'no email'})`;
      select.appendChild(opt);
    });
  } catch (err) {
    console.error('Failed to load guests for complaint select', err);
  }
}

function showCreateComplaintModal() {
  loadGuestOptions();
  document.getElementById('createComplaintForm')?.reset();
  const modal = document.getElementById('createComplaintModal');
  if (modal) modal.classList.add('active');
}

async function createComplaint(e) {
  e?.preventDefault();

  const select = document.getElementById('complaintGuestId');
  const manualNameInput = document.getElementById('complaintGuestName');
  let guest_id = select?.value || null;
  let guest_name = '';

  if (guest_id) {
    const g = guests.find(x => {
      const guestId = x.id || x.guest_id;
      return String(guestId) === String(guest_id);
    });
    
    if (g) {
      guest_name = g.name || '';
      if (!guest_name && g.first_name) {
        guest_name = `${g.first_name} ${g.last_name || ''}`.trim();
      }
      if (!guest_name) {
        guest_name = (manualNameInput?.value || '').trim();
      }
    }
  } else {
    guest_name = (manualNameInput?.value || '').trim();
  }

  const comment = (document.getElementById('complaintComment')?.value || '').trim();
  const status = document.getElementById('complaintStatus')?.value || 'pending';
  const type = document.getElementById('complaintType')?.value || 'complaint';

  if (!guest_name) return showNotification('Guest name is required (select a guest or type a name)', 'error');
  if (!comment) return showNotification('Comment is required', 'error');

  const payload = { guest_id: guest_id || null, guest_name, comment, status, type };

  try {
    await apiRequest('complaints.php', 'POST', payload);
    await loadComplaints();
    closeModal('createComplaintModal');
    document.getElementById('createComplaintForm')?.reset();
    showNotification('Complaint submitted successfully!');
  } catch (err) {
    console.error('Failed adding complaint:', err);
    showNotification(err?.message || 'Failed to submit complaint', 'error');
  }
}

function editComplaint(id) {
  const item = complaints.find(c => Number(c.id) === Number(id));
  if (!item) return showNotification('Complaint not found', 'error');

  editingComplaintId = id;
  document.getElementById('editComplaintId').value = item.id || '';
  document.getElementById('editComplaintGuestName').value = item.guest_name || '';
  document.getElementById('editComplaintComment').value = item.comment || '';
  document.getElementById('editComplaintType').value = item.type || 'complaint';
  document.getElementById('editComplaintRating').value = item.rating || '';
  document.getElementById('editComplaintStatus').value = item.status || 'pending';

  document.getElementById('editComplaintModal')?.classList.add('active');
}

async function updateComplaint(e) {
  e?.preventDefault();
  const data = {
    id: document.getElementById('editComplaintId')?.value,
    type: document.getElementById('editComplaintType')?.value || 'complaint',
    rating: document.getElementById('editComplaintRating')?.value || null,
    status: document.getElementById('editComplaintStatus')?.value || 'pending'
  };

  if (!data.id) return showNotification('Missing complaint ID', 'error');

  try {
    await apiRequest('complaints.php', 'PUT', data);
    await loadComplaints();
    closeModal('editComplaintModal');
    editingComplaintId = null;
    showNotification('Complaint updated successfully!');
  } catch (e) {
    console.error(e);
    showNotification('Failed to update complaint', 'error');
  }
}

async function replyToComplaint(id) {
  const item = complaints.find(c => Number(c.id) === Number(id));
  if (!item) return;
  const reply = prompt(`Respond to ${item.guest_name || 'Guest'}'s complaint:`);
  if (!reply?.trim()) return;

  try {
    await apiRequest('complaints.php', 'PUT', { id, status: 'resolved', reply: reply.trim() });
    await loadComplaints();
    showNotification('Response sent and complaint marked as resolved!');
  } catch (e) { console.error(e); }
}

async function deleteComplaint(id) {
  if (!confirm('Are you sure you want to delete this complaint?')) return;
  try {
    await apiRequest('complaints.php', 'DELETE', { id });
    await loadComplaints();
    showNotification('Complaint deleted successfully!');
  } catch (e) { console.error(e); }
}

function updateComplaintStats() {
  const totalSuggestions = complaints.filter(c => c.type === 'suggestion').length;
  const totalCompliments = complaints.filter(c => c.type === 'compliment').length;
  const totalComplaints = complaints.filter(c => c.type === 'complaint').length;
  const activeComplaints = complaints.filter(c => c.type === 'complaint' && c.status !== 'resolved' && c.status !== 'dismissed').length;
  const resolved = complaints.filter(c => c.status === 'resolved').length;
  const resolutionRate = totalComplaints > 0 ? Math.round((resolved / totalComplaints) * 100) : 0;

  const setIf = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

  setIf('statSuggestions', totalSuggestions);
  setIf('statCompliments', totalCompliments);
  setIf('statActiveComplaints', activeComplaints);
  setIf('statResolutionRate', resolutionRate + '%');
}

/* --------------------------
   Loyalty Programs (unchanged)
---------------------------*/
async function loadLoyaltyPrograms() {
  try {
    loyaltyPrograms = (await apiRequest('loyalty.php'))?.data || [];
    renderPrograms();
  } catch (e) { console.error(e); showNotification('Failed to load loyalty programs', 'error'); }
}

function renderPrograms() {
  const container = document.querySelector('.loyalty-programs');
  if (!container) return console.error('Loyalty programs container not found');
  container.innerHTML = '';

  if (!Array.isArray(loyaltyPrograms) || !loyaltyPrograms.length) {
    container.innerHTML = '<p style="color: white; text-align: center; padding: 40px;">No loyalty programs available</p>';
    return;
  }

  loyaltyPrograms.forEach(p => {
    const card = document.createElement('div');
    card.className = `program-card ${p.tier || ''}`;

    let benefits = [];
    if (typeof p.benefits === 'string') {
      benefits = p.benefits.split(',').map(b => b.trim()).filter(Boolean);
    } else if (Array.isArray(p.benefits)) {
      benefits = p.benefits;
    }

    card.innerHTML = `
      <div class="program-header">
        <div class="program-icon">${getTierIcon(p.tier)}</div>
        <div class="program-info">
          <h3>${escapeHtml(p.name || '—')}</h3>
          <p>${Number(p.members_count) || 0} members</p>
        </div>
      </div>
      <div class="program-details">
        <div class="points-info">
          <span>Points per $1: <strong>${p.points_rate || 0}x</strong></span>
        </div>
        <ul class="benefits-list">
          ${benefits.map(b => `<li>• ${escapeHtml(b)}</li>`).join('')}
        </ul>
      </div>
    `;

    container.appendChild(card);
  });
}

function getTierIcon(tier) {
  switch ((tier || '').toLowerCase()) {
    case 'platinum': return '💎';
    case 'gold': return '🏆';
    case 'silver': return '🥈';
    case 'bronze': return '🥉';
    default: return '⭐';
  }
}

function showCreateProgramModal() {
  document.getElementById('createProgramModal')?.classList.add('active');
}

async function createProgram(e) {
  e?.preventDefault();

  const name = document.getElementById('programName')?.value.trim();
  const tier = document.getElementById('programTier')?.value.trim();
  const pointsRate = document.getElementById('programPointsRate')?.value.trim();
  const benefits = document.getElementById('programBenefits')?.value.trim();
  const membersCount = document.getElementById('programMembersCount')?.value.trim();

  if (!name || !tier || !pointsRate) {
    return showNotification('Please fill in all required fields', 'error');
  }

  const payload = {
    name,
    tier,
    points_rate: parseFloat(pointsRate),
    benefits,
    members_count: membersCount ? parseInt(membersCount, 10) : 0,
  };

  try {
    await apiRequest('loyalty.php', 'POST', payload);

    loyaltyPrograms = [];
    await loadLoyaltyPrograms();

    closeModal('createProgramModal');
    document.getElementById('createProgramForm')?.reset();
    showNotification('Loyalty program created successfully!');
  } catch (e) {
    console.error(e);
    showNotification('Failed to create program: ' + (e.message || ''), 'error');
  }
}

/* --------------------------
   Modals & listeners
---------------------------*/
function closeModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  m.classList.remove('active');

  if (id === 'createCampaignModal') {
    editingCampaignId = null;
    const title = m.querySelector('h3');
    if (title) title.textContent = 'Create New Campaign';
    document.getElementById('createCampaignForm')?.reset();
  } else if (id === 'editGuestModal') {
    editingGuestId = null;
    document.getElementById('editGuestForm')?.reset();
  } else if (id === 'addGuestModal') {
    document.getElementById('addGuestForm')?.reset();
  } else if (id === 'editComplaintModal') {
    editingComplaintId = null;
    document.getElementById('editComplaintForm')?.reset();
  } else if (id === 'createComplaintModal') {
    document.getElementById('createComplaintForm')?.reset();
  } else if (id === 'createProgramModal') {
    document.getElementById('createProgramForm')?.reset();
  }
}

function closeAllModals() {
  document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
  editingGuestId = null; editingCampaignId = null; editingComplaintId = null;
}

function attachStaticListeners() {
  detachListener('#addGuestForm', 'submit', addGuest);
  detachListener('#editGuestForm', 'submit', updateGuest);
  detachListener('#createCampaignForm', 'submit', createCampaign);
  detachListener('#createProgramForm', 'submit', createProgram);
  detachListener('#createComplaintForm', 'submit', createComplaint);
  detachListener('#editComplaintForm', 'submit', updateComplaint);

  document.querySelector('#addGuestForm')?.addEventListener('submit', addGuest);
  document.querySelector('#editGuestForm')?.addEventListener('submit', updateGuest);
  document.querySelector('#createCampaignForm')?.addEventListener('submit', createCampaign);
  document.querySelector('#createProgramForm')?.addEventListener('submit', createProgram);
  document.querySelector('#createComplaintForm')?.addEventListener('submit', createComplaint);
  document.querySelector('#editComplaintForm')?.addEventListener('submit', updateComplaint);

  window.addEventListener('click', e => {
    document.querySelectorAll('.modal').forEach(m => {
      if (m.classList.contains('active') && e.target === m) m.classList.remove('active');
    });
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeAllModals();
  });
}

function detachListener(selector, event, handler) {
  const el = document.querySelector(selector);
  if (el) el.removeEventListener(event, handler);
}

/* --------------------------
   Utilities
---------------------------*/
function escapeHtml(str) {
  if (typeof str !== 'string') return str;
  return str.replace(/[&<>"']/g, function (m) {
    return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m];
  });
}

/* --------------------------
   Expose to window
---------------------------*/
window.showSection = showSection;
window.loadAll = loadAll;

window.showAddGuestModal = showAddGuestModal;
window.addGuest = addGuest;
window.editGuest = editGuest;
window.updateGuest = updateGuest;
window.deleteGuest = deleteGuest;
window.filterGuests = filterGuests;

window.showCreateCampaignModal = showCreateCampaignModal;
window.createCampaign = createCampaign;
window.editCampaign = editCampaign;
window.deleteCampaign = deleteCampaign;
window.viewCampaign = viewCampaign;

window.showFeedbackType = showFeedbackType;
window.replyToFeedback = replyToFeedback;
window.updateFeedbackStatus = updateFeedbackStatus;
window.deleteFeedback = deleteFeedback;

window.showCreateProgramModal = showCreateProgramModal;
window.createProgram = createProgram;

window.closeModal = closeModal;
window.closeAllModals = closeAllModals;

window.showCreateComplaintModal = showCreateComplaintModal;
window.createComplaint = createComplaint;
window.editComplaint = editComplaint;
window.updateComplaint = updateComplaint;
window.replyToComplaint = replyToComplaint;
window.deleteComplaint = deleteComplaint;

window.loadGuestOptions = loadGuestOptions;