
function showToast(message, duration = 3000) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, duration);
}

function showModal(formHtml) {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'editModalOverlay';
    overlay.innerHTML = `<div class="editModal">${formHtml}</div>`;
    document.body.appendChild(overlay);

    // Cancel buttons
    overlay.querySelectorAll('.cancelEdit').forEach(btn => {
        btn.addEventListener('click', () => overlay.remove());
    });

    return overlay.querySelector('form'); // Return form element
}

function setupModalForm(form, updateEndpoint) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(`inventory-actions/${updateEndpoint}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                showToast('Updated successfully!');
                form.closest('.editModalOverlay').remove();
                refreshInventoryModal();
            } else {
                showToast('Update failed.');
            }
        })
        .catch(err => {
            console.error(`Error updating ${updateEndpoint}:`, err);
            showToast('Something went wrong.');
        });
    });
}

function refreshInventoryModal() {
    const query = document.getElementById('searchInput')?.value || '';
    fetch(`../../inventorysearchmodal.php?search=${encodeURIComponent(query)}&page=1`)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableBody = doc.getElementById('inventoryTableBody');
            document.getElementById('inventoryTableBody').innerHTML = newTableBody.innerHTML;
            bindEditButtons();
        });
}

function bindEditButtons() {
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = this.closest('tr');
            row.dataset.originalCells = row.innerHTML;
            const category = row.dataset.category?.toLowerCase().trim();
            const productId = row.dataset.productId;

            // TIRE
            if (category === 'tire') {
                const tireId = row.dataset.tireId;
                if (!tireId) { showToast('No tire_id'); return; }
                fetch(`inventory-actions/fetch_tires.php?tire_id=${tireId}`)
                    .then(res => res.json())
                    .then(data => {
                        const formHtml = `
                            <form class="inlineEditForm">
                                <input type="hidden" name="tire_id" value="${data.tire_id}">
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                                <label>Size:<input name="size" value="${data.size || ''}"></label>
                                <label>Pattern:<input name="pattern" value="${data.pattern || ''}"></label>
                                <label>Made:<input name="made" value="${data.made || ''}"></label>
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </form>`;
                        const form = showModal(formHtml);
                        setupModalForm(form, 'update_tires.php');
                    })
                    .catch(err => { console.error(err); showToast('Error fetching tire'); });
                return;
            }

            // ACCESSORIES
            if (category === 'accessories') {
                const accessoriesId = row.dataset.accessoriesId;
                if (!accessoriesId) { showToast('No accessories_id'); return; }
                fetch(`inventory-actions/fetch_accessories.php?accessories_id=${accessoriesId}`)
                    .then(res => res.json())
                    .then(data => {
                        const formHtml = `
                            <form class="inlineEditForm">
                                <input type="hidden" name="accessories_id" value="${data.accessories_id}">
                                <label>Type of Accessories:<input name="typeofaccessories" value="${data.typeofaccessories || ''}"></label>
                                <label>Brand: <input name="brand" value="${data.brand || ''}"></label>
                                <label>Model Number: <input name="model_number" value="${data.model_number || ''}"></label>
                                <label>Material: <input name="material" value="${data.material || ''}"></label>
                                <label>Fitment Details: <input name="fitment" value="${data.fitment_details || ''}"></label>
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </form>`;
                        const form = showModal(formHtml);
                        setupModalForm(form, 'update_accessories.php');
                    })
                    .catch(err => { console.error(err); showToast('Error fetching accessories'); });
                return;
            }

            // BATTERY
            if (category === 'battery') {
                const batteryId = row.dataset.batteryId;
                if (!batteryId) { showToast('No battery_id'); return; }
                fetch(`inventory-actions/fetch_battery.php?battery_id=${batteryId}`)
                    .then(res => res.json())
                    .then(data => {
                        const formHtml = `
                            <form class="inlineEditForm">
                                <input type="hidden" name="battery_id" value="${data.battery_id}">
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                                <label>Voltage:<input name="voltage" value="${data.voltage || ''}"></label>
                                <label>Model Number:<input name="model_number" value="${data.model_number || ''}"></label>
                                <label>Capacity:<input name="capacity" value="${data.capacity || ''}"></label>
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </form>`;
                        const form = showModal(formHtml);
                        setupModalForm(form, 'update_battery.php');
                    })
                    .catch(err => { console.error(err); showToast('Error fetching battery'); });
                return;
            }

            // ENGINE OIL
            if (category === 'engine oil') {
                const oilId = row.dataset.oilId;
                if (!oilId) { showToast('No oil_id'); return; }
                fetch(`inventory-actions/fetch_engineoil.php?oil_id=${oilId}`)
                    .then(res => res.json())
                    .then(data => {
                        const formHtml = `
                            <form class="inlineEditForm">
                                <input type="hidden" name="oil_id" value="${data.oil_id}">
                                <label>Brand:<input name="brand" value="${data.brand || ''}"></label>
                                <label>Oil Type:<input name="oiltype" value="${data.oiltype || ''}"></label>
                                <label>Capacity:<input name="capacity" value="${data.capacity || ''}"></label>
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </form>`;
                        const form = showModal(formHtml);
                        setupModalForm(form, 'update_engineoil.php');
                    })
                    .catch(err => { console.error(err); showToast('Error fetching engine oil'); });
                return;
            }

            // FILTER
            if (category === 'filter') {
                const filterId = row.dataset.filterId;
                if (!filterId) { showToast('No filter_id'); return; }
                fetch(`inventory-actions/fetch_filter.php?filter_id=${filterId}`)
                    .then(res => res.json())
                    .then(data => {
                        const formHtml = `
                            <form class="inlineEditForm">
                                <input type="hidden" name="filter_id" value="${data.filter_id}">
                                <label>Type of Filter:<input name="typeoffilter" value="${data.typeoffilter || ''}"></label>
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </form>`;
                        const form = showModal(formHtml);
                        setupModalForm(form, 'update_filter.php');
                    })
                    .catch(err => { console.error(err); showToast('Error fetching filter'); });
                return;
            }

            // Repeat this pattern for all other categories (lugnuts, mags, mechanical product, motorcycle tires, nitrogen, tire valve, wheel weights)...

            // GENERIC PRODUCT (if productId exists)
            if (productId) {
                fetch(`inventory-actions/fetch_generic.php?product_id=${productId}`)
                    .then(res => res.json())
                    .then(data => {
                        const formHtml = `
                            <form class="inlineEditForm">
                                <input type="hidden" name="product_id" value="${data.product_id}">
                                <label>Product Name:<input name="product_name" value="${data.product_name || ''}"></label>
                                <label>Price:<input name="price" type="number" value="${data.price || 0}"></label>
                                <label>Cost:<input name="cost" type="number" value="${data.cost || 0}"></label>
                                <label>Detail 1:<input name="detail1" value="${data.detail1 || ''}"></label>
                                <label>Detail 2:<input name="detail2" value="${data.detail2 || ''}"></label>
                                <label>Detail 3:<input name="detail3" value="${data.detail3 || ''}"></label>
                                <label>Detail 4:<input name="detail4" value="${data.detail4 || ''}"></label>
                                <label>Detail 5:<input name="detail5" value="${data.detail5 || ''}"></label>
                                <label>Detail 6:<input name="detail6" value="${data.detail6 || ''}"></label>
                                <button type="submit">Save</button>
                                <button type="button" class="cancelEdit">Cancel</button>
                            </form>`;
                        const form = showModal(formHtml);
                        setupModalForm(form, 'update_generic.php');
                    })
                    .catch(err => { console.error(err); showToast('Error loading generic editor.'); });
                return;
            }

            // If no category and no productId
            showToast('Missing category and product ID.');
        });
    });
}

// Initialize
bindEditButtons();

// Pagination & search handlers (unchanged)
document.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const page = this.getAttribute('data-page');
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);
        window.location.search = params.toString();
    });
});

document.getElementById('searchInput').addEventListener('input', function () {
    const query = this.value;
    fetch(`inventorysearchmodal.php?search=${encodeURIComponent(query)}&page=1`)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableBody = doc.getElementById('inventoryTableBody');
            document.getElementById('inventoryTableBody').innerHTML = newTableBody.innerHTML;
        });
});
