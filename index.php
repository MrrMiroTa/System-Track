<!DOCTYPE html>
<html lang="km">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ប្រព័ន្ធគ្រប់គ្រងចំណូលចំណាយ - Payment Tracker</title>
    <link rel="icon" href="uzita.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4">
</head>

<body>

    <!-- Authentication Overlay -->
    <div id="auth-overlay" class="auth-overlay">
        <div class="auth-card">
            <div class="auth-header">
                <h2 id="auth-title">Login</h2>
                <p id="auth-subtitle">Sign in to your account</p>
            </div>

            <form id="login-form" class="auth-form">
                <div class="form-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" required placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label for="login-password">Password (6 digits)</label>
                    <input type="password" id="login-password" required placeholder="Enter 6-digit password" maxlength="6">
                </div>
                <div id="login-error" class="auth-error" style="display:none;"></div>
                <button type="submit" class="btn-submit">Login</button>
            </form>

            <form id="signup-form" class="auth-form" style="display:none;">
                <div class="form-group">
                    <label for="signup-username">Username</label>
                    <input type="text" id="signup-username" required placeholder="Choose a username (3+ chars)">
                </div>
                <div class="form-group">
                    <label for="signup-password">Password (6 digits)</label>
                    <input type="password" id="signup-password" required placeholder="Enter 6-digit password" maxlength="6">
                </div>
                <div id="signup-error" class="auth-error" style="display:none;"></div>
                <button type="submit" class="btn-submit">Create Account</button>
            </form>

            <div class="auth-switch">
                <span id="auth-switch-text">Don't have an account?</span>
                <button id="auth-switch-btn" class="btn-link">Sign Up</button>
            </div>
        </div>
    </div>

    <!-- Main App -->
    <div id="main-app" class="container" style="display:none;">
        <header>
            <div>
                <h1>ប្រព័ន្ធគ្រប់គ្រងហិរញ្ញវត្ថុ (Payment Tracker)</h1>
                <p class="subtitle">តាមដានចំណូល និង ចំណាយប្រចាំថ្ងៃ</p>
            </div>
            <div class="header-actions">
                <button class="btn-export" onclick="openPdfReport()">បោះពុម្ភរបាយការណ៍ PDF</button>
                <span id="user-badge" class="user-badge" style="cursor:pointer;" onclick="openProfile()" title="View profile"></span>
                <button class="btn-logout" onclick="doLogout()">Logout</button>
            </div>
        </header>

        <!-- Mobile FAB -->
        <button id="fab-add" class="fab" style="display:none;" onclick="openMobileForm()">+</button>

        <!-- Admin Panel -->
        <div id="admin-panel" class="admin-panel" style="display:none;">
            <div class="card admin-card">
                <h3>Admin Panel - User Management</h3>
                <div class="table-container">
                    <table id="admin-users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-users-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Search / Filter Bar by Date Range -->
