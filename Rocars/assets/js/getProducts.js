// Global elements
const categorySelect = document.getElementById('Category');
const searchInput = document.getElementById('searchInput');
const container = document.getElementById('inventoryContainer');
const cartContainer = document.getElementById('cartItems');
const subtotalDisplay = document.getElementById('subtotal');
const cartCount = document.getElementById('cartCount');
const receiptModal = document.getElementById('receiptModal');
const receiptContent = document.getElementById('receiptContent');
const saleError = document.getElementById('saleError');
 




let cart = [];
let isProcessingSale = false;

// ----------------- LOAD PRODUCTS -----------------
function loadData(page = 1) {
    const category = categorySelect.value;
    const search = searchInput.value;

    container.innerHTML = '<p style="text-align:center;">Loading products...</p>';

    fetch(`getProductsData.php?page=${page}&category=${category}&search=${search}`)
        .then(response => response.text())
        .then(data => {

            container.innerHTML = data;
            

            document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    const product = {
                        id: btn.dataset.id,
                        name: btn.dataset.name,
                        type: btn.dataset.type || '',
                        price: parseFloat(btn.dataset.price),
                        cost: parseFloat(btn.dataset.cost),
                        maxQty: parseInt(btn.dataset.qty)
                    };
                    addToCart(product);
                });
            });

                document.querySelectorAll('.page-btn').forEach(button => {
                    button.addEventListener('click', e => {
                        const newPage = parseInt(e.target.dataset.page);
                        loadData(newPage);
                    });
                });
        })
        .catch(error => {
            console.error("Error loading products:", error);
            container.innerHTML = "<p>Error loading products.</p>";
        });
}

// ----------------- CART FUNCTIONS -----------------
function addToCart(product) {
    saleError.textContent = '';

    const qtyBox = document.getElementById(`qty-${product.id}`);
    if (!qtyBox) return;

    const qtyStrong = qtyBox.querySelector('strong');
    let availableQty = parseInt(qtyStrong.textContent);

    if (availableQty <= 0) {
        saleError.textContent = `Out of stock: "${product.name}"`;
        return;
    }

    const existing = cart.find(item => item.id === product.id);

    if (!existing) {
        cart.push({
            ...product,
            quantity: 1,
            originalStock: availableQty + 0 
        });
    } else {
        existing.quantity += 1;
    }


    qtyStrong.textContent = availableQty - 1;

    renderCart();
}


function renderCart() {
    cartContainer.innerHTML = "";
    let subtotal = 0;

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;

        const div = document.createElement('div');
        div.classList.add('cart-item');
        div.innerHTML = `
            <div class="cart-product">
                <strong>${item.name}</strong>
                <button class="remove-btn" data-index="${index}" title="Remove Item">✕</button>
            </div>
            <div class="quantity-controls">
                <button class="qty-btn minus" data-index="${index}">−</button>
                <span>${item.quantity}</span>
                <button class="qty-btn plus" data-index="${index}">+</button>
                <span>₱${itemTotal.toFixed(2)}</span>
            </div>`;
        cartContainer.appendChild(div);
    });

    subtotalDisplay.textContent = subtotal.toFixed(2);
    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);

// PLUS
document.querySelectorAll('.qty-btn.plus').forEach(btn => {
    btn.addEventListener('click', () => {
        const index = parseInt(btn.dataset.index);
        const item = cart[index];

        const qtyBox = document.getElementById(`qty-${item.id}`);
        if (!qtyBox) return;

        const qtyStrong = qtyBox.querySelector('strong');
        let available = parseInt(qtyStrong.textContent);

        if (available > 0) {
            item.quantity += 1;
            qtyStrong.textContent = available - 1;
            renderCart();
        } else {
            saleError.textContent = `No more stock for "${item.name}"`;
        }
    });
});

// MINUS
document.querySelectorAll('.qty-btn.minus').forEach(btn => {
    btn.addEventListener('click', () => {
        const index = parseInt(btn.dataset.index);
        const item = cart[index];

        const qtyBox = document.getElementById(`qty-${item.id}`);
        if (qtyBox) {
            const qtyStrong = qtyBox.querySelector('strong');
            qtyStrong.textContent = parseInt(qtyStrong.textContent) + 1;
        }

        if (item.quantity > 1) {
            item.quantity -= 1;
        } else {
            cart.splice(index, 1);
        }

        renderCart();
    });
});

