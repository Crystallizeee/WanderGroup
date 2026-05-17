/**
 * WanderGroup — Drag-and-Drop Itinerary Reorder
 * Uses native HTML5 drag and drop API for timeline item reordering.
 */
document.addEventListener('DOMContentLoaded', function () {
    initDragAndDrop();
    initNotificationToasts();
});

// ═══════════════════════════════════════════════
// Drag & Drop Itinerary Items
// ═══════════════════════════════════════════════
function initDragAndDrop() {
    const container = document.getElementById('itinerary-timeline');
    if (!container) return;

    const items = container.querySelectorAll('[data-item-id]');

    items.forEach(item => {
        item.setAttribute('draggable', 'true');
        item.classList.add('cursor-grab');

        item.addEventListener('dragstart', e => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', item.dataset.itemId);
            item.classList.add('opacity-40', 'scale-95');
            item.classList.remove('cursor-grab');
            item.classList.add('cursor-grabbing');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-40', 'scale-95', 'cursor-grabbing');
            item.classList.add('cursor-grab');
            container.querySelectorAll('.drag-over').forEach(el =>
                el.classList.remove('drag-over', 'border-t-2', 'border-primary')
            );
        });

        item.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            item.classList.add('drag-over', 'border-t-2', 'border-primary');
        });

        item.addEventListener('dragleave', () => {
            item.classList.remove('drag-over', 'border-t-2', 'border-primary');
        });

        item.addEventListener('drop', e => {
            e.preventDefault();
            const draggedId = e.dataTransfer.getData('text/plain');
            const draggedEl = container.querySelector(`[data-item-id="${draggedId}"]`);

            if (draggedEl && draggedEl !== item) {
                // Insert before the drop target
                container.insertBefore(draggedEl, item);
                saveNewOrder(container);
            }

            item.classList.remove('drag-over', 'border-t-2', 'border-primary');
        });
    });
}

function saveNewOrder(container) {
    const items = container.querySelectorAll('[data-item-id]');
    const reorderUrl = container.dataset.reorderUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const orderData = [];
    items.forEach((item, index) => {
        orderData.push({
            id: parseInt(item.dataset.itemId),
            sort_order: index + 1
        });
    });

    fetch(reorderUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ items: orderData })
    })
    .then(r => r.json())
    .then(() => showToast('Order updated!', 'success'))
    .catch(() => showToast('Failed to save order', 'error'));
}


// ═══════════════════════════════════════════════
// Notification Toast System
// ═══════════════════════════════════════════════
function initNotificationToasts() {
    // Show flash messages from Laravel session
    const successMsg = document.querySelector('[data-flash-success]');
    const errorMsg = document.querySelector('[data-flash-error]');

    if (successMsg) showToast(successMsg.dataset.flashSuccess, 'success');
    if (errorMsg) showToast(errorMsg.dataset.flashError, 'error');
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container') || createToastContainer();

    const colors = {
        success: 'bg-primary text-on-primary',
        error: 'bg-error text-on-error',
        info: 'bg-secondary-container text-on-secondary-container',
    };

    const icons = {
        success: 'check_circle',
        error: 'error',
        info: 'info',
    };

    const toast = document.createElement('div');
    toast.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-elevation-2 ${colors[type] || colors.info} font-label text-label-md transform translate-y-4 opacity-0 transition-all duration-300`;
    toast.innerHTML = `
        <span class="material-symbols-outlined text-[18px]">${icons[type] || icons.info}</span>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-4', 'opacity-0');
    });

    // Remove after delay
    setTimeout(() => {
        toast.classList.add('translate-y-4', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed bottom-24 lg:bottom-8 right-4 lg:right-8 z-50 flex flex-col gap-2 items-end';
    document.body.appendChild(container);
    return container;
}

// Make globally available
window.showToast = showToast;