<div class="filter-bar">
             <span>ស្វែងរកតាមកាលបរិច្ឆេទ៖</span>
             <div class="filter-group">
                 <label for="filter-start-date">ចាប់ពីថ្ងៃ៖</label>
                 <input type="text" id="filter-start-date" placeholder="ឧ. 30-06-26 ឬ 2026-07-24" maxlength="20">
             </div>
             <div class="filter-group">
                 <label for="filter-end-date">ដល់ថ្ងៃ៖</label>
                 <input type="text" id="filter-end-date" placeholder="ឧ. 30-06-26 ឬ 2026-07-24" maxlength="20">
             </div>
             <div class="filter-group">
                 <label for="filter-username">អ្នកប្រើប្រាស់៖</label>
                 <input type="text" id="filter-username" placeholder="ស្វែងរកតាមឈ្មោះ" maxlength="100">
             </div>
             <button class="btn-clear">បង្ហាញទាំងអស់ (Reset)</button>
         </div>

        <!-- Summary Metrics Box Display -->
        <div class="dashboard-grid">
            <div class="card balance">
                <h3>💰 សមតុល្យសរុប (Total Balance)</h3>
                <div class="multi-currency-row"><span>KHR (រៀល)</span><div class="amount" id="balance-khr">0 ៛</div></div>
                <div class="multi-currency-row"><span>USD (ដុល្លារ)</span><div class="amount" id="balance-usd">$0.00</div></div>
            </div>
            <div class="card income">
                <h3>📈 ចំណូលសរុប (Total Income)</h3>
                <div class="multi-currency-row"><span>KHR (រៀល)</span><div class="amount" id="income-khr">0 ៛</div></div>
                <div class="multi-currency-row"><span>USD (ដុល្លារ)</span><div class="amount" id="income-usd">$0.00</div></div>
            </div>
            <div class="card expense">
                <h3>📉 ចំណាយសរុប (Total Expense)</h3>
                <div class="multi-currency-row"><span>KHR (រៀល)</span><div class="amount" id="expense-khr">0 ៛</div></div>
                <div class="multi-currency-row"><span>USD (ដុល្លារ)</span><div class="amount" id="expense-usd">$0.00</div></div>
            </div>
        </div>

        <div class="main-content">
            <!-- Data Creation Area Container -->
            <div class="card">
                <h2 class="form-title">បន្ថែមប្រតិបត្តិការថ្មី</h2>
                <form id="transaction-form">
                    <div class="form-group">
                        <label for="title">បរិយាយ / ឈ្មោះប្រតិបត្តិការ</label>
                        <input type="text" id="title" required placeholder="ឧ. បើកប្រាក់ខែ, ទិញម្ហូប...">
                    </div>
                    <div class="form-group">
                        <label for="amount">ចំនួនទឹកប្រាក់</label>
                        <div class="amount-input-group">
                            <input type="text" id="amount" step="any" required placeholder="0.00">
                            <select id="currency" required>
                                <option value="KHR">រៀល (៛)</option>
                                <option value="USD">ដុល្លារ ($)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="type">ប្រភេទប្រតិបត្តិការ</label>
                        <select id="type" required>
                            <option value="income">ចំណូល (Income)</option>
                            <option value="expense">ចំណាយ (Expense)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">ប្រភេទក្រុម (Category)</label>
                        <input type="text" id="category" required placeholder="ឧ. ម្ហូបអាហារ, ផ្ទះបាយ, ធ្វើដំណើរ...">
                    </div>
                    <div class="form-group">
                        <label for="date">កាលបរិច្ឆេទ</label>
                        <input type="date" id="date" required>
                    </div>
                    <button type="submit" class="btn-submit">រក្សាទុកទិន្នន័យ (Save)</button>​
                </form>
            </div>

            <!-- Ledger Record Presentation Component -->