document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const index = parseInt(btn.dataset.index);
        const item = cart[index];

        // restore stock back to inventory UI
        const qtyBox = document.getElementById(`qty-${item.id}`);
        if (qtyBox) {
            const qtyStrong = qtyBox.querySelector('strong');
            qtyStrong.textContent =
                parseInt(qtyStrong.textContent) + item.quantity;
        }

        cart.splice(index, 1);
        renderCart();
    });
});

    const cartSummary = document.querySelector('.cart-summary');
    cartSummary.style.display = cart.length === 0 ? 'none' : 'block';
}




















function showReceiptModal(saleData, items) {

    const subtotal = items.reduce(
        (sum, item) => sum + (item.price * item.quantity), 0
    );

    const serviceCost = parseFloat(saleData.serviceCost || 0);

    const totalAmount = subtotal + serviceCost;

    let itemsHtml = items.map(item => `
        <div class="receipt-item-row">
            <span>${item.quantity} x ${item.name}</span>
            <span>₱${(item.price * item.quantity).toFixed(2)}</span>
        </div>
    `).join('');

    let serviceHtml = '';
    if (saleData.serviceDescription && serviceCost > 0) {
        serviceHtml = `
            <div class="receipt-item-row">
                <span>Service – ${saleData.serviceDescription}</span>
                <span>₱${serviceCost.toFixed(2)}</span>
            </div>
        `;
    }

    receiptContent.innerHTML = `
        <div class="receipt-header">
            <h3>ROCAR GARAGE</h3>
            <p>Official Sales Receipt</p>
            <p style="font-size:0.8em;">
                SI No: <strong>${saleData.si_number}</strong> |
                Date: ${new Date().toLocaleString()}
            </p>
        </div>

        <div class="receipt-info">
            <p><strong>Mechanic:</strong> ${saleData.mechanic_name || 'N/A'}</p>
            <p><strong>Customer:</strong> ${saleData.customer || 'Walk-in'}</p>
            <p><strong>Vehicle:</strong> ${saleData.vehicle || 'N/A'} / ${saleData.plate || 'N/A'}</p>
            <p><strong>Payment:</strong> ${saleData.paymentMethod}</p>
        </div>

        <div class="receipt-items">
            ${itemsHtml}
            ${serviceHtml}
        </div>

        <div class="receipt-total">
            <span>TOTAL:</span>
            <span>₱${totalAmount.toFixed(2)}</span>
        </div>

        ${saleData.remarks ? `
            <div style="margin-top:10px;font-size:0.8em;text-align:center;border-top:1px solid #eee;">
                Remarks: ${saleData.remarks}
            </div>
        ` : ''}

        <div style="margin-top:20px;font-size:0.8em;text-align:center;">
            <p>Thank you for your business!</p>
        </div>
    `;

    receiptModal.style.display = 'block';
}

function printReceipt() {
    window.print();
}

const quickService = document.getElementById('quickService');
const openQuickService = document.getElementById('openQuickService');
const closeQuickService = document.getElementById('closeQuickService');
const serviceForm = document.getElementById('serviceform');

// Assuming these are defined elsewhere:
// const quickService = document.getElementById('openQuickService'); // or whatever triggers the modal
// const openQuickService = document.getElementById('openQuickService');
// const closeQuickService = document.getElementById('closeQuickService');
// const serviceForm = document.getElementById('serviceform');

quickService.addEventListener("click", () => {
    openQuickService.style.display = 'flex';
});

closeQuickService.addEventListener("click", () => {
    openQuickService.style.display = 'none';
});

window.addEventListener("click", (e) => {
    if (e.target === openQuickService) {
        openQuickService.style.display = 'none';
    }
});

