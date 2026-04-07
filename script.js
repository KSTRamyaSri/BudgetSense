'use strict';
    const CHART_DATA = <?= json_encode([
    'catLabels' => $cat_labels,
    'catAmounts' => $cat_amounts,
    'monthlyLabels' => array_column($monthly_data, 'label'),
    'monthlyAmounts' => array_column($monthly_data, 'amount'),
    'budgetPct' => $budget_pct,
    'month' => $month,
]) ?>;

    const MOOD_CLASS = '<?= $mood_class ?>';

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Handle Routing
        const params = new URLSearchParams(window.location.search);
        const section = params.get('section') || (window.location.hash ? window.location.hash.substring(1) :
            'overview');
        showSection(section);

        // 2. Initialize UI Components
        initSidebar();
        initNotifications();
        animateOnLoad();
        setDefaultDates();
        createMoodShower(MOOD_CLASS);

        // 3. Initialize Data & Charts
        refreshDashboardStats(); // This updates the text
        initCharts(); // This draws the canvas
    });

    async function updateLiveStats() {
        try {
            const response = await fetch(`get_stats.php?month=${CHART_DATA.month}`);
            const data = await response.json();

            if (data.success) {
                // Update total values on the cards
                // Note: Ensure your HTML elements have these IDs or classes
                console.log("Live Stats Updated:", data);
            }
        } catch (err) {
            console.error("Could not fetch live stats", err);
        }
    }

    // Run this when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        updateLiveStats();
    });

    function showSection(name) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById(name)?.classList.add('active');
        document.querySelector(`.nav-item[data-section="${name}"]`)?.classList.add('active');
    }

    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const sectionName = item.dataset.section;
            showSection(sectionName);
            history.pushState(null, '', `?month=${CHART_DATA.month}&section=${sectionName}`);
            if (window.innerWidth <= 900) {
                document.getElementById('sidebar')?.classList.remove('open');
                document.getElementById('sidebarBackdrop')?.classList.remove('show');
            }
        });
    });

    let isSidebarCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';

    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        document.getElementById('menuBtn')?.addEventListener('click', () => {
            sidebar.classList.add('open');
            backdrop.classList.add('show');
        });
        document.getElementById('sidebarClose')?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
        backdrop?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            isSidebarCollapsed = !isSidebarCollapsed;
            localStorage.setItem('sidebar_collapsed', isSidebarCollapsed);
            applySidebarState();
        });
        applySidebarState();
    }

    function applySidebarState() {
        if (window.innerWidth > 900) {
            document.body.classList.toggle('sidebar-is-collapsed', isSidebarCollapsed);
            document.getElementById('sidebar')?.classList.toggle('sidebar-collapsed', isSidebarCollapsed);
        }
    }
    window.addEventListener('resize', applySidebarState);

    function initNotifications() {
        const bell = document.getElementById('notifBell');
        const panel = document.getElementById('notifPanel');
        if (!bell || !panel) return;
        bell.addEventListener('click', e => {
            e.stopPropagation();
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', e => {
            if (!panel.contains(e.target) && e.target !== bell) panel.style.display = 'none';
        });
    }

    function createMoodShower(mood) {
        document.getElementById('mood-shower-container')?.remove();
        const container = document.createElement('div');
        container.id = 'mood-shower-container';
        document.body.insertAdjacentElement('afterbegin', container);
        const configs = {
            happy: {
                count: 40,
                emojis: ['😊', '🌸', '💖', '🎉', '✨']
            },
            sad: {
                count: 50,
                emojis: ['😢', '💧', '☂️'],
                isRain: true
            }
        };
        const config = configs[mood.toLowerCase()] || {};
        for (let i = 0; i < (config.count || 0); i++) {
            const p = document.createElement('div');
            p.className = config.isRain ? 'particle raindrop' : 'particle';
            if (!config.isRain) p.textContent = config.emojis[Math.floor(Math.random() * config.emojis.length)];
            p.style.left = (Math.random() * 105 - 5) + 'vw';
            p.style.animationDuration = (Math.random() * 8 + 4) + 's';
            p.style.animationDelay = (Math.random() * 5) + 's';
            container.appendChild(p);
        }
    }

    function openModal(id) {
        document.getElementById(id)?.classList.add('open');
    }

    function closeModal(id) {
        const overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.remove('open');
        const form = overlay.querySelector('form');
        if (form) form.reset();
        if (id === 'incomeModal') {
            document.getElementById('incomeModalTitle').textContent = 'Add Income';
            document.getElementById('incomeSubmitBtn').textContent = 'Add Income';
        }
        if (id === 'expenseModal') {
            document.getElementById('expenseModalTitle').textContent = 'Add Expense';
            document.getElementById('expenseSubmitBtn').textContent = 'Add Expense';
        }
    }
    document.addEventListener('click', e => {
        if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
    });

    function animateOnLoad() {
        const bar = document.getElementById('budgetBar');
        if (bar) setTimeout(() => {
            bar.style.width = bar.getAttribute('data-target');
        }, 300);
    }

    let toastTimer;

    function showToast(msg, type = 'info') {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className = `toast ${type} show`;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 3500);
    }

    function setDefaultDates() {
        const today = new Date().toISOString().split('T')[0];
        ['income_date', 'expense_date'].forEach(id => {
            const el = document.getElementById(id);
            if (el && !el.value) el.value = today;
        });
    }


    function initCharts() {
        // 1. Pie/Donut Chart (Spending by Category)
        const pieCtx = document.getElementById('pieChart')?.getContext('2d');
        if (pieCtx) {
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: CHART_DATA.catLabels,
                    datasets: [{
                        data: CHART_DATA.catAmounts,
                        backgroundColor: ['#ff90e8', '#ffc900', '#01d1ff', '#00f5d4', '#ff5c5c'],
                        borderWidth: 2,
                        borderColor: '#000'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                }
            });
        }

        // 2. Bar Chart (Monthly Expenses)
        const barCtx = document.getElementById('barChart')?.getContext('2d');
        if (barCtx) {
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: CHART_DATA.monthlyLabels,
                    datasets: [{
                        label: 'Expenses',
                        data: CHART_DATA.monthlyAmounts,
                        backgroundColor: '#ffc900',
                        borderColor: '#000',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Idi false unte chart container height ki adjust avthundi
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: {
                            position: 'bottom' // Legend valla height peruguthundi, so bottom ki marchu
                        }
                    }
                }
            });
        }

        // 3. Line Chart (Mood Trend)
        const moodCtx = document.getElementById('moodChart')?.getContext('2d');
        if (moodCtx) {
            new Chart(moodCtx, {
                type: 'line',
                data: {
                    labels: CHART_DATA.moodLabels,
                    datasets: [{
                        label: 'Mood Level',
                        data: CHART_DATA.moodData,
                        borderColor: '#01d1ff',
                        tension: 0.4,
                        fill: false,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Idi false unte chart container height ki adjust avthundi
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: {
                            position: 'bottom' // Legend valla height peruguthundi, so bottom ki marchu
                        }
                    }
                }
            });
        }
    }


    // async function refreshDashboardStats() {
    //     try {
    //         const response = await fetch(`get_stats.php?month=${CHART_DATA.month}`);
    //         const data = await response.json();
    //         if (data.error) return;

    //         // Update Stat Cards
    //         document.querySelector('.income-amt-val').textContent = '₹' + parseFloat(data.total_income)
    //             .toLocaleString();
    //         document.querySelector('.expense-amt-val').textContent = '₹' + parseFloat(data.total_expenses)
    //             .toLocaleString();

    //         const savingsEl = document.querySelector('.savings-amt-val');
    //         savingsEl.textContent = (data.savings < 0 ? '-' : '') + '₹' + Math.abs(data.savings)
    //             .toLocaleString();
    //         savingsEl.style.color = data.savings < 0 ? '#ef4444' : 'inherit';

    //         document.querySelector('.budget-limit-val').textContent = '₹' + parseFloat(data.budget_limit)
    //             .toLocaleString();

    //         if (data.message) document.querySelector('.mood-message').textContent = data.message;
    //     } catch (error) {
    //         console.error('Error fetching stats:', error);
    //     }
    // }


    async function refreshDashboardStats() {
        try {
            const response = await fetch(`get_stats.php?month=${CHART_DATA.month}`);
            const data = await response.json();
            if (data.error) return;

            // Update Stat Cards - Ensure these classes exist in your HTML
            const updateText = (selector, val) => {
                const el = document.querySelector(selector);
                if (el) el.textContent = '₹' + parseFloat(val).toLocaleString();
            };

            updateText('.income-amt-val', data.total_income);
            updateText('.expense-amt-val', data.total_expenses);
            updateText('.budget-limit-val', data.budget_limit);

            const savingsEl = document.querySelector('.savings-amt-val');
            if (savingsEl) {
                savingsEl.textContent = (data.savings < 0 ? '-' : '') + '₹' + Math.abs(data.savings)
                .toLocaleString();
                savingsEl.style.color = data.savings < 0 ? '#ff3f3f' : 'inherit';
            }

            // Use 'message' or 'suggestion' based on your PHP return key
            const msgEl = document.querySelector('.mood-message');
            if (msgEl) msgEl.textContent = data.message || data.suggestion || "";

        } catch (error) {
            console.error('Error fetching stats:', error);
        }
    }


    // CORRECTED FORM SUBMISSION LOGIC
    async function handleFormSubmit(url, data, modalId, submitBtnId) {
        const btn = document.getElementById(submitBtnId);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving…';
        }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.success) {
                showToast('Saved successfully!', 'success');
                if (modalId) closeModal(modalId);
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(json.error || 'Failed to save', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Submit';
                }
            }
        } catch {
            showToast('Network error', 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Submit';
            }
        }
    }

    document.getElementById('incomeForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const data = {
            id: document.getElementById('income_id').value,
            source: document.getElementById('income_source').value,
            amount: document.getElementById('income_amount').value,
            date: document.getElementById('income_date').value,
            note: document.getElementById('income_note').value
        };
        handleFormSubmit('add_income.php', data, 'incomeModal', 'incomeSubmitBtn');
    });

    document.getElementById('expenseForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const data = {
            id: document.getElementById('expense_id').value,
            title: document.getElementById('expense_title').value,
            category: document.getElementById('expense_category').value,
            amount: document.getElementById('expense_amount').value,
            date: document.getElementById('expense_date').value,
            note: document.getElementById('expense_note').value
        };
        handleFormSubmit('add_expense.php', data, 'expenseModal', 'expenseSubmitBtn');
    });

    document.getElementById('budgetForm')?.addEventListener('submit', e => {
        e.preventDefault();
        handleFormSubmit('update_budget.php', {
            amount: document.getElementById('budgetAmount').value,
            month: CHART_DATA.month
        }, null);
    });

    function deleteRecord(type, id) {
        if (confirm(`Delete this ${type}?`)) {
            handleFormSubmit('delete_expense.php', {
                type,
                id
            }, null);
        }
    }

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