<div class="table-container">
                 <div class="loader-wrapper" id="table-loader">
                     <div class="spinner"></div>
                     <p>កំពុងទាញយកទិន្នន័យ...</p>
                 </div>
                 <div class="table-scroll">
                     <table id="transactions-table">
                         <thead>
                             <tr>
                                 <th>កាលបរិច្ឆេទ</th>
                                 <th>បរិយាយ</th>
                                 <th>អ្នកបន្ថែម</th>
                                 <!-- <th>ក្រុម</th> -->
                                 <th>ប្រភេទ</th>
                                 <th>ចំនួនទឹកប្រាក់</th>
                                 <th>សកម្មភាព</th>
                             </tr>
                         </thead>
                         <tbody id="transaction-rows">
                             <!-- Content loaded dynamically from database -->
                         </tbody>
                     </table>
                 </div>
                 <div class="pagination" id="pagination">
                     <button class="btn-page" id="btn-prev" onclick="changePage(-1)">‹ Prev</button>
                     <span class="page-info" id="page-info"></span>
                     <button class="btn-page" id="btn-next" onclick="changePage(1)">Next ›</button>
                 </div>
             </div>
        </div>

        <!-- Footer -->
        <footer class="app-footer">
            <div class="footer-inner">
                <span class="footer-brand">Payment Tracker By <a target="_blank" href="https://t.me/Dsophors">UziTa</a></span>
                <span class="footer-text">ប្រព័ន្ធគ្រប់គ្រងហិរញ្ញវត្ថុ</span>
                <span class="footer-copy">&copy; 2026 Payment Tracker. All rights reserved.</span>
            </div>
        </footer>
    </div>

    <!-- Mobile Transaction Form Modal -->
    <div id="mobile-form-modal" class="modal-overlay" style="display:none;">
        <div class="modal-card modal-card-wide">
            <div class="modal-header">
                <h3>បន្ថែមប្រតិបត្តិការថ្មី</h3>
                <button class="modal-close" onclick="closeMobileForm()">✕</button>
            </div>
            <div class="modal-body">
                <form id="mobile-transaction-form">
                    <div class="form-group">
                        <label for="mobile-title">បរិយាយ / ឈ្មោះប្រតិបត្តិការ</label>
                        <input type="text" id="mobile-title" required placeholder="ឧ. បើកប្រាក់ខែ, ទិញម្ហូប...">
                    </div>
                    <div class="form-group">
                        <label for="mobile-amount">ចំនួនទឹកប្រាក់</label>
                        <div class="amount-input-group">
                            <input type="text" id="mobile-amount" step="any" required placeholder="0.00">
                            <select id="mobile-currency" required>
                                <option value="KHR">រៀល (៛)</option>
                                <option value="USD">ដុល្លារ ($)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mobile-type">ប្រភេទប្រតិបត្តិការ</label>
                        <select id="mobile-type" required>
                            <option value="income">ចំណូល (Income)</option>
                            <option value="expense">ចំណាយ (Expense)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mobile-category">ប្រភេទក្រុម (Category)</label>
                        <input type="text" id="mobile-category" required placeholder="ឧ. ម្ហូបអាហារ, ផ្ទះបាយ...">
                    </div>
                    <div class="form-group">
                        <label for="mobile-date">កាលបរិច្ឆេទ</label>
                        <input type="date" id="mobile-date" required>
                    </div>
                    <button type="submit" class="btn-submit">រក្សាទុកទិន្នន័យ (Save)</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';
        let allTransactions = [];
        let currentFilteredTransactions = [];
        let currentPage = 1;
        const rowsPerPage = 10;

        const form = document.getElementById('transaction-form');

        // Dashboard metric definitions
        const balanceKhrEl = document.getElementById('balance-khr');
        const balanceUsdEl = document.getElementById('balance-usd');
        const incomeKhrEl = document.getElementById('income-khr');
        const incomeUsdEl = document.getElementById('income-usd');
        const expenseKhrEl = document.getElementById('expense-khr');
        const expenseUsdEl = document.getElementById('expense-usd');

const tableRowsEl = document.getElementById('transaction-rows');
         const startDateInput = document.getElementById('filter-start-date');
         const endDateInput = document.getElementById('filter-end-date');
         const usernameInput = document.getElementById('filter-username');

        document.getElementById('date').valueAsDate = new Date();

        function formatCurrency(value, currency) {
            if (currency === 'KHR') {
                const num = Math.round(value);
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(num) + ' ៛';
            } else {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency', currency: 'USD'
                }).format(value);
            }
        }

        function formatDateTime(isoString) {
            if (!isoString) return '';
            var d = isoString.split(/[- :]/);
            return d[2] + '-' + d[1] + '-' + d[0].slice(-2) + ' ' + d[3] + ':' + d[4];
        }

        async function fetchDashboardData() {
            const loader = document.getElementById('table-loader');
            loader.classList.add('active');
            tableRowsEl.innerHTML = '';

            try {
                const response = await fetch(API_URL);

                if (response.status === 304) {
                    applyDateFilter();
                    loader.classList.remove('active');
                    return;
                }

                const data = await response.json();
                allTransactions = Array.isArray(data) ? data : [];

                if (data.error) {
                    tableRowsEl.innerHTML = `<tr><td colspan="7" class="empty-state">${escapeHtml(data.error)}</td></tr>`;
                    return;
                }

                applyDateFilter();
            } catch (error) {
                tableRowsEl.innerHTML = `<tr><td colspan="7" class="empty-state">កំពុងមានបញ្ហា​ក្នុងការតភ្ជាប់។ សូមព្យាយាមម្តងទៀត។</td></tr>`;
            } finally {
                loader.classList.remove('active');
            }
        }

        function debounce(fn, delay) {
            let timer;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        }

        const debouncedFilter = debounce(applyDateFilter, 250);
        const debouncedClear = debounce(clearDateFilter, 250);

        startDateInput.addEventListener('input', debouncedFilter);
        endDateInput.addEventListener('input', debouncedFilter);
        usernameInput.addEventListener('input', debouncedFilter);

        document.querySelector('.btn-clear').addEventListener('click', debouncedClear);

        function parseInputDate(value) {
            value = value.trim();
            if (!value) return '';

            const normalized = value.replace(/\//g, '-');

            if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
                return normalized;
            }

            const parts = normalized.split('-');
            if (parts.length === 3) {
                const [dd, mm, yy] = parts;
                if (dd.length === 2 && mm.length === 2 && yy.length === 2) {
                    const yyyy = parseInt(yy) > 70 ? '19' + yy : '20' + yy;
                    return `${yyyy}-${mm}-${dd}`;
                }
                if (dd.length === 2 && mm.length === 2 && yy.length === 4) {
                    return `${yy}-${mm}-${dd}`;
                }
            }

            return '';
        }