serviceForm.addEventListener('submit', async e => {
    e.preventDefault();
    const formdata = new FormData(serviceForm);

    try {
        const res = await fetch('actions/quickService.php', { method: 'POST', body: formdata });
        const data = await res.json();

        const message = document.getElementById('message');
        openQuickService.style.display = 'none';

        setTimeout(() => {
            message.style.display = 'block';
            if (data.status === 'success') {
                message.textContent = data.message; // e.g., "Service record saved successfully"
            } else {
                message.textContent = "Error: " + data.message; // e.g., "mechanic_id is required"
            }
            setTimeout(() => message.style.display = 'none', 5000);
        }, 500);

    } catch (err) {
        const message = document.getElementById('message');
        openQuickService.style.display = 'none';
        setTimeout(() => {
            message.style.display = 'flex';
            message.textContent = "Request failed: " + err.message;
            setTimeout(() => message.style.display = 'none', 5000);
        }, 500);
    }
});







//*auto comppletion of input field
document.querySelectorAll('.plateNo').forEach(plateInput => {
    // Finds the suggestion box immediately following the input
    const suggestionbox = plateInput.nextElementSibling;

    plateInput.addEventListener('input', async () => {
        const query = plateInput.value.trim();

        // 1. Clear fields if input is empty
        if (!query) {
            suggestionbox.innerHTML = '';
            const parent = plateInput.closest('form, .cart-sidebar');
            if (parent) {
                parent.querySelectorAll('input').forEach(inp => {
                    if (inp !== plateInput) inp.value = '';
                });
            }
            return;
        }

        try {
            // 2. Fetch data from server
            const res = await fetch(`actions/searchPlate.php?query=${encodeURIComponent(query)}`);
            const data = await res.json();

            // 3. Render suggestions
            suggestionbox.innerHTML = data.map(item => `
                <div class="suggested-items"
                     style="cursor:pointer; padding: 10px; border-bottom: 1px solid #eee;"
                     data-customer="${item.customer_name || ''}"
                     data-vehicle="${item.vehicle || ''}"
                     data-phone="${item.cp_number || ''}"
                     data-odometer="${item.odometer || ''}"
                     data-plate="${item.plate_no || ''}">
                    <strong>${item.plate_no}</strong> - ${item.customer_name || 'New Customer'}
                </div>
            `).join('');

            // 4. Click Event for each suggestion
            suggestionbox.querySelectorAll('.suggested-items').forEach(div => {
                div.addEventListener('click', () => {
                    // Find the specific container (Modal OR Sidebar) this input belongs to
                    const parent = plateInput.closest('form, .cart-sidebar');
                    
                    if (parent) {
                        // Fill Plate Number
                        plateInput.value = div.dataset.plate;

                        // Fill other fields by targeting NAME or multiple possible IDs
                        // This handles both your Modal IDs and Sidebar IDs/Names
                        const setFieldValue = (selectors, value) => {
                            const field = parent.querySelector(selectors);
                            if (field) field.value = value;
                        };

                        setFieldValue('input[name="customer_name"], #customerNameCart, #customerNameModal', div.dataset.customer);
                        setFieldValue('input[name="vehicle"], #vehicleCart, #vehicleModal', div.dataset.vehicle);
                        setFieldValue('input[name="odometer"], #odometerCart, #odometerModal', div.dataset.odometer);
                        setFieldValue('input[name="cp_number"], #customerPhoneCart, #cpModal', div.dataset.phone);
                    }

                    // Hide suggestions
                    suggestionbox.innerHTML = '';
                });
            });

        } catch (err) {
            console.error("Plate search error:", err);
        }
    });
});

// Close suggestions if user clicks outside
document.addEventListener('click', (e) => {
    if (!e.target.classList.contains('plateNo') && !e.target.classList.contains('suggested-items')) {
        document.querySelectorAll('.suggestions').forEach(box => box.innerHTML = '');
    }
});
//!dont erase this is for the basics
// const plateInput = document.getElementById('plateNo');
// const suggestionbox = document.getElementById('suggestions');

// const customerNameInput = document.getElementById('customerName');
// const vehicleInput = document.getElementById('vehicle');
// const cpInput = document.getElementById('customerPhone');
// const odometerInput = document.getElementById('odometer');

// plateInput.addEventListener('input', async () => {
//     const query = plateInput.value.trim();

//     if(!query){
//         suggestionbox.innerHTML = '';
//                 customerNameInput.value = '';
//         vehicleInput.value = '';
//         cpInput.value = '';
//         odometerInput.value = '';
//         return;
//     }

