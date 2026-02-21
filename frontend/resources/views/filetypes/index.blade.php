<x-app-layout>
    <!-- USER MANAGEMENT SECTION-->
    <main class="p-4 lg:p-6 view-section active" id="user-management-view">

        <div class="mb-6 animate-in stagger-1">
            <!-- Main Container: items-start helps align H1/P better on desktop -->
            <div class="flex flex-wrap sm:flex-nowrap items-start sm:items-center justify-between gap-y-4 gap-x-4">

                <!-- 1. Header & Description (Always Order 1) -->
                <div class="order-1">
                    <h1 class="font-display text-2xl lg:text-3xl font-bold text-[var(--fg-primary)]">
                        File Type
                    </h1>
                    <!-- Visible only on Desktop -->
                    <p class="hidden sm:block text-[var(--fg-secondary)] mt-1">
                        Used for managing file type.
                    </p>
                </div>

                <!-- 2. The Button 
              Mobile: order-2 (Top Right)
              Desktop: order-3 (Far Right) -->
                <button type="button" onclick="openModal('add')"
                    class="order-2 sm:order-3 btn-primary inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold transition-all hover:brightness-110 active:scale-95 whitespace-nowrap">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    <span>Add Type</span>
                </button>

                <!-- 3. Selects Wrapper
              Mobile: order-3 & w-full (Forces it to Row 2)
              Desktop: order-2, w-auto, and sm:ml-auto (Pushes itself and the button to the right) -->
                <div class="order-3 sm:order-2 w-full sm:w-auto sm:ml-auto flex items-center gap-3">

                </div>
            </div>
        </div>
        @if(session('success'))
            <div class="px-4 py-3 rounded mb-4 border bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-4 shadow" style="background-color: var(--bg-success); border-color: var(--border-success); color: var(--fg-success);" role="alert">
                <strong>Success!</strong> {{ session('success') }}
            </div>
        @endif
        <!-- Table Section  -->

        <div class="card overflow-hidden">
            <style>
                /* Custom Checkbox */
                .custom-checkbox {
                    appearance: none;
                    width: 18px;
                    height: 18px;
                    border: 1px solid var(--border);
                    border-radius: 4px;
                    background: var(--bg-secondary);
                    cursor: pointer;
                    position: relative;
                    transition: all 0.2s ease;
                }

                .custom-checkbox:checked {
                    background: var(--accent);
                    border-color: var(--accent);
                }

                .custom-checkbox:checked::after {
                    content: '';
                    position: absolute;
                    left: 5px;
                    top: 2px;
                    width: 5px;
                    height: 10px;
                    border: solid var(--bg-primary);
                    border-width: 0 2px 2px 0;
                    transform: rotate(45deg);
                }

                .modal-overlay {
                    backdrop-filter: blur(4px);
                    transition: opacity 0.3s ease;
                    display: none;
                    position: fixed;
                    inset: 0;
                    z-index: 200;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                    background: rgba(0, 0, 0, 0.8);
                }

                .modal-overlay.active {
                    display: flex;
                }
            </style>
            <!-- Toolbar -->
            <div class="p-4 border-b border-[var(--border)]">
                <div class="grid grid-cols-10 lg:flex lg:items-center gap-3">

                    <!-- Search (70% mobile) -->
                    <div class="col-span-7 lg:flex-1 relative">
                        <input type="search" id="searchInputTable" placeholder="Search by name..." class="input-field w-full">
                    </div>

                    <!-- Export (30% mobile) -->
                    <div class="col-span-3 lg:order-last">
                        <button class="btn-ghost w-full py-3 px-1 text-sm sm:text-xs lg:px-4 lg:w-auto">Export CSV</button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-[var(--bg-secondary)] text-[var(--fg-secondary)] text-sm border-b border-[var(--border)]">
                        <tr>
                            <th class="p-4 w-12"><input type="checkbox" class="custom-checkbox"></th>
                            <th class="p-4 font-medium">File Type</th>
                            <th class="p-4 font-medium">Status</th>
                            <th class="p-4 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                    </tbody>
                </table>
            </div>


            <div class="modal-overlay" id="universalModal"></div>



            @php
            // Prepare frontend-ready array
            $filetypes = $filetypes->map(function($filetype) {
            return [
            'id' => $filetype->id,
            'name' => $filetype->name,
            'status' => ucfirst($filetype->status),
            'avatar' => $filetype->name // or any unique seed
            ];
            });
            @endphp

            <script>
                let filetypes = @json($filetypes);
                let deleteId = null;

                function renderTable(data) {
                    const body = document.getElementById('userTableBody');

                    body.innerHTML = data.map(u => `
                  <tr class="table-row border-b border-[var(--border)] last:border-0">
                      <td class="p-4"><input type="checkbox" class="custom-checkbox"></td>
                      <td class="p-4">
                          <div class="flex items-center gap-3">
                              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=${u.avatar}" class="w-9 h-9 rounded-full bg-[var(--bg-secondary)]">
                              <div><p class="text-sm font-medium">${u.name}</p></div>
                          </div>
                      </td>
                        <td class="p-4">
                        <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-md ${
                            u.status == 1
                                ? 'bg-emerald-500/10 text-emerald-400'
                                : 'bg-gray-500/10 text-gray-400'
                        }">
                            ${u.status == 1 ? 'Active' : 'Inactive'}
                        </span>
                        </td>
                      <td class="p-4 text-right space-x-1">
                          <button onclick="openModal('show', ${u.id})" class="p-2 text-[var(--fg-muted)] hover:text-white transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></button>
                          <button onclick="openModal('edit', ${u.id})" class="p-2 text-[var(--accent)] hover:bg-[var(--accent)]/10 rounded-md transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                          <button onclick="openModal('delete', ${u.id})" class="p-2 text-red-500 hover:bg-red-500/10 rounded-md transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                      </td>
                  </tr>
              `).join('');
                }

                function closeAllModals() {
                    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
                }


                function openModal(type, id = null) {
                    let url;

                    switch (type) {
                        case 'add':
                            url = `/filetypes/create`; // route to return add modal HTML
                            break;
                        case 'edit':
                            url = `/filetypes/${id}/edit`;
                            break;
                        case 'delete':
                            url = `/filetypes/${id}/show`; // reuse show or delete partial
                            break;
                        case 'show':
                        default:
                            url = `/filetypes/${id}/show`;
                            break;
                    }

                    fetch(url)
                        .then(res => res.text())
                        .then(html => {
                            const modal = document.getElementById('universalModal');

                            if (type === 'delete') {
                                modal.innerHTML = `
                <div class="bg-[var(--bg-card)] border border-[var(--border)] w-full max-w-xs rounded-xl p-6 text-center">
                    <div class="w-16 h-16 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">Delete File Type?</h3>
                    <p class="text-sm text-[var(--fg-muted)] mb-6">Remove this file type?</p>
                    <div class="space-y-2">
                        <button id="confirmDeleteBtn" class="btn-danger w-full">Delete</button>
                        <button class="btn-ghost w-full p-2" onclick="closeModal()">Cancel</button>
                    </div>
                </div>`;
                            } else {
                                modal.innerHTML = html; // show, edit, or add partial
                            }

                            modal.classList.add('active');

                            // Attach form submit for add/edit
                            if (type === 'edit' || type === 'add') {
                                const form = document.getElementById('modalForm');
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    const id = document.getElementById('fileTypeId')?.value; // may be null for add
                                    const name = document.getElementById('fileTypeName').value;
                                    const status = document.getElementById('fileTypeStatus').value;

                                    const method = type === 'edit' ? 'PUT' : 'POST';
                                    const url = type === 'edit' ? `/filetypes/${id}` : `/filetypes`;

                                    fetch(url, {
                                        method: method,
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({
                                            name,
                                            status
                                        })
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            renderTable(filetypes);
                                            closeModal();

                                            // Optional: show success alert
                                            showAlert('File Type saved successfully!', 'success');
                                        }
                                    });
                                });
                            }

                            // Attach delete handler
                            if (type === 'delete') {
                                const btn = document.getElementById('confirmDeleteBtn');
                                btn.addEventListener('click', function() {
                                    fetch(`/filetypes/${id}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            filetypes = filetypes.filter(f => f.id != id);
                                            renderTable(filetypes);
                                            closeModal();

                                            showAlert('File Type deleted successfully!', 'success');
                                        }
                                    });
                                });
                            }
                        });
                }
                function closeModal() {
                    const modal = document.getElementById('universalModal');
                    modal.classList.remove('active');
                    modal.innerHTML = '';
                }

                const tS = document.getElementById('searchInputTable');
                [tS].forEach(el => el.oninput = (e) => {
                    const val = e.target.value.toLowerCase();
                    tS.value = e.target.value;
                    renderTable(filetypes.filter(u => u.name.toLowerCase().includes(val)));
                });

                renderTable(filetypes);
            </script>


        </div>

    </main>
    <script>
        // Data
        const usersData = [{
                name: 'Sarah Chen',
                role: 'Admin',
                status: 'active'
            },
            {
                name: 'Mike Ross',
                role: 'Editor',
                status: 'active'
            },
        ];

        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const navItems = document.querySelectorAll('.nav-item');
        const toastContainer = document.getElementById('toastContainer');

        // Initialization
        function init() {
            setupEventListeners();
            animateCounters();
        }

        // Event Listeners
        function setupEventListeners() {
            // Mobile sidebar
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('open');
            });

            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });

            // Dropdowns
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle('open');
                profileDropdown.classList.remove('open');
            });

            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('open');
                notificationDropdown.classList.remove('open');
            });

            document.addEventListener('click', () => {
                notificationDropdown.classList.remove('open');
                profileDropdown.classList.remove('open');
            });

            // Navigation & View Switching
            navItems.forEach(item => {
                item.addEventListener('click', () => {
                    const viewId = item.dataset.view;
                    if (!viewId) return;

                    // Update Nav Active State
                    navItems.forEach(i => i.classList.remove('active'));
                    item.classList.add('active');

                    // Switch Views
                    document.querySelectorAll('.view-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    const targetView = document.getElementById(viewId + '-view');
                    if (targetView) {
                        targetView.classList.add('active');
                        // Re-trigger animations if needed
                        const animElements = targetView.querySelectorAll('.animate-in');
                        animElements.forEach(el => {
                            el.style.animation = 'none';
                            el.offsetHeight; // trigger reflow
                            el.style.animation = null;
                        });
                    }

                    // Close Mobile Sidebar
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('open');

                    showToast(`Navigated to ${viewId.charAt(0).toUpperCase() + viewId.slice(1)}`, 'info');
                });
            });
        }

        // Animations
        function animateCounters() {
            const counters = document.querySelectorAll('[data-count]');
            counters.forEach(counter => {
                const target = parseInt(counter.dataset.count);
                const duration = 2000;
                const start = performance.now();

                function update(currentTime) {
                    const elapsed = currentTime - start;
                    const progress = Math.min(elapsed / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 4);
                    counter.textContent = Math.floor(target * eased).toLocaleString();
                    if (progress < 1) requestAnimationFrame(update);
                }
                requestAnimationFrame(update);
            });
        }

        // Toast
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const colors = {
                success: 'var(--accent)',
                error: 'var(--danger)',
                warning: 'var(--warning)',
                info: 'var(--info)'
            };
            toast.className = 'card-elevated p-4 flex items-center gap-3 min-w-72';
            toast.style.animation = 'fadeSlideUp 0.3s ease-out';
            toast.innerHTML = `
        <div class="w-2 h-2 rounded-full" style="background: ${colors[type]}"></div>
        <p class="text-sm">${message}</p>
      `;
            toastContainer.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</x-app-layout>