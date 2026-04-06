/* =============================================
   BudgetSense — Main JavaScript
   ============================================= */

'use strict';

// ===== SECTION NAVIGATION =====
function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

  const section = document.getElementById(name);
  if (section) section.classList.add('active');

  document.querySelectorAll(`[data-section="${name}"]`).forEach(n => n.classList.add('active'));

  // Re-animate cards in section
  section?.querySelectorAll('[data-animate]').forEach((el, i) => {
    el.classList.remove('animated');
    setTimeout(() => el.classList.add('animated'), i * 80);
  });
}

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', e => {
    e.preventDefault();
    const section = item.dataset.section;
    showSection(section);
    closeSidebar();
  });
});

// ===== SIDEBAR MOBILE =====
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menuBtn');
const sidebarClose = document.getElementById('sidebarClose');

menuBtn?.addEventListener('click', () => sidebar.classList.add('open'));
sidebarClose?.addEventListener('click', closeSidebar);
function closeSidebar() { sidebar.classList.remove('open'); }
document.addEventListener('click', e => {
  if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== menuBtn) closeSidebar();
});

// ===== THEME TOGGLE =====
const themeBtn = document.getElementById('themeToggle');
const themeIcon = themeBtn?.querySelector('.theme-icon');
const themeLabel = themeBtn?.querySelector('.theme-label');

const savedTheme = localStorage.getItem('budgetsense-theme') || 'light';
applyTheme(savedTheme);

themeBtn?.addEventListener('click', () => {
  const current = document.documentElement.getAttribute('data-theme');
  applyTheme(current === 'dark' ? 'light' : 'dark');
});

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('budgetsense-theme', theme);
  if (themeIcon) themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
  if (themeLabel) themeLabel.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
}

// ===== NOTIFICATIONS =====
const notifBell = document.getElementById('notifBell');
const notifPanel = document.getElementById('notifPanel');

notifBell?.addEventListener('click', e => {
  e.stopPropagation();
  notifPanel.style.display = notifPanel.style.display === 'block' ? 'none' : 'block';
});
document.addEventListener('click', e => {
  if (!notifPanel?.contains(e.target) && e.target !== notifBell) {
    if (notifPanel) notifPanel.style.display = 'none';
  }
});

// ===== PROGRESS BAR ANIMATION =====
function animateBudgetBar() {
  const bar = document.getElementById('budgetBar');
  if (!bar) return;
  const target = bar.getAttribute('data-target');
  setTimeout(() => { bar.style.width = target; }, 300);
}

// ===== ENTRANCE ANIMATIONS =====
function animateOnLoad() {
  document.querySelectorAll('#overview [data-animate]').forEach((el, i) => {
    setTimeout(() => el.classList.add('animated'), 200 + i * 100);
  });
  animateBudgetBar();
}

// ===== TOAST =====
let toastTimer;
function showToast(msg, type = 'info') {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = msg;
  toast.className = `toast ${type} show`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
}

// ===== MODAL SYSTEM =====
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.add('open');
}
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('open');
    modal.querySelector('form')?.reset();
    // Reset hidden IDs
    modal.querySelector('[id$="_id"]') && (modal.querySelector('[id$="_id"]').value = '');
    // Reset modal titles
    if (id === 'incomeModal') {
      document.getElementById('incomeModalTitle').textContent = 'Add Income';
      document.getElementById('incomeSubmitBtn').textContent = 'Add Income';
    }
    if (id === 'expenseModal') {
      document.getElementById('expenseModalTitle').textContent = 'Add Expense';
      document.getElementById('expenseSubmitBtn').textContent = 'Add Expense';
    }
  }
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

// ===== INCOME FORM =====
const incomeForm = document.getElementById('incomeForm');
incomeForm?.addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('incomeSubmitBtn');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  const data = {
    id: document.getElementById('income_id').value,
    source: document.getElementById('income_source').value.trim(),
    amount: document.getElementById('income_amount').value,
    date: document.getElementById('income_date').value,
    note: document.getElementById('income_note').value.trim()
  };

  try {
    const res = await fetch('add_income.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.success) {
      showToast('✅ Income saved successfully!', 'success');
      closeModal('incomeModal');
      setTimeout(() => location.reload(), 600);
    } else {
      showToast('❌ ' + (json.error || 'Failed to save'), 'error');
    }
  } catch(err) {
    showToast('❌ Network error', 'error');
  } finally {
    btn.disabled = false;
  }
});

function editIncome(id, source, amount, date, note) {
  document.getElementById('income_id').value = id;
  document.getElementById('income_source').value = source;
  document.getElementById('income_amount').value = amount;
  document.getElementById('income_date').value = date;
  document.getElementById('income_note').value = note;
  document.getElementById('incomeModalTitle').textContent = 'Edit Income';
  document.getElementById('incomeSubmitBtn').textContent = 'Update Income';
  openModal('incomeModal');
}

// ===== EXPENSE FORM =====
const expenseForm = document.getElementById('expenseForm');
expenseForm?.addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('expenseSubmitBtn');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  const data = {
    id: document.getElementById('expense_id').value,
    title: document.getElementById('expense_title').value.trim(),
    category: document.getElementById('expense_category').value,
    amount: document.getElementById('expense_amount').value,
    date: document.getElementById('expense_date').value,
    note: document.getElementById('expense_note').value.trim()
  };

  try {
    const res = await fetch('add_expense.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.success) {
      showToast('✅ Expense saved!', 'success');
      closeModal('expenseModal');
      setTimeout(() => location.reload(), 600);
    } else {
      showToast('❌ ' + (json.error || 'Failed to save'), 'error');
    }
  } catch(err) {
    showToast('❌ Network error', 'error');
  } finally {
    btn.disabled = false;
  }
});

function editExpense(id, title, category, amount, date, note) {
  document.getElementById('expense_id').value = id;
  document.getElementById('expense_title').value = title;
  document.getElementById('expense_category').value = category;
  document.getElementById('expense_amount').value = amount;
  document.getElementById('expense_date').value = date;
  document.getElementById('expense_note').value = note;
  document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
  document.getElementById('expenseSubmitBtn').textContent = 'Update Expense';
  openModal('expenseModal');
}

// ===== DELETE RECORDS =====
function deleteRecord(type, id) {
  if (!confirm(`Delete this ${type}? This cannot be undone.`)) return;
  fetch('delete_expense.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({type, id})
  })
  .then(r => r.json())
  .then(json => {
    if (json.success) {
      showToast(`✅ ${type === 'income' ? 'Income' : 'Expense'} deleted`, 'success');
      // Animate row removal
      const row = document.querySelector(`tr[data-id="${id}"]`);
      if (row) {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        row.style.transition = 'all 0.3s ease';
        setTimeout(() => { row.remove(); updateMoodUI(json.mood); }, 300);
      } else {
        setTimeout(() => location.reload(), 600);
      }
    } else {
      showToast('❌ Delete failed', 'error');
    }
  })
  .catch(() => showToast('❌ Network error', 'error'));
}

// ===== UPDATE BUDGET =====
const budgetForm = document.getElementById('budgetForm');
budgetForm?.addEventListener('submit', async e => {
  e.preventDefault();
  const amount = document.getElementById('budgetAmount').value;
  const msg = document.getElementById('budgetMsg');
  msg.textContent = '';

  try {
    const res = await fetch('update_budget.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({amount, month: CHART_DATA.month})
    });
    const json = await res.json();
    if (json.success) {
      msg.textContent = '✅ Budget updated successfully!';
      msg.className = 'budget-feedback success';
      showToast('🎯 Budget goal set!', 'success');
      setTimeout(() => location.reload(), 1000);
    } else {
      msg.textContent = '❌ ' + json.error;
      msg.className = 'budget-feedback error';
    }
  } catch(err) {
    msg.textContent = '❌ Failed to update budget';
    msg.className = 'budget-feedback error';
  }
});

// ===== UPDATE MOOD UI DYNAMICALLY =====
function updateMoodUI(mood) {
  if (!mood) return;
  const card = document.getElementById('moodCard');
  if (!card) return;
  card.className = 'mood-card mood-' + mood.mood.toLowerCase();
  card.querySelector('.mood-emoji-large').textContent = mood.mood_emoji;
  card.querySelector('.mood-state').textContent = mood.mood + ' Mode';
  card.querySelector('.mood-message').textContent = mood.mood_message;
}

// ===== CONFETTI FOR HAPPY STATE =====
function triggerConfetti() {
  if (typeof confetti === 'undefined') return;
  const duration = 3000;
  const end = Date.now() + duration;
  const colors = ['#4F46E5', '#22C55E', '#06B6D4', '#F59E0B', '#EC4899'];

  (function frame() {
    confetti({
      particleCount: 4,
      angle: 60,
      spread: 55,
      origin: { x: 0 },
      colors: colors
    });
    confetti({
      particleCount: 4,
      angle: 120,
      spread: 55,
      origin: { x: 1 },
      colors: colors
    });
    if (Date.now() < end) requestAnimationFrame(frame);
  })();
}

// ===== CHARTS =====
let pieChart, barChart, moodChart, donutChart;

function initCharts() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const textColor = isDark ? 'rgba(241,245,249,0.7)' : 'rgba(15,18,26,0.6)';
  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.color = textColor;

  // ===== PIE CHART =====
  const pieCtx = document.getElementById('pieChart');
  if (pieCtx && CHART_DATA.catLabels.length > 0) {
    pieChart = new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: CHART_DATA.catLabels,
        datasets: [{
          data: CHART_DATA.catAmounts,
          backgroundColor: ['#EF4444','#06B6D4','#F59E0B','#8B5CF6','#EC4899','#64748B'],
          borderColor: isDark ? '#1e2440' : '#ffffff',
          borderWidth: 3,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '62%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { padding: 16, font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10 }
          },
          tooltip: {
            callbacks: {
              label: ctx => ` ₹${ctx.parsed.toLocaleString('en-IN', {minimumFractionDigits:2})}`
            }
          }
        }
      }
    });
  }

  // ===== BAR CHART =====
  const barCtx = document.getElementById('barChart');
  if (barCtx) {
    barChart = new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: CHART_DATA.monthlyLabels,
        datasets: [{
          label: 'Expenses (₹)',
          data: CHART_DATA.monthlyAmounts,
          backgroundColor: CHART_DATA.monthlyLabels.map((_, i) =>
            i === CHART_DATA.monthlyLabels.length - 1
              ? 'rgba(79,70,229,0.9)'
              : 'rgba(79,70,229,0.3)'
          ),
          borderColor: 'rgba(79,70,229,0.8)',
          borderWidth: 0,
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor } },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v)
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ` ₹${ctx.parsed.y.toLocaleString('en-IN', {minimumFractionDigits:2})}`
            }
          }
        }
      }
    });
  }

  // ===== MOOD TREND CHART =====
  const moodCtx = document.getElementById('moodChart');
  if (moodCtx) {
    const moodToNum = m => m === 'Happy' ? 3 : m === 'Neutral' ? 2 : m === 'Sad' ? 1 : null;
    const moodToColor = m => m === 'Happy' ? '#22C55E' : m === 'Neutral' ? '#F59E0B' : m === 'Sad' ? '#EF4444' : '#94A3B8';

    moodChart = new Chart(moodCtx, {
      type: 'line',
      data: {
        labels: CHART_DATA.moodLabels,
        datasets: [{
          label: 'Mood',
          data: CHART_DATA.moodData.map(m => moodToNum(m)),
          borderColor: '#4F46E5',
          backgroundColor: 'rgba(79,70,229,0.08)',
          borderWidth: 2.5,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: CHART_DATA.moodData.map(moodToColor),
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 7,
          pointHoverRadius: 10,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor } },
          y: {
            min: 0.5, max: 3.5,
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              stepSize: 1,
              callback: v => v === 3 ? '😄 Happy' : v === 2 ? '😐 Neutral' : v === 1 ? '😢 Sad' : ''
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => {
                const m = CHART_DATA.moodData[ctx.dataIndex];
                const e = m === 'Happy' ? '😄' : m === 'Neutral' ? '😐' : m === 'Sad' ? '😢' : '—';
                return ` ${e} ${m || 'No data'}`;
              }
            }
          }
        }
      }
    });
  }

  // ===== DONUT BUDGET CHART =====
  const donutCtx = document.getElementById('budgetDonut');
  if (donutCtx) {
    const usedColor = CHART_DATA.currentMood === 'Happy' ? '#22C55E' :
                      CHART_DATA.currentMood === 'Sad' ? '#EF4444' : '#F59E0B';
    donutChart = new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        datasets: [{
          data: [CHART_DATA.budgetPct, CHART_DATA.budgetRemaining],
          backgroundColor: [usedColor, isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)'],
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: false,
        cutout: '76%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        animation: { animateRotate: true, duration: 1200, easing: 'easeOutQuart' }
      }
    });
  }
}

// ===== SET TODAY AS DEFAULT DATE IN MODALS =====
function setDefaultDates() {
  const today = new Date().toISOString().split('T')[0];
  const dates = ['income_date', 'expense_date'];
  dates.forEach(id => {
    const el = document.getElementById(id);
    if (el && !el.value) el.value = today;
  });
}

// ===== CHECK SECTION FROM URL =====
function checkUrlSection() {
  const params = new URLSearchParams(window.location.search);
  const section = params.get('section');
  if (section) showSection(section);
  else showSection('overview');
}

// ===== HANDLE URL HASH =====
function handleHash() {
  const hash = window.location.hash.replace('#', '');
  if (hash && document.getElementById(hash)) showSection(hash);
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
  checkUrlSection();
  handleHash();
  animateOnLoad();
  setDefaultDates();

  // Small delay for charts (after section switch)
  setTimeout(() => {
    initCharts();
    // Trigger confetti if Happy and no confetti shown yet
    if (CHART_DATA.currentMood === 'Happy' && !sessionStorage.getItem('confetti_shown')) {
      setTimeout(triggerConfetti, 800);
      sessionStorage.setItem('confetti_shown', '1');
    }
  }, 100);
});

window.addEventListener('hashchange', handleHash);

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    if (notifPanel) notifPanel.style.display = 'none';
  }
});