//     try{
//         const res = await fetch(`actions/searchPlate.php?query=${encodeURIComponent(query)}`);
//         const text = await res.text();
//         // console.log("PHP response:", text); 

//         const data = JSON.parse(text);
//         // console.log("Parsed JSON:", data);

//         suggestionbox.innerHTML = data.map(item => `
//             <div class="suggested-items" 
//                 data-customer="${item.customer_name}"
//                 data-vehicle="${item.vehicle}"
//                 data-phone="${item.cp_number}"
//                 data-odometer="${item.odometer}"
//                 data-value="${item.plate_no}">
//                 ${item.plate_no}
//             </div>
//         `).join('');

//             document.querySelectorAll('.suggested-items').forEach(div => {
//                 div.addEventListener('click', ()=>{

//                 plateInput.value = div.dataset.value;
//                 customerNameInput.value = div.dataset.customer;
//                 vehicleInput.value = div.dataset.vehicle;
//                 cpInput.value = div.dataset.phone;
//                 odometerInput.value = div.dataset.odometer;

//                 suggestionbox.innerHTML="";
//             });
//             });

//          }catch (err){
//          console.error(err);
//     }

// });
























document.getElementById('checkoutBtn').addEventListener('click', () => {
    saleError.textContent = '';
    if (cart.length === 0) {
        saleError.textContent = "Cart is empty! Add products to continue.";
        return;
    }

    const mechanic_id = document.getElementById('mech_id').value.trim();
    const si_number = document.getElementById('si_number').value.trim();
    const customer = document.getElementById('customerNameCart').value.trim() || null;
    const phone = document.getElementById('customerPhoneCart').value.trim() || null;
    const vehicle = document.getElementById('vehicleCart').value.trim() || null;
    const plate = document.getElementById('plateNoCart').value.trim() || null;
    const odometer = document.getElementById('odometerCart').value.trim() || null;
    const remarks = document.getElementById('remarks').value.trim() || null;
    const discrepancy = document.getElementById('discrepancy').value.trim() || null;
    const frontline = document.getElementById('frontline').value.trim() || "0";
    const skilled = document.getElementById('skilled').value.trim() || "0";
    const paymentMethod = document.getElementById('paymentMethod').value.trim();
    const productDescription = document.querySelector('input[name="productDescription"]').value.trim();
    const branch_id = window.branch_id;

    let total_amount = 0;
    let total_cost = 0;

    cart.forEach(item => {
        total_amount += item.price * item.quantity;
        total_cost += item.cost * item.quantity;
    });

    const serviceDescription = document.querySelector('input[name="serviceDescription"]').value.trim();
    const serviceCost = parseFloat(document.querySelector('input[name="serviceCost"]').value) || 0;

const payload = {
    si_number, mechanic_id, customer, vehicle, plate, odometer,
    phone, frontline, skilled, remarks, discrepancy, branch_id,
    paymentMethod, cart,
    cart_total: total_amount.toFixed(2),
    total_cost: total_cost.toFixed(2),
    productDescription,   
    serviceDescription,
    serviceCost
};


    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.textContent = 'Processing...';
    checkoutBtn.disabled = true;

    fetch('includes/receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        checkoutBtn.textContent = 'Complete Sale';
        checkoutBtn.disabled = false;

        if (data.success) {
            cart = [];
            renderCart();
            showReceiptModal({ ...payload, mechanic_name: document.getElementById('mech_id').selectedOptions[0].text }, payload.cart);
            document.querySelectorAll('#mech_id,#si_number,#customerNameCart,#customerPhoneCart,#vehicleCart,#plateNoCart,#odometerCart,#remarks,#discrepancy,#frontline,#skilled').forEach(el => el.value = '');
        } else {
            saleError.textContent = "Error: " + (data.error || "Unknown server error");
        }
    })
    .catch(err => {
        checkoutBtn.textContent = 'Complete Sale';
        checkoutBtn.disabled = false;
        saleError.textContent = "Sale processing failed: " + err.message;
        console.error(err);
    });
});

// ----------------- INIT -----------------
loadData();
categorySelect.onchange = () => loadData();
searchInput.oninput = () => loadData();
renderCart();