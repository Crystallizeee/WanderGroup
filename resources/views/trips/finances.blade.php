<x-app-layout>
    @php $title = $trip->title . ' - Finances'; @endphp

    <header class="mb-stack-lg flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-display-lg text-on-background mb-1">Financial Dashboard</h1>
            <p class="font-body text-body-lg text-on-surface-variant">Manage expenses and settlements for {{ $trip->destination }}.</p>
        </div>
        <button onclick="document.getElementById('add-expense-modal').classList.remove('hidden')" class="btn-primary shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined">add_card</span> Add Expense
        </button>
    </header>

    {{-- Stats Grid (Premium Elevation) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-stack-lg">
        <div class="card flex flex-col justify-center items-center text-center bg-white border-0 shadow-elevation-1">
            <h3 class="font-label text-label-sm text-outline uppercase tracking-widest mb-3">Total Trip Spent</h3>
            <div class="font-display text-display-lg text-tertiary mb-1">{{ rupiah($totalSpent) }}</div>
            <div class="flex items-center gap-1 text-[10px] text-on-surface-variant bg-surface-variant/30 px-2 py-0.5 rounded-full">
                <span class="material-symbols-outlined text-[12px]">analytics</span> All members
            </div>
        </div>

        <div class="card p-0 overflow-hidden bg-white border-0 shadow-elevation-1">
            <div class="p-4 border-b border-surface-variant/30 font-headline text-label-md">Spending by Category</div>
            <div class="p-5 space-y-4">
                @foreach($categoryBreakdown as $cat => $total)
                <div class="space-y-1.5">
                    <div class="flex justify-between font-label text-[11px] text-on-surface-variant uppercase tracking-tighter">
                        <span class="font-bold text-on-surface">{{ $cat }}</span>
                        <span>{{ round(($total / ($totalSpent ?: 1)) * 100) }}%</span>
                    </div>
                    <div class="h-2 bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full bg-primary transition-all duration-1000 shadow-sm" style="width: {{ ($total / ($totalSpent ?: 1)) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card flex flex-col justify-center items-center text-center bg-surface-container-lowest border-0 shadow-elevation-1 relative overflow-hidden">
            @php $net = $memberBalances['net'][Auth::id()] ?? 0; @endphp
            <div class="absolute top-0 right-0 w-24 h-24 {{ $net >= 0 ? 'bg-primary/5' : 'bg-error/5' }} rounded-full -mr-12 -mt-12"></div>
            
            <h3 class="font-label text-label-sm text-outline uppercase tracking-widest mb-3">Your Net Balance</h3>
            <div class="font-display text-display-lg {{ $net >= 0 ? 'text-primary' : 'text-error' }} mb-1">
                {{ rupiah_signed($net) }}
            </div>
            <p class="font-label text-[11px] {{ $net >= 0 ? 'text-primary/70' : 'text-error/70' }} font-bold uppercase">{{ $net >= 0 ? 'Group owes you' : 'You owe the group' }}</p>
        </div>
    </div>

    {{-- Optimized Settlements (Level 3 Shadow for Focus) --}}
    <section class="mb-stack-lg">
        <h2 class="font-display text-headline-md text-on-background mb-4">Smart Settlements</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($memberBalances['optimized_transactions'] as $tx)
            <div class="card p-5 flex flex-col gap-4 border-0 bg-white shadow-elevation-2 hover:shadow-elevation-3 transition-all">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center text-[11px] font-bold shadow-sm">{{ $tx['from']->initials }}</div>
                        <div>
                            <p class="font-label text-[10px] text-outline uppercase leading-none">From</p>
                            <span class="font-headline text-label-md">{{ $tx['from']->name }}</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-outline">arrow_forward</span>
                    <div class="flex items-center gap-3 text-right">
                        <div>
                            <p class="font-label text-[10px] text-outline uppercase leading-none">To</p>
                            <span class="font-headline text-label-md">{{ $tx['to']->name }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[11px] font-bold shadow-sm">{{ $tx['to']->initials }}</div>
                    </div>
                </div>
                <div class="text-center py-3 bg-surface-container-low rounded-2xl font-display text-headline-md text-primary ring-1 ring-primary/5">
                    {{ rupiah($tx['amount']) }}
                </div>
                @if($tx['from']->id === Auth::id())
                <button class="btn-primary py-2.5 w-full text-label-sm shadow-md" onclick="openSettleModal({{ $tx['to']->id }}, '{{ $tx['to']->name }}', {{ $tx['amount'] }})">Pay Now</button>
                @endif
            </div>
            @empty
            <div class="col-span-full bg-surface-bright rounded-3xl border-2 border-dashed border-outline-variant p-10 flex flex-col items-center justify-center text-center">
                <span class="material-symbols-outlined text-[40px] text-primary/20 mb-3">check_circle</span>
                <p class="font-body text-body-md text-on-surface-variant">All debts are cleared! Enjoy your trip without financial worries.</p>
            </div>
            @endforelse
        </div>
    </section>

    {{-- Recent Expenses --}}
    <section class="mb-stack-lg">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display text-headline-md text-on-background">Activity Log</h2>
        </div>
        <div class="card p-0 overflow-hidden bg-white border-0 shadow-elevation-1">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low/50 font-label text-[11px] text-outline uppercase tracking-wider">
                        <tr>
                            <th class="p-5">Date</th>
                            <th class="p-5">Expense Detail</th>
                            <th class="p-5">Payer</th>
                            <th class="p-5 text-right">Amount</th>
                            <th class="p-5"></th>
                        </tr>
                    </thead>
                    <tbody class="font-body text-body-md">
                        @foreach($recentExpenses as $expense)
                        <tr class="border-b border-surface-variant/20 hover:bg-surface-bright transition-colors group">
                            <td class="p-5 text-outline text-[13px]">{{ $expense->expense_date->format('M d, Y') }}</td>
                            <td class="p-5">
                                <p class="font-headline text-label-md text-on-surface leading-none">{{ $expense->title }}</p>
                                <span class="font-label text-[10px] text-outline uppercase mt-1 inline-block">{{ $expense->category }}</span>
                            </td>
                            <td class="p-5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-surface-variant flex items-center justify-center text-[9px] font-bold shadow-sm">{{ $expense->payer->initials }}</div>
                                    <span class="font-label text-label-sm text-on-surface-variant">{{ $expense->payer->name }}</span>
                                </div>
                            </td>
                            <td class="p-5 text-right font-display text-headline-sm text-primary">{{ rupiah($expense->amount) }}</td>
                            <td class="p-5 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" onclick="openEditExpense({{ json_encode(['id' => $expense->id, 'title' => $expense->title, 'amount' => $expense->amount, 'category' => $expense->category, 'expense_date' => $expense->expense_date->format('Y-m-d'), 'notes' => $expense->notes]) }})" class="w-8 h-8 rounded-full flex items-center justify-center text-outline hover:bg-primary/10 hover:text-primary transition-all" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <form method="POST" action="{{ route('trips.expenses.destroy', [$trip, $expense]) }}" onsubmit="return confirm('Delete this record?')">
                                        @csrf @method('DELETE')
                                        <button class="w-8 h-8 rounded-full flex items-center justify-center text-outline hover:bg-error/5 hover:text-error transition-all">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Add Expense Modal (Level 3 Shadow + Glass) --}}
    <div id="add-expense-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-on-surface/30 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-3xl shadow-elevation-3 w-full max-w-2xl p-8 max-h-[90vh] overflow-y-auto animate-scale-up">
            <header class="flex justify-between items-center mb-8">
                <h3 class="font-display text-headline-lg text-on-surface">Add Expense</h3>
                <button onclick="this.closest('#add-expense-modal').classList.add('hidden')" class="w-10 h-10 rounded-full hover:bg-surface-container-low flex items-center justify-center text-outline">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>

            {{-- AI Scanner Integration --}}
            <div class="mb-8 p-5 bg-gradient-to-br from-primary/5 to-secondary/5 rounded-2xl border border-primary/10 flex items-center justify-between">
                <div>
                    <h4 class="font-headline text-label-md text-primary mb-1">Wander AI Scanner</h4>
                    <p class="font-body text-[12px] text-on-surface-variant">Upload receipt and let AI extract details.</p>
                </div>
                <label class="btn-primary !px-4 !py-2 !text-[12px] cursor-pointer shadow-md">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span> Scan
                    <input type="file" id="receipt-scanner" class="hidden" accept="image/*" onchange="scanReceipt(this)">
                </label>
            </div>

            <form method="POST" action="{{ route('trips.expenses.store', $trip) }}" class="space-y-5" id="expense-form">
                @csrf
                <div class="space-y-4">
                    <input class="input-field pl-4" name="title" id="exp-title" placeholder="What was it for? *" required>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative">
                            <input class="input-field pl-4" name="amount" id="exp-amount" type="number" step="0.01" placeholder="Amount *" required oninput="updateSplits()">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 font-label text-label-sm text-outline">IDR</span>
                        </div>
                        <input class="input-field pl-4" name="expense_date" id="exp-date" type="date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <select class="input-field pl-4" name="category" id="exp-category">
                        <option value="food">Food & Drink</option>
                        <option value="transport">Transport</option>
                        <option value="accommodation">Accommodation</option>
                        <option value="activity">Activity</option>
                        <option value="shopping">Shopping</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="pt-4 border-t border-surface-variant/30">
                    <label class="font-headline text-label-sm text-primary uppercase tracking-widest mb-3 block">Split Configuration</label>
                    <select class="input-field pl-4 mb-4" name="split_method" id="split-method" onchange="toggleSplitInputs()">
                        <option value="equal">Split Equally</option>
                        <option value="percentage">Split by Percentage (%)</option>
                        <option value="exact">Exact Amounts</option>
                        <option value="itemized">Split by Items (Advanced)</option>
                    </select>

                    <div id="split-details" class="hidden space-y-3 bg-surface-container-low rounded-2xl p-5 border border-primary/5">
                        @foreach($trip->members as $member)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[10px] font-bold shadow-sm">{{ $member->initials }}</div>
                            <span class="flex-1 font-label text-label-sm text-on-surface">{{ $member->name }}</span>
                            <div class="relative w-24">
                                <input type="hidden" name="splits[{{ $loop->index }}][user_id]" value="{{ $member->id }}">
                                <input type="number" step="0.01" name="splits[{{ $loop->index }}][amount]" class="input-field !py-2 !px-3 text-right split-input" data-user-id="{{ $member->id }}">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-outline split-unit"></span>
                            </div>
                        </div>
                        @endforeach
                        <div class="pt-3 border-t border-surface-variant/50 flex justify-between font-headline text-[12px]">
                            <span id="split-total-label">Total:</span>
                            <span id="split-total-value">0</span>
                        </div>
                    </div>
                    
                    <div id="items-container" class="hidden mt-4 space-y-3 bg-white p-4 rounded-2xl border border-outline-variant/30 shadow-sm">
                        <label class="font-headline text-label-sm text-primary uppercase tracking-widest block mb-2">Receipt Items</label>
                        <div id="items-list" class="space-y-2"></div>
                        <button type="button" onclick="addItemRow()" class="text-primary text-[12px] font-bold flex items-center gap-1 mt-2 hover:bg-primary/5 p-1 rounded"><span class="material-symbols-outlined text-[16px]">add</span> Add Manual Item</button>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full py-4 text-headline-sm shadow-xl shadow-primary/20">Record Expense</button>
            </form>
        </div>
    </div>

    {{-- Edit Expense Modal --}}
    <div id="edit-expense-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-on-surface/30 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-3xl shadow-elevation-3 w-full max-w-lg p-8 animate-scale-up">
            <header class="flex justify-between items-center mb-6">
                <h3 class="font-display text-headline-md text-on-surface">Edit Expense</h3>
                <button onclick="this.closest('#edit-expense-modal').classList.add('hidden')" class="w-10 h-10 rounded-full hover:bg-surface-container-low flex items-center justify-center text-outline">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>
            <form id="edit-expense-form" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Title *</label>
                    <input class="input-field pl-4" name="title" id="edit-exp-title" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Amount *</label>
                        <input class="input-field pl-4" name="amount" id="edit-exp-amount" type="number" step="0.01" required>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Date *</label>
                        <input class="input-field pl-4" name="expense_date" id="edit-exp-date" type="date" required>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Category *</label>
                    <select class="input-field pl-4" name="category" id="edit-exp-category">
                        <option value="food">Food & Drink</option>
                        <option value="transport">Transport</option>
                        <option value="accommodation">Accommodation</option>
                        <option value="activity">Activity</option>
                        <option value="shopping">Shopping</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Notes</label>
                    <textarea class="input-field pl-4 resize-none" name="notes" id="edit-exp-notes" rows="2"></textarea>
                </div>
                <button type="submit" class="btn-primary w-full py-3">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditExpense(data) {
            document.getElementById('edit-expense-form').action = '{{ url("trips/{$trip->id}/expenses") }}/' + data.id;
            document.getElementById('edit-exp-title').value = data.title;
            document.getElementById('edit-exp-amount').value = data.amount;
            document.getElementById('edit-exp-date').value = data.expense_date;
            document.getElementById('edit-exp-category').value = data.category;
            document.getElementById('edit-exp-notes').value = data.notes || '';
            document.getElementById('edit-expense-modal').classList.remove('hidden');
        }
        const tripMembers = @json($trip->members->map(function($m) { return ['id' => $m->id, 'name' => $m->name]; }));
        let itemCount = 0;
        
        function addItemRow(name = '', price = '') {
            const list = document.getElementById('items-list');
            let options = `<option value="">Shared (Split Equally)</option>`;
            tripMembers.forEach(m => { options += `<option value="${m.id}">${m.name}</option>`; });

            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 bg-surface p-2 rounded-xl border border-outline-variant/30';
            row.innerHTML = `
                <input type="text" name="items[${itemCount}][name]" value="${name}" class="input-field !py-2 !px-3 text-[12px] flex-1" placeholder="Item Name">
                <input type="number" name="items[${itemCount}][price]" value="${price}" class="input-field !py-2 !px-3 text-[12px] w-24 text-right item-price" placeholder="Price" oninput="calculateItemizedSplits()">
                <select name="items[${itemCount}][assigned_to]" class="input-field !py-2 !px-2 text-[12px] w-40 item-assignee" onchange="calculateItemizedSplits()">
                    ${options}
                </select>
                <button type="button" onclick="this.parentElement.remove(); calculateItemizedSplits();" class="text-error hover:bg-error/10 p-1 rounded"><span class="material-symbols-outlined text-[16px]">close</span></button>
            `;
            list.appendChild(row);
            itemCount++;
            document.getElementById('items-container').classList.remove('hidden');
        }

        function calculateItemizedSplits() {
            if (document.getElementById('split-method').value !== 'itemized') return;
            
            let userTotals = {};
            tripMembers.forEach(m => userTotals[m.id] = 0);
            let sharedTotal = 0;
            
            const rows = document.getElementById('items-list').children;
            for(let row of rows) {
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                const assignee = row.querySelector('.item-assignee').value;
                if (assignee) {
                    userTotals[assignee] += price;
                } else {
                    sharedTotal += price;
                }
            }
            
            const sharedPerPerson = sharedTotal / tripMembers.length;
            let grandTotal = 0;
            
            document.querySelectorAll('.split-input').forEach(input => {
                const userId = input.getAttribute('data-user-id');
                const total = userTotals[userId] + sharedPerPerson;
                input.value = total.toFixed(2);
                grandTotal += total;
            });
            
            document.getElementById('exp-amount').value = grandTotal.toFixed(2);
            
            // Re-run the updateSplits function to format currency labels
            const totalValue = document.getElementById('split-total-value');
            if (totalValue) totalValue.innerText = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(grandTotal);
        }

        function toggleSplitInputs() {
            const method = document.getElementById('split-method').value;
            const details = document.getElementById('split-details');
            const units = document.querySelectorAll('.split-unit');
            const totalLabel = document.getElementById('split-total-label');
            const inputs = document.querySelectorAll('.split-input');
            const itemsContainer = document.getElementById('items-container');
            
            if (method === 'equal') {
                details.classList.add('hidden');
                itemsContainer.classList.add('hidden');
            } else if (method === 'itemized') {
                details.classList.remove('hidden');
                itemsContainer.classList.remove('hidden');
                inputs.forEach(input => input.setAttribute('readonly', true));
                units.forEach(u => u.innerText = '');
                totalLabel.innerText = 'Total Calculated:';
                calculateItemizedSplits();
            } else {
                details.classList.remove('hidden');
                itemsContainer.classList.add('hidden');
                inputs.forEach(input => input.removeAttribute('readonly'));
                units.forEach(u => u.innerText = method === 'percentage' ? '%' : '');
                totalLabel.innerText = method === 'percentage' ? 'Total Percentage:' : 'Total Amount:';
                updateSplits();
            }
        }

        function updateSplits() {
            const method = document.getElementById('split-method').value;
            const amount = parseFloat(document.getElementById('exp-amount').value) || 0;
            const inputs = document.querySelectorAll('.split-input');
            let total = 0;
            
            inputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            const totalValue = document.getElementById('split-total-value');
            totalValue.innerText = method === 'percentage' ? total + '%' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(total);
            
            if (method === 'percentage' && Math.abs(total - 100) > 0.01) {
                totalValue.classList.add('text-error');
            } else if (method === 'exact' && Math.abs(total - amount) > 0.1) {
                totalValue.classList.add('text-error');
            } else {
                totalValue.classList.remove('text-error');
            }
        }

        async function scanReceipt(input) {
            if (!input.files || !input.files[0]) return;
            
            const formData = new FormData();
            formData.append('receipt', input.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            const btn = input.parentElement;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="animate-spin material-symbols-outlined text-[16px]">sync</span>';
            btn.classList.add('opacity-50', 'pointer-events-none');

            try {
                const response = await fetch('{{ route("trips.expenses.scan", $trip) }}', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Failed to communicate with AI');
                }
                
                if (data.title) document.getElementById('exp-title').value = data.title;
                if (data.amount) document.getElementById('exp-amount').value = data.amount;
                if (data.category) document.getElementById('exp-category').value = data.category;
                if (data.date) document.getElementById('exp-date').value = data.date;
                
                if (data.items && data.items.length > 0) {
                    document.getElementById('items-list').innerHTML = ''; // clear existing
                    itemCount = 0;
                    document.getElementById('split-method').value = 'itemized';
                    
                    data.items.forEach(item => addItemRow(item.name, item.price));
                    toggleSplitInputs();
                    calculateItemizedSplits();
                }
                
                showToast('Receipt scanned successfully!', 'success');
                updateSplits();
            } catch (error) {
                showToast(error.message || 'Failed to scan receipt.', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.classList.remove('opacity-50', 'pointer-events-none');
                input.value = ''; // Reset input so same file can be selected again
            }
        }

        document.querySelectorAll('.split-input').forEach(input => {
            input.addEventListener('input', updateSplits);
        });
    </script>
</x-app-layout>