function applyDateFilter() {
              const startDate = parseInputDate(startDateInput.value);
              const endDate = parseInputDate(endDateInput.value);
              const usernameFilter = usernameInput.value.trim().toLowerCase();

              currentFilteredTransactions = allTransactions.filter(tx => {
                  if (startDate && tx.date < startDate) return false;
                  if (endDate && tx.date > endDate) return false;
                  if (usernameFilter && !(tx.username && tx.username.toLowerCase().includes(usernameFilter))) return false;
                  return true;
              });

              currentPage = 1;
              renderDashboard(currentFilteredTransactions);
          }

          function clearDateFilter() {
              startDateInput.value = '';
              endDateInput.value = '';
              usernameInput.value = '';
              currentFilteredTransactions = allTransactions;
              currentPage = 1;
              renderDashboard(allTransactions);
          }

        function changePage(delta) {
            currentPage += delta;
            if (currentPage < 1) currentPage = 1;
            renderDashboard(currentFilteredTransactions);
        }

        function renderDashboard(transactions) {
            let incKhr = 0, expKhr = 0, incUsd = 0, expUsd = 0;

            transactions.forEach(tx => {
                const amt = parseFloat(tx.amount);
                const isIncome = tx.type === 'income';
                const curr = tx.currency || 'KHR';
                if (curr === 'KHR') {
                    if (isIncome) incKhr += amt; else expKhr += amt;
                } else {
                    if (isIncome) incUsd += amt; else expUsd += amt;
                }
            });

            const totalPages = Math.max(1, Math.ceil(transactions.length / rowsPerPage));
            if (currentPage > totalPages) currentPage = totalPages;
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageTransactions = transactions.slice(start, end);

            if (pageTransactions.length === 0) {
                tableRowsEl.innerHTML = `<tr><td colspan="7" class="empty-state"><span class="empty-state-icon">📭</span>មិនមានទិន្នន័យទេ។</td></tr>`;
            } else {
                let rows = [];
                pageTransactions.forEach(tx => {
                    const amt = parseFloat(tx.amount);
                    const isIncome = tx.type === 'income';
                    const curr = tx.currency || 'KHR';
                    rows.push(`
                        <tr data-id="${tx.id}">
                            <td data-label="កាលបរិច្ឆេទ">${formatDateTime(tx.created_at)}</td>
                            <td data-label="បរិយាយ"><strong>${escapeHtml(tx.title)}</strong></td>
                            <td data-label="អ្នកបន្ថែម">${escapeHtml(tx.username || '-')}</td>
                            <td data-label="ប្រភេទ"><span class="badge ${tx.type}">${isIncome ? 'ចំណូល' : 'ចំណាយ'}</span></td>
                            <td data-label="ចំនួនទឹកប្រាក់" class="${isIncome ? 'text-income' : 'text-expense'}" translate="no">${formatCurrency(amt, curr)}</td>
                            <td data-label="សកម្មភាព"><button class="btn-dot" onclick="openActionModal(${tx.id})">⋯</button></td>
                        </tr>
                    `);
                });
                tableRowsEl.innerHTML = rows.join('');
            }

            incomeKhrEl.innerHTML = '<span translate="no">' + formatCurrency(incKhr, 'KHR') + '</span>';
            expenseKhrEl.innerHTML = '<span translate="no">' + formatCurrency(expKhr, 'KHR') + '</span>';
            balanceKhrEl.innerHTML = '<span translate="no">' + formatCurrency(incKhr - expKhr, 'KHR') + '</span>';

            incomeUsdEl.innerHTML = '<span translate="no">' + formatCurrency(incUsd, 'USD') + '</span>';
            expenseUsdEl.innerHTML = '<span translate="no">' + formatCurrency(expUsd, 'USD') + '</span>';
            balanceUsdEl.innerHTML = '<span translate="no">' + formatCurrency(incUsd - expUsd, 'USD') + '</span>';

            document.getElementById('page-info').textContent = 'Page ' + currentPage + ' of ' + totalPages;
            document.getElementById('btn-prev').disabled = currentPage <= 1;
            document.getElementById('btn-next').disabled = currentPage >= totalPages;
        }

        function showAlert(message, type) {
            type = type || 'error';
            const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠' : '✕';
            const overlay = document.createElement('div');
            overlay.className = 'custom-alert-overlay';
            overlay.innerHTML = '<div class="custom-alert-card"><div class="custom-alert-header ' + type + '"><span class="alert-icon">' + icon + '</span><h4>' + (type === 'success' ? 'Success' : type === 'warning' ? 'Warning' : 'Error') + '</h4></div><div class="custom-alert-body">' + escapeHtml(message) + '</div><div class="custom-alert-footer"><button class="btn-ok" onclick="this.closest(\'.custom-alert-overlay\').remove()">OK</button></div></div>';
            document.body.appendChild(overlay);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) overlay.remove();
            });
        }

        function showConfirm(message) {
            return new Promise(function(resolve) {
                const overlay = document.createElement('div');
                overlay.className = 'custom-confirm-overlay';
                overlay.innerHTML = '<div class="custom-confirm-card"><div class="custom-confirm-body">' + escapeHtml(message) + '</div><div class="custom-confirm-footer"><button class="btn-confirm-no">No</button><button class="btn-confirm-yes">Yes</button></div></div>';
                document.body.appendChild(overlay);
                overlay.querySelector('.btn-confirm-no').addEventListener('click', function() { overlay.remove(); resolve(false); });
                overlay.querySelector('.btn-confirm-yes').addEventListener('click', function() { overlay.remove(); resolve(true); });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) { overlay.remove(); resolve(false); }
                });
            });
        }

        function showPrompt(title, message) {
            return new Promise(function(resolve) {
                const overlay = document.createElement('div');
                overlay.className = 'custom-confirm-overlay';
                overlay.innerHTML = '<div class="custom-confirm-card"><div class="custom-confirm-header" style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)"><h4 style="font-size:0.9rem;font-weight:700">' + escapeHtml(title) + '</h4></div><div class="custom-confirm-body"><p>' + escapeHtml(message) + '</p><input type="text" class="edit-input" id="prompt-input" style="width:100%;margin-top:0.5rem;" placeholder="Enter password"></div><div class="custom-confirm-footer"><button class="btn-refirm-no">Cancel</button><button class="btn-confirm-yes">OK</button></div></div>';
                document.body.appendChild(overlay);
                const input = overlay.querySelector('#prompt-input');
                input.focus();
                overlay.querySelector('.btn-refirm-no').addEventListener('click', function() { overlay.remove(); resolve(null); });
                overlay.querySelector('.btn-confirm-yes').addEventListener('click', function() { resolve({ value: input.value }); });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) { overlay.remove(); resolve(null); }
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') { resolve({ value: input.value }); }
                    if (e.key === 'Escape') { overlay.remove(); resolve(null); }
                });
            });
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = form.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'កំពុងរក្សាទុក...';

            const payload = {
                title: document.getElementById('title').value,
                amount: document.getElementById('amount').value,
                currency: document.getElementById('currency').value,
                type: document.getElementById('type').value,
                category: document.getElementById('category').value,
                date: document.getElementById('date').value
            };

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    form.reset();
                    document.getElementById('date').valueAsDate = new Date();
                    fetchDashboardData();
                } else {
                    showAlert(result.error || "Failed to save records.");
                }
            } catch (error) {
                console.error("Transmission writing fault encountered:", error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });

        function escapeHtml(str) {
            if (typeof str !== 'string') str = String(str);
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function enableEdit(id) {
            const row = document.querySelector('tr[data-id="' + id + '"]');
            const tx = allTransactions.find(function(t) { return t.id === id; });
            if (!row || !tx) return;

            row.innerHTML = '<td class="edit-cell" data-label="កាលបរិច្ឆេទ"><input type="date" class="edit-input" id="edit-date-' + id + '" value="' + tx.date + '"></td>' +
                '<td class="edit-cell" data-label="បរិយាយ"><input type="text" class="edit-input" id="edit-title-' + id + '" value="' + escapeHtml(tx.title) + '"></td>' +
                '<td class="edit-cell" data-label="ក្រុម"><input type="text" class="edit-input" id="edit-category-' + id + '" value="' + escapeHtml(tx.category) + '"></td>' +
                '<td class="edit-cell" data-label="ប្រភេទ"><select class="edit-select" id="edit-type-' + id + '"><option value="income"' + (tx.type === 'income' ? ' selected' : '') + '>ចំណូល</option><option value="expense"' + (tx.type === 'expense' ? ' selected' : '') + '>ចំណាយ</option></select></td>' +
                '<td class="edit-cell" data-label="ចំនួនទឹកប្រាក់"><div class="edit-amount-group"><input type="text" class="edit-input" id="edit-amount-' + id + '" step="any" value="' + tx.amount + '"><select class="edit-select" id="edit-currency-' + id + '"><option value="KHR"' + (tx.currency === 'KHR' ? ' selected' : '') + '>៛</option><option value="USD"' + (tx.currency === 'USD' ? ' selected' : '') + '>$</option></select></div></td>' +
                '<td class="edit-cell" data-label="សកម្មភាព"><div class="edit-actions"><button class="btn-save" onclick="saveEdit(' + id + ')">រក្សាទុក</button><button class="btn-cancel" onclick="cancelEdit()">បោះបង់</button></div></td>';
        }

        async function saveEdit(id) {
            const payload = {
                title: document.getElementById('edit-title-' + id).value,
                amount: document.getElementById('edit-amount-' + id).value,
                currency: document.getElementById('edit-currency-' + id).value,
                type: document.getElementById('edit-type-' + id).value,
                category: document.getElementById('edit-category-' + id).value,
                date: document.getElementById('edit-date-' + id).value
            };

            try {
                const response = await fetch(API_URL + '?id=' + id, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (result.success) {
                    fetchDashboardData();
                } else {
                    showAlert(result.error || "Failed to update transaction.");
                    cancelEdit();
                }
            } catch (error) {
                console.error("Update failed:", error);
                showAlert("Update failed. Please try again.");
                cancelEdit();
            }
        }

        function cancelEdit() {
            fetchDashboardData();
        }

        async function checkAuth() {
            try {
                const response = await fetch(`${API_URL}/auth-check`);
                const data = await response.json();
                if (data.authenticated) {
                    showApp(data.user);
                    fetchDashboardData();
                } else {
                    showAuth();
                }
            } catch (error) {
                showAuth();
            }
        }

        function showAuth() {
            document.getElementById('auth-overlay').style.display = 'flex';
            document.getElementById('main-app').style.display = 'none';
            document.getElementById('login-form').style.display = 'block';
            document.getElementById('signup-form').style.display = 'none';
            document.getElementById('auth-title').textContent = 'Login';
            document.getElementById('auth-subtitle').textContent = 'Sign in to your account';
            document.getElementById('auth-switch-text').textContent = 'Don\'t have an account?';
            document.getElementById('auth-switch-btn').textContent = 'Sign Up';
            document.getElementById('auth-switch-btn').onclick = toggleAuthForm;
        }

        function showApp(user) {
            document.getElementById('auth-overlay').style.display = 'none';
            document.getElementById('main-app').style.display = 'block';
            const badge = document.getElementById('user-badge');
            badge.textContent = `${user.username} (${user.role})`;
            if (user.role === 'admin') {
                document.getElementById('admin-panel').style.display = 'block';
                fetchAdminUsers();
            } else {
                document.getElementById('admin-panel').style.display = 'none';
            }
        }

        function toggleAuthForm() {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                document.getElementById('auth-title').textContent = 'Login';
                document.getElementById('auth-subtitle').textContent = 'Sign in to your account';
                document.getElementById('auth-switch-text').textContent = 'Don\'t have an account?';
                document.getElementById('auth-switch-btn').textContent = 'Sign Up';
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
                document.getElementById('auth-title').textContent = 'Sign Up';
                document.getElementById('auth-subtitle').textContent = 'Create a new account';
                document.getElementById('auth-switch-text').textContent = 'Already have an account?';
                document.getElementById('auth-switch-btn').textContent = 'Login';
            }
            hideAuthErrors();
        }

        function hideAuthErrors() {
            document.getElementById('login-error').style.display = 'none';
            document.getElementById('signup-error').style.display = 'none';
        }

        function showAuthError(formType, message) {
            const el = document.getElementById(formType === 'login' ? 'login-error' : 'signup-error');
            el.textContent = message;
            el.style.display = 'block';
        }

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAuthErrors();
            const username = document.getElementById('login-username').value.trim();
            const password = document.getElementById('login-password').value.trim();
            if (!username || !password) return;

            try {
                const response = await fetch(`${API_URL}/signin`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify({ username, password })
                });
                const data = await response.json();
                if (data.error) {
                    showAuthError('login', data.error);
                } else if (data.success) {
                    showApp(data.user);
                    fetchDashboardData();
                }
            } catch (error) {
                showAuthError('login', 'Connection failed. Please try again.');
            }
        });

        document.getElementById('signup-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAuthErrors();
            const username = document.getElementById('signup-username').value.trim();
            const password = document.getElementById('signup-password').value.trim();
            if (!username || !password) return;

            if (password.length !== 6 || !/^\d{6}$/.test(password)) {
                showAuthError('signup', 'Password must be exactly 6 digits.');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/signup`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify({ username, password })
                });
                const data = await response.json();
                if (data.error) {
                    showAuthError('signup', data.error);
                } else if (data.success) {
                    document.getElementById('auth-title').textContent = 'Account Created';
                    document.getElementById('auth-subtitle').textContent = 'Please login with your new account.';
                    showAuthError('signup', '');
                    toggleAuthForm();
                }
            } catch (error) {
                showAuthError('signup', 'Connection failed. Please try again.');
            }
        });

        function doLogout() {
            fetch(`${API_URL}/logout`, { method: 'POST' }).finally(() => {
                location.reload();
            });
        }

        async function fetchAdminUsers() {
            try {
                const response = await fetch(`${API_URL}/admin/users`);
                if (!response.ok) {
                    const text = await response.text();
                    let message = 'Failed to fetch users.';
                    try {
                        const err = JSON.parse(text);
                        if (err.error) message = err.error;
                    } catch (_) {}
                    throw new Error(message);
                }
                const users = await response.json();
                if (!Array.isArray(users)) {
                    throw new Error('Unexpected response format.');
                }
                const tbody = document.getElementById('admin-users-body');
                tbody.innerHTML = '';
                if (users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><span class="empty-state-icon">👥</span>មិ�មានអ្នកប្រើប្រាស់ទេ។</td></tr>';
                    return;
                }
                users.forEach(u => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td data-label="ID">${escapeHtml(u.id)}</td>
                        <td data-label="Username"><strong>${escapeHtml(u.username)}</strong></td>
                        <td data-label="Role"><span class="badge ${u.role === 'admin' ? 'income' : 'expense'}">${u.role}</span></td>
                        <td data-label="Created">${u.created_at ? u.created_at.substring(0, 10) : '-'}</td>
                        <td data-label="Actions"><button class="btn-edit" onclick="resetAdminPassword(${u.id}, '${escapeHtml(u.username)}')">Reset Password</button></td>
                    `;
                    tbody.appendChild(row);
                });
            } catch (error) {
                console.error('Failed to fetch users:', error);
                showAlert('មិនអាចទាញយកថ្នាក់អ្នកប្រើប្រាស់។ សូមព្យាយាមម្តងទៀត។', 'error');
            }
        }

        async function resetAdminPassword(userId, username) {
            const result = await showPrompt('Reset password', 'Enter a new 6-digit password for "' + username + '":');
            if (result === null) return;
            const newPassword = result.value;
            if (!/^\d{6}$/.test(newPassword)) {
                showAlert('Password must be exactly 6 digits.', 'warning');
                return;
            }
            const confirmed = await showConfirm('Set password for "' + username + '" to "' + newPassword + '"?');
            if (!confirmed) return;

            try {
                const response = await fetch(`${API_URL}/admin/reset-password?id=${userId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify({ password: newPassword })
                });
                const data = await response.json();
                if (data.success) {
                    showAlert('Password reset successfully.', 'success');
                } else {
                    showAlert(data.error || 'Failed to reset password.');
                }
            } catch (error) {
                showAlert('Failed to reset password. Please try again.');
            }
        }

        async function confirmDelete(id) {
            const confirmed = await showConfirm('តើលំហាត់លុបប្រតិបត្តិការនេះ?');
            if (!confirmed) return;
            try {
                const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
                    fetchDashboardData();
                } else {
                    showAlert(result.error || 'Failed to delete transaction.');
                }
            } catch (error) {
                console.error('Delete failed:', error);
                showAlert('Delete failed. Please try again.');
            }
        }

        function openActionModal(id) {
            actionModalTargetId = id;
            const modal = document.getElementById('action-modal');
            modal.style.display = 'flex';
        }

        function closeActionModal() {
            const modal = document.getElementById('action-modal');
            modal.style.display = 'none';
            actionModalTargetId = null;
        }

        let actionModalTargetId = null;

        function handleModalEdit() {
            var id = actionModalTargetId;
            closeActionModal();
            if (id !== null) {
                enableEdit(id);
            }
        }

        function handleModalDelete() {
            var id = actionModalTargetId;
            closeActionModal();
            if (id !== null) {
                confirmDelete(id);
            }
        }

        function openPdfReport() {
            window.open('pdf.php', '_blank');
        }

        function openProfile() {
            window.open('profile.php', '_blank');
        }

        function openMobileForm() {
            const modal = document.getElementById('mobile-form-modal');
            modal.style.display = 'flex';
            document.getElementById('mobile-date').valueAsDate = new Date();
        }

        function closeMobileForm() {
            document.getElementById('mobile-form-modal').style.display = 'none';
        }

        document.getElementById('mobile-transaction-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'កំពុងរក្សាទុក...';

            const payload = {
                title: document.getElementById('mobile-title').value,
                amount: document.getElementById('mobile-amount').value,
                currency: document.getElementById('mobile-currency').value,
                type: document.getElementById('mobile-type').value,
                category: document.getElementById('mobile-category').value,
                date: document.getElementById('mobile-date').value
            };

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (result.success) {
                    e.target.reset();
                    document.getElementById('mobile-date').valueAsDate = new Date();
                    closeMobileForm();
                    fetchDashboardData();
                    showAlert('Transaction added successfully.', 'success');
                } else {
                    showAlert(result.error || "Failed to save records.");
                }
            } catch (error) {
                console.error("Transmission writing fault encountered:", error);
                showAlert("Connection failed. Please try again.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });

        document.getElementById('mobile-form-modal').addEventListener('click', function(e) {
            if (e.target === this) closeMobileForm();
        });

        document.addEventListener('DOMContentLoaded', function() {
            var actionModal = document.getElementById('action-modal');
            if (actionModal) {
                actionModal.addEventListener('click', function(e) {
                    if (e.target === actionModal) closeActionModal();
                });
            }
        });

        checkAuth();
    </script>

    <!-- Action Modal -->
    <div id="action-modal" class="modal-overlay" style="display:none;">
        <div class="modal-card">
            <div class="modal-header">
                <h3>សកម្មភាព</h3>
                <button class="modal-close" onclick="closeActionModal()">✕</button>
            </div>
            <div class="modal-body">
                <button class="modal-action-btn modal-edit" onclick="handleModalEdit()">
                    <span class="modal-icon">✎</span>
                    <span>កែសម្រួល</span>
                </button>
                <button class="modal-action-btn modal-delete" onclick="handleModalDelete()">
                    <span class="modal-icon">✕</span>
                    <span>លុប</span>
                </button>
            </div>
        </div>
    </div>

</body>

</html>