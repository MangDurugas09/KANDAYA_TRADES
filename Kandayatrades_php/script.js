// Show seller profile modal/page with all their products
function showSellerProfile(sellerId) {
    // Fetch seller profile from API using user_id
    fetch(`api_profiles.php?action=get&user_id=${encodeURIComponent(sellerId)}`, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (!data.success) {
            console.error('Profile load failed:', data.message);
            alert('Could not load seller profile');
            return;
        }
        
        const sellerProfile = data.profile;
        
        // Fetch seller's products
        return fetch('api_products.php?action=list', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            return res.json();
        })
        .then(prodData => {
            if (!prodData.success) {
                console.error('Products load failed:', prodData.message);
                alert('Could not load products');
                return;
            }
            
            // Filter products by seller
            const sellerProducts = prodData.products.filter(p => p.owner_id === sellerProfile.id);
            
            // Create modal
            let modal = document.getElementById('seller-profile-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'seller-profile-modal';
                modal.style.position = 'fixed';
                modal.style.top = '50%';
                modal.style.left = '50%';
                modal.style.transform = 'translate(-50%, -50%)';
                modal.style.background = 'rgba(255,255,255,0.98)';
                modal.style.boxShadow = '0 2px 24px rgba(0,0,0,0.2)';
                modal.style.zIndex = '9999';
                modal.style.padding = '32px 24px';
                modal.style.borderRadius = '16px';
                modal.style.minWidth = '320px';
                modal.style.maxWidth = '90vw';
                modal.style.maxHeight = '80vh';
                modal.style.overflowY = 'auto';
                document.body.appendChild(modal);
            }
            
            // Build avatar image source
            const avatarSrc = sellerProfile.avatar ? `data:image/jpeg;base64,${sellerProfile.avatar}` : '';
            const displayName = sellerProfile.displayName || sellerProfile.username;
            
            modal.innerHTML = `<button class='modal-close-btn' onclick='document.getElementById("seller-profile-modal").style.display="none";' aria-label='Close'>&times;</button>
                <div style='display:flex;align-items:center;gap:16px;margin-bottom:16px;'>
                    ${avatarSrc ? `<img src='${avatarSrc}' class='profile-avatar' style='width:64px;height:64px;object-fit:cover;border-radius:8px;'>` : '<div style="width:64px;height:64px;background:#ddd;border-radius:8px;"></div>'}
                    <div style='display:flex;flex-direction:column;'>
                        <h2 style='margin:0;display:inline-block;'>${displayName}</h2>
                        <div style='font-size:0.95em;color:#666;'>${sellerProfile.bio || ''}</div>
                        <div style='font-size:0.9em;color:#999;'>@${sellerProfile.username}</div>
                    </div>
                </div>
                <h3 style='margin-top:0;'>Products by this seller:</h3>
                <ul style='list-style:none;padding:0;'>
                    ${sellerProducts.length > 0 ? sellerProducts.map(p => {
                        const imgSrc = p.image ? `data:image/jpeg;base64,${p.image}` : '';
                        return `<li style='margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:12px;'>
                            ${imgSrc ? `<img src='${imgSrc}' style='width:48px;height:48px;object-fit:cover;margin-right:8px;border-radius:8px;'>` : '<div style="width:48px;height:48px;background:#ddd;border-radius:8px;"></div>'}
                            <div style='flex:1;'>
                                <strong>${p.name}</strong> <small>(${p.category || 'Uncategorized'})</small><br>
                                <span style='font-weight:700;'>${formatPrice(p.price)}</span><br>
                                <span style='font-size:0.95em;'>${p.description || ''}</span>
                            </div>
                            <div style='display:flex;flex-direction:column;gap:6px;'>
                                <button class='buy-button' onclick='buyProduct(${p.id})'>Buy</button>
                                <button class='home-like-btn' onclick='toggleFavorite(${p.id})'>${isFavorited(p.id) ? '♥' : '♡'}</button>
                            </div>
                        </li>`;
                    }).join('') : '<li>No products posted yet</li>'}
                </ul>`;
            
            // Update like button states after rendering
            setTimeout(updateLikeButtons, 0);
            modal.style.display = 'block';
        });
    })
    .catch(err => {
        console.error('Error loading seller profile:', err);
        alert('Error loading seller profile');
    });
}
// Login with database backend
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);

    fetch('api_auth.php?action=login', {
        method: 'POST',
        body: JSON.stringify({ username, password }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const modal = document.getElementById('login-modal');
            const main = document.getElementById('main-page');

            // Store user info from session (PHP manages it server-side)
            sessionStorage.setItem('user', data.user.username);
            sessionStorage.setItem('userId', data.user.id);
            sessionStorage.setItem('userRole', data.user.role);

            updateNavVisibility();

            // Animate modal exit
            modal.classList.add('modal-exit');

            // Prepare main page
            main.style.display = 'block';
            main.classList.remove('show');
            requestAnimationFrame(() => {
                main.classList.add('show');
            });

            // Hide modal after animation
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.remove('modal-exit');
            }, 450);

            // Load user data and show home page
            setTimeout(() => {
                loadProfile();
                updateNavVisibility();
                loadPageContent('home').then(() => {
                    activateSection('home');
                });
                loadFavorites();
            }, 100);
        } else {
            document.getElementById('login-error').style.display = 'block';
        }
    })
    .catch(err => {
        console.error('Login error:', err);
        document.getElementById('login-error').style.display = 'block';
    });
});

// Load external HTML pages dynamically
async function loadPageContent(pageName) {
    try {
        const response = await fetch(`pages_php/${pageName}.php`);
        if (!response.ok) throw new Error(`Failed to load ${pageName}.php`);
        const html = await response.text();
        const contentDiv = document.getElementById('content');
        contentDiv.innerHTML = html;
        // after loading sellers page, attach product form handlers
        if (pageName === 'sellers') {
            setTimeout(() => attachProductFormHandlers(), 10);
        }
    } catch (err) {
        console.error('Error loading page:', err);
        document.getElementById('content').innerHTML = '<p>Error loading page. Please refresh.</p>';
    }
}

// Signup flow
function showSignup() {
    document.getElementById('login-form').style.display = 'none';
    document.getElementById('login-error').style.display = 'none';
    document.getElementById('signup-box').style.display = 'block';
}

function showLogin() {
    document.getElementById('signup-box').style.display = 'none';
    document.getElementById('signup-error').style.display = 'none';
    document.getElementById('signup-success').style.display = 'none';
    document.getElementById('login-form').style.display = 'block';
}

document.getElementById('signup-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const username = document.getElementById('signup-username').value.trim();
    const password = document.getElementById('signup-password').value;
    const confirm = document.getElementById('signup-password-confirm').value;

    const errEl = document.getElementById('signup-error');
    const okEl = document.getElementById('signup-success');
    errEl.style.display = 'none';
    okEl.style.display = 'none';

    if (!username || !password) {
        errEl.textContent = 'Please provide username and password.';
        errEl.style.display = 'block';
        return;
    }
    if (password !== confirm) {
        errEl.textContent = 'Passwords do not match.';
        errEl.style.display = 'block';
        return;
    }

    const role = document.getElementById('signup-role') ? document.getElementById('signup-role').value : 'customer';

    // Send signup request to server
    fetch('api_auth.php?action=signup', {
        method: 'POST',
        body: JSON.stringify({ username, password, role }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            okEl.textContent = 'Account created — you can now log in.';
            okEl.style.display = 'block';

            // clear signup fields
            document.getElementById('signup-form').reset();

            // automatically switch back to login after a short delay
            setTimeout(() => {
                showLogin();
            }, 900);
        } else {
            errEl.textContent = data.message || 'Error creating account.';
            errEl.style.display = 'block';
        }
    })
    .catch(err => {
        console.error('Signup error:', err);
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
    });
});

// --- Seller profile utilities ---
function getCurrentUser() {
    return sessionStorage.getItem('user') || null;
}

function loadProfile() {
    const user = getCurrentUser();
    if (!user) return;

    fetch('api_profiles.php?action=load', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (!data.success) {
            console.error('Profile load failed:', data.message);
            return;
        }
        
        const profile = data.profile;

        // populate view
        const profileUsernameEl = document.getElementById('profile-username');
        const profileDisplayNameEl = document.getElementById('profile-displayname');
        const profileEmailEl = document.getElementById('profile-email');
        const profileBioEl = document.getElementById('profile-bio');
        if (profileUsernameEl) profileUsernameEl.textContent = profile.username;
        if (profileDisplayNameEl) profileDisplayNameEl.textContent = profile.displayName || profile.username;
        if (profileEmailEl) profileEmailEl.textContent = profile.email ? `Email: ${profile.email}` : '';
        if (profileBioEl) profileBioEl.textContent = profile.bio || '';

        // populate account area
        const acctName = document.getElementById('acct-displayname');
        const acctUser = document.getElementById('acct-username');
        const acctEmail = document.getElementById('acct-email');
        const acctBio = document.getElementById('acct-bio');
        if (acctName) acctName.textContent = profile.displayName || profile.username;
        if (acctUser) acctUser.textContent = profile.username;
        if (acctEmail) acctEmail.textContent = profile.email || '';
        if (acctBio) acctBio.textContent = profile.bio || '';

        // populate edit fields
        const inputDisplayName = document.getElementById('profile-displayname-input');
        const inputEmail = document.getElementById('profile-email-input');
        const inputBio = document.getElementById('profile-bio-input');
        if (inputDisplayName) inputDisplayName.value = profile.displayName || '';
        if (inputEmail) inputEmail.value = profile.email || '';
        if (inputBio) inputBio.value = profile.bio || '';
        
        // avatar preview (base64 encoded)
        const avatarEl = document.getElementById('profile-avatar');
        const previewEl = document.getElementById('profile-avatar-preview');
        if (profile.avatar) {
            const avatarDataUrl = 'data:image/jpeg;base64,' + profile.avatar;
            if (avatarEl) { avatarEl.src = avatarDataUrl; avatarEl.style.display = 'inline-block'; }
            if (previewEl) { previewEl.src = avatarDataUrl; previewEl.style.display = 'block'; }
        } else {
            if (avatarEl) { avatarEl.style.display = 'none'; }
            if (previewEl) { previewEl.style.display = 'none'; }
        }
    })
    .catch(err => {
        console.error('Error loading profile:', err);
        console.error('Stack:', err.stack);
    });
}

function updateNavVisibility() {
    const user = getCurrentUser();
    const role = sessionStorage.getItem('userRole') || null;
    const navSellers = document.getElementById('nav-sellers');
    if (navSellers) navSellers.style.display = (role === 'seller') ? 'list-item' : 'none';
    const navAccount = document.getElementById('nav-account');
    if (navAccount) navAccount.style.display = user ? 'list-item' : 'none';
    // toggle post product button for sellers
    const postBtn = document.getElementById('post-product-btn');
    if (postBtn) postBtn.style.display = (role === 'seller') ? 'inline-block' : 'none';
}

// Store favorited products locally for UI
let localFavorites = [];

// Format price as Philippine Pesos (₱). Uses locale formatting when available.
function formatPrice(price) {
    const n = Number(price) || 0;
    try {
        return n.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
    } catch (err) {
        return '₱' + n.toFixed(2);
    }
}

function isFavorited(productId) {
    return localFavorites.includes(productId);
}

function toggleFavorite(productId) {
    const user = getCurrentUser();
    if (!user) { alert('Please log in to favorite items.'); return; }
    
    const idx = localFavorites.indexOf(productId);
    const action = idx === -1 ? 'add' : 'remove';

    fetch(`api_favorites.php?action=${action}${action === 'remove' ? '&productId=' + productId : ''}`, {
        method: action === 'add' ? 'POST' : 'DELETE',
        credentials: 'same-origin',
        body: action === 'add' ? new URLSearchParams({ productId }).toString() : null,
        headers: action === 'add' ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (idx === -1) localFavorites.push(productId);
            else localFavorites.splice(idx, 1);
            
            // update UI buttons and account favorites
            updateLikeButtons();
            renderFavorites();
            try { loadProducts(); } catch (err) { }
        }
    })
    .catch(err => console.error('Favorite toggle error:', err));
}

function loadFavorites() {
    fetch('api_favorites.php?action=list', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localFavorites = data.favorites.map(f => f.id);
            updateLikeButtons();
            renderFavorites();
        }
    })
    .catch(err => console.error('Error loading favorites:', err));
}

function updateLikeButtons() {
    // Update all like buttons based on localFavorites array (populated from database)
    document.querySelectorAll('[id^="like-btn-"]').forEach(btn => {
        const productId = parseInt(btn.id.replace('like-btn-', ''));
        const favorited = isFavorited(productId);
        
        if (btn.classList.contains('home-like-btn')) {
            btn.textContent = favorited ? '♥' : '♡';
        } else {
            btn.textContent = favorited ? '♥ Liked' : '♡ Like';
        }
        if (favorited) btn.classList.add('liked'); else btn.classList.remove('liked');
    });
    
    // Also update data-driven like buttons
    document.querySelectorAll('[data-like-id]').forEach(btn => {
        const productId = parseInt(btn.getAttribute('data-like-id'));
        const favorited = isFavorited(productId);
        btn.textContent = favorited ? '♥' : '♡';
        if (favorited) btn.classList.add('liked'); else btn.classList.remove('liked');
    });
}

function renderFavorites() {
    const user = getCurrentUser();
    const favListEl = document.getElementById('favorites-list');
    if (!favListEl) return;
    favListEl.innerHTML = '';
    if (!user) { favListEl.innerHTML = '<li>Log in to see favorites</li>'; return; }
    
    fetch('api_favorites.php?action=list', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        
        const favorites = data.favorites || [];
        if (favorites.length === 0) {
            favListEl.innerHTML = '<li>No favorites yet</li>';
            return;
        }
        
        favorites.forEach(prod => {
            const li = document.createElement('li');
            const imgSrc = prod.image ? 'data:image/jpeg;base64,' + prod.image : '';
            const img = imgSrc ? `<img src="${imgSrc}" style="max-width:60px; margin-right:8px; vertical-align:middle">` : '';
            li.innerHTML = `${img}<strong>${prod.name}</strong> — ${prod.category || 'Uncategorized'} — ${formatPrice(prod.price)} <button onclick="toggleFavorite(${prod.id})">Remove</button>`;
            favListEl.appendChild(li);
        });
    })
    .catch(err => console.error('Error rendering favorites:', err));
}

function showEditProfile() {
    const view = document.getElementById('profile-view');
    const edit = document.getElementById('profile-edit');
    if (view) view.style.display = 'none';
    if (edit) edit.style.display = 'block';
    // setup avatar input preview handler (only once)
    const avatarInput = document.getElementById('profile-avatar-input');
    const previewEl = document.getElementById('profile-avatar-preview');
    if (avatarInput && !avatarInput._listenerAttached) {
        avatarInput._listenerAttached = true;
        avatarInput.addEventListener('change', function() {
            const f = this.files && this.files[0];
            if (!f) { if (previewEl) { previewEl.style.display = 'none'; previewEl.src = ''; } return; }
            const r = new FileReader();
            r.onload = function(e) { if (previewEl) { previewEl.src = e.target.result; previewEl.style.display = 'block'; } };
            r.readAsDataURL(f);
        });
    }
}

function cancelEditProfile() {
    const view = document.getElementById('profile-view');
    const edit = document.getElementById('profile-edit');
    if (edit) edit.style.display = 'none';
    if (view) view.style.display = 'block';
}

function saveProfile() {
    const user = getCurrentUser();
    if (!user) return alert('No logged-in user');
    
    const inputDisplayName = document.getElementById('profile-displayname-input');
    const inputEmail = document.getElementById('profile-email-input');
    const inputBio = document.getElementById('profile-bio-input');
    const avatarInput = document.getElementById('profile-avatar-input');

    const formData = new FormData();
    formData.append('displayName', inputDisplayName ? inputDisplayName.value : '');
    formData.append('email', inputEmail ? inputEmail.value : '');
    formData.append('bio', inputBio ? inputBio.value : '');
    
    if (avatarInput && avatarInput.files && avatarInput.files[0]) {
        formData.append('avatar', avatarInput.files[0]);
    }

    fetch('api_profiles.php?action=update', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadProfile();
            cancelEditProfile();
        } else {
            alert(data.message || 'Error updating profile');
        }
    })
    .catch(err => {
        console.error('Profile save error:', err);
        alert('Error saving profile');
    });
}

// Show sections
function showSection(sectionId) {
    // Map section IDs to page files
    const pageMap = {
        'home': 'home',
        'foods': 'foods',
        'clothes': 'clothes',
        'vehicles': 'vehicles',
        'property': 'property',
        'others': 'others',
        'about': 'about',
        'sellers': 'sellers',
        'account': 'account'
    };
    
    const pageName = pageMap[sectionId];
    if (!pageName) {
        console.error('Unknown section:', sectionId);
        return;
    }
    
    // Load the page if it's different from the current one
    const content = document.getElementById('content');
    if (!content.querySelector(`#${sectionId}`)) {
        loadPageContent(pageName).then(() => {
            activateSection(sectionId);
        });
    } else {
        activateSection(sectionId);
    }
}

function activateSection(sectionId) {
    document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
    const section = document.getElementById(sectionId);
    if (section) {
        // remove then add in the next frame to reliably trigger CSS transition (fly-in)
        section.classList.remove('active');
        requestAnimationFrame(() => {
            // small timeout to ensure DOM insertion completed for dynamically loaded pages
            setTimeout(() => {
                section.classList.add('active');
            }, 8);
        });
    }
    
    // refresh profile/favorites when showing account or sellers
    if (sectionId === 'account') {
        loadProfile();
        renderFavorites();
        // show 'Be a Seller' box only for logged-in customers (not sellers)
        try {
            const beBox = document.getElementById('be-seller-box');
            const user = getCurrentUser();
            const profiles = JSON.parse(localStorage.getItem('profiles')) || {};
            const role = (user && profiles[user] && profiles[user].role) ? profiles[user].role : (localStorage.getItem('userRole') || null);
            if (beBox) beBox.style.display = (user && role !== 'seller') ? 'block' : 'none';
        } catch (err) { /* ignore DOM errors */ }
        // load buyer approvals into account notifications
        try { loadAccountNotifications(); } catch (err) {}
    }
    if (sectionId === 'sellers') { loadProfile(); loadProducts(); loadNotifications(); }
    // if we're showing a category or the home page, ensure products are loaded into the newly-inserted DOM
    if (sectionId === 'home') { loadProducts(); renderFavorites(); }
    const categoryIds = ['foods','clothes','vehicles','property','others'];
    if (categoryIds.includes(sectionId)) { loadProducts(); renderFavorites(); }
    // toggle the decorative blurred background overlay for primary pages
    try {
        const bgPages = ['home','foods','clothes','vehicles','property','others','about','sellers','account'];
        if (bgPages.includes(sectionId)) document.body.classList.add('bg-image'); else document.body.classList.remove('bg-image');
    } catch (err) { /* ignore DOM timing issues */ }
}

// Logout: clear user and show login modal
function logout() {
    fetch('api_auth.php?action=logout', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(() => {
        sessionStorage.removeItem('user');
        sessionStorage.removeItem('userId');
        sessionStorage.removeItem('userRole');
        localFavorites = [];

        const modal = document.getElementById('login-modal');
        const main = document.getElementById('main-page');

        // animate main page out
        main.classList.remove('show');
        // after animation end hide it
        setTimeout(() => {
            main.style.display = 'none';
            // show login modal and animate it in
            modal.style.display = 'flex';
            // start from exit state then remove to animate in
            modal.classList.add('modal-exit');
            requestAnimationFrame(() => modal.classList.remove('modal-exit'));
            // update nav visibility for logged-out state
            updateNavVisibility();
            renderFavorites();
        }, 420);
    })
    .catch(err => console.error('Logout error:', err));
}

// Sellers: Show post form
function showPostForm() {
    document.getElementById('post-form').style.display = 'block';
}

// Handle product posting
// Attach product form handler and image preview; called defensively after pages load
function attachProductFormHandlers() {
    const productForm = document.getElementById('product-form');
    if (productForm && !productForm._handlerAttached) {
        productForm._handlerAttached = true;
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('product-name').value;
            const desc = document.getElementById('product-desc').value;
            const type = document.getElementById('sale-type').value;
            const categoryEl = document.getElementById('product-category');
            const category = categoryEl ? categoryEl.value : 'others';
            const price = document.getElementById('price').value;
            const imageInput = document.getElementById('product-image');

            // Ensure an image is selected
            if (!imageInput || !imageInput.files || imageInput.files.length === 0) {
                alert('Please select a product image.');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('desc', desc);
            formData.append('type', type);
            formData.append('category', category);
            formData.append('price', price);
            formData.append('image', imageInput.files[0]);

            fetch('api_products.php?action=create', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadProducts();
                    document.getElementById('product-form').reset();
                    document.getElementById('post-form').style.display = 'none';
                    // hide preview
                    const preview = document.getElementById('image-preview');
                    if (preview) { preview.style.display = 'none'; preview.src = ''; }
                    alert('Product posted successfully!');
                } else {
                    alert(data.message || 'Error posting product');
                }
            })
            .catch(err => {
                console.error('Product creation error:', err);
                alert('Error posting product');
            });
        });
    }

    // Image preview for product image input
    const prodImageInput = document.getElementById('product-image');
    if (prodImageInput && !prodImageInput._previewAttached) {
        prodImageInput._previewAttached = true;
        prodImageInput.addEventListener('change', function() {
            const file = this.files && this.files[0];
            const preview = document.getElementById('image-preview');
            if (!file) {
                if (preview) { preview.style.display = 'none'; preview.src = ''; }
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        });
    }
}
// Load and display products (sellers only)
function loadProducts() {
    fetch('api_products.php?action=list', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (!data.success) {
            console.error('Products load failed:', data.message);
            return;
        }
        
        const products = data.products || [];
        
        // Sellers list
        const sellerList = document.getElementById('product-list');
        if (sellerList) sellerList.innerHTML = '';

        // Home list
        const homeList = document.getElementById('home-product-list');
        if (homeList) homeList.innerHTML = '';

        // Clear category lists if present
        const categories = ['foods','clothes','vehicles','property','others'];
        categories.forEach(catId => {
            const sec = document.getElementById(catId);
            if (!sec) return;
            const ul = sec.querySelector('.category-product-list');
            if (ul) ul.innerHTML = '';
        });

        const currentUser = getCurrentUser();
        const currentUserId = sessionStorage.getItem('userId');
        
        products.forEach(prod => {
            const imgSrc = prod.image ? 'data:image/jpeg;base64,' + prod.image : '';
            const imgHtml = imgSrc ? `<img src="${imgSrc}" alt="${prod.name}" style="max-width:100px; margin-right:10px;">` : '';

        // Seller item: only show if product owner matches current user
        if (sellerList && prod.owner_id === parseInt(currentUserId)) {
            const ownerName = prod.owner_name || 'Unknown';
            const ownerAvatar = null;
                const li = document.createElement('li');
                const canDelete = currentUserId && prod.owner_id && (parseInt(currentUserId) === prod.owner_id);
                li.innerHTML = `${imgHtml}<div><strong>${prod.name}</strong> <small>(${prod.category || 'Uncategorized'})</small><br>
                                ${prod.type} - ${formatPrice(prod.price)}<br>
                                <em>${prod.description}</em><br>
                                <small>Seller: <span class="seller-info" data-seller="${prod.owner_id}">${ownerAvatar ? `<img src="${ownerAvatar}" class="seller-avatar">` : ''}${ownerName}</span></small></div>
                                <div style="display:flex; gap:8px; align-items:center;"><button id="like-btn-${prod.id}" onclick="toggleFavorite(${prod.id})">${isFavorited(prod.id) ? '♥ Liked' : '♡ Like'}</button>
                                ${canDelete ? `<button onclick="deleteProduct(${prod.id})">Delete</button>` : ''}</div>`;
                // Add click event for seller info
                setTimeout(() => {
                    const sellerElem = li.querySelector('.seller-info');
                    if (sellerElem) {
                        sellerElem.style.cursor = 'pointer';
                        sellerElem.onclick = function(e) {
                            e.stopPropagation();
                            showSellerProfile(prod.owner_id);
                        };
                    }
                }, 0);
            sellerList.appendChild(li);
        }

            // Home item: compact card view with like and buy buttons
            if (homeList) {
                const ownerName = prod.owner_name || 'Unknown';
                const ownerAvatarSrc = null;
                const li2 = document.createElement('li');
                li2.innerHTML = `${imgSrc ? `<img src="${imgSrc}" alt="${prod.name}">` : ''}
                    <div class="home-product-info">
                        <strong>${prod.name}</strong>
                        <small>${prod.category || 'Uncategorized'}</small>
                        <div style="margin-top:8px; font-weight:700;">${formatPrice(prod.price)}</div>
                        <div style="margin-top:4px;font-size:0.95em;display:flex;align-items:center;gap:8px;">
                            <span class="seller-info" data-seller="${prod.owner_id}" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                <span>${ownerName}</span>
                            </span>
                        </div>
                    </div>
                    <div class="home-product-actions">
                        <button class="home-like-btn" id="like-btn-${prod.id}" onclick="toggleFavorite(${prod.id})">${isFavorited(prod.id) ? '♥' : '♡'}</button>
                        <button class="buy-button" onclick="buyProduct(${prod.id})">Buy</button>
                    </div>`;
                setTimeout(() => {
                    const sellerElem = li2.querySelector('.seller-info');
                    if (sellerElem) {
                        sellerElem.style.cursor = 'pointer';
                        sellerElem.onclick = function(e) {
                            e.stopPropagation();
                            showSellerProfile(prod.owner_id);
                        };
                    }
                }, 0);
                homeList.appendChild(li2);
            }
            // Category view: append product to its category section list
            try {
                const catSec = document.getElementById(prod.category);
                if (catSec) {
                    let cul = catSec.querySelector('.category-product-list');
                    if (!cul) { cul = document.createElement('ul'); cul.className = 'category-product-list'; cul.style.listStyle = 'none'; cul.style.padding = '0'; catSec.appendChild(cul); }
                    const cli = document.createElement('li');
                    cli.style.padding = '10px 0';
                    // image element
                    if (imgSrc) {
                        const cimg = document.createElement('img');
                        cimg.src = imgSrc;
                        cimg.alt = prod.name;
                        cli.appendChild(cimg);
                    }
                    // meta
                    const meta = document.createElement('div');
                    meta.className = 'category-product-meta';
                    const title = document.createElement('strong');
                    title.textContent = prod.name;
                    meta.appendChild(title);
                    const catText = document.createElement('small');
                    catText.textContent = prod.category || 'Uncategorized';
                    meta.appendChild(catText);
                    const priceDiv = document.createElement('div');
                    priceDiv.style.marginTop = '6px';
                    priceDiv.style.fontWeight = '700';
                    priceDiv.textContent = formatPrice(prod.price);
                    meta.appendChild(priceDiv);
                    cli.appendChild(meta);

                    // seller info
                    const ownerName = prod.owner_name || 'Unknown';
                    const ownerAvatar = null;
                    const sellerDiv = document.createElement('div');
                    sellerDiv.innerHTML = `<small>Seller: <span class="seller-info" data-seller="${prod.owner_id}">${ownerName}</span></small>`;
                    cli.appendChild(sellerDiv);
                    setTimeout(() => {
                        const sellerElem = sellerDiv.querySelector('.seller-info');
                        if (sellerElem) {
                            sellerElem.style.cursor = 'pointer';
                            sellerElem.onclick = function(e) {
                                e.stopPropagation();
                                showSellerProfile(prod.owner_id);
                            };
                        }
                    }, 0);
                    // actions
                    const actions = document.createElement('div');
                    actions.className = 'category-product-actions';
                    const buyBtn = document.createElement('button');
                    buyBtn.className = 'buy-button';
                    buyBtn.textContent = 'Buy';
                    buyBtn.addEventListener('click', function() { buyProduct(prod.id); });
                    const likeBtn = document.createElement('button');
                    likeBtn.className = 'home-like-btn';
                    likeBtn.setAttribute('data-like-id', prod.id);
                    likeBtn.textContent = isFavorited(prod.id) ? '♥' : '♡';
                    likeBtn.addEventListener('click', function() { toggleFavorite(prod.id); });
                    actions.appendChild(buyBtn);
                    actions.appendChild(likeBtn);
                    cli.appendChild(actions);
                    cul.appendChild(cli);
                }
            } catch (err) { /* ignore category render errors */ }
        });
        // ensure like buttons are styled/updated
        updateLikeButtons();
    })
    .catch(err => {
        console.error('Error loading products:', err);
        console.error('Stack:', err.stack);
    });
}

// Delete product (sellers only)
function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    
    fetch(`api_products.php?action=delete&id=${id}`, {
        method: 'DELETE',
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadProducts();
            alert('Product deleted');
        } else {
            alert(data.message || 'Error deleting product');
        }
    })
    .catch(err => {
        console.error('Product deletion error:', err);
        alert('Error deleting product');
    });
}

// Buyer action: send a purchase notification to the seller
function buyProduct(productId) {
    const user = getCurrentUser();
    if (!user) { alert('Please log in to buy items.'); return; }

    const formData = new URLSearchParams();
    formData.append('productId', productId);

    fetch('api_notifications.php?action=purchase-request', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Purchase request sent to seller.');
            loadNotifications();
        } else {
            alert(data.message || 'Error sending purchase request');
        }
    })
    .catch(err => {
        console.error('Purchase request error:', err);
        alert('Error sending purchase request');
    });
}

// Load notifications for the current seller and render them
function loadNotifications() {
    const user = getCurrentUser();
    const notifList = document.getElementById('notifications-list');
    const notifBox = document.getElementById('seller-notifications');
    if (!notifList) return;
    notifList.innerHTML = '';
    if (!user) { if (notifBox) notifBox.style.display = 'none'; return; }

    fetch('api_notifications.php?action=list&type=purchase_request', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        
        const notifications = data.notifications || [];
        if (!notifications.length) { if (notifBox) notifBox.style.display = 'none'; return; }
        if (notifBox) notifBox.style.display = 'block';
        
        // show newest first
        notifications.slice().reverse().forEach(n => {
            const li = document.createElement('li');
            const timeStr = n.created_at ? new Date(n.created_at).toLocaleString() : '';
            li.innerHTML = `${n.sender_id} wants to buy "${n.product_name}" — ${timeStr} <button style="margin-left:8px;" onclick="approvePurchase(${n.id})">Approve</button>`;
            notifList.appendChild(li);
        });
    })
    .catch(err => console.error('Error loading notifications:', err));
}

// Seller approves a purchase request: notify the buyer
function approvePurchase(notificationId) {
    const formData = new URLSearchParams();
    formData.append('notificationId', notificationId);

    fetch('api_notifications.php?action=approve', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            loadAccountNotifications();
            alert('Purchase approved and buyer notified.');
        } else {
            alert(data.message || 'Error approving purchase');
        }
    })
    .catch(err => {
        console.error('Approval error:', err);
        alert('Error approving purchase');
    });
}

// Load notifications for the current account (buyer) and render approval messages
function loadAccountNotifications() {
    const user = getCurrentUser();
    const acctList = document.getElementById('account-notifs-list');
    const acctBox = document.getElementById('account-notifications');
    if (!acctList) return;
    acctList.innerHTML = '';
    if (!user) { if (acctBox) acctBox.style.display = 'none'; return; }

    fetch('api_notifications.php?action=list&type=approval', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        
        const approvals = data.notifications || [];
        if (!approvals.length) { if (acctBox) acctBox.style.display = 'none'; return; }
        if (acctBox) acctBox.style.display = 'block';
        
        approvals.slice().reverse().forEach(n => {
            const li = document.createElement('li');
            const timeStr = n.created_at ? new Date(n.created_at).toLocaleString() : '';
            li.textContent = `Your purchase of "${n.product_name}" was approved by ${n.sender_id} — ${timeStr}`;
            acctList.appendChild(li);
        });
    })
    .catch(err => console.error('Error loading account notifications:', err));
}

function clearAccountNotifications() {
    // In a real system, this would delete all approval notifications via API
    // For now, just clear the UI
    const acctList = document.getElementById('account-notifs-list');
    if (acctList) acctList.innerHTML = '<li>No notifications</li>';
    const acctBox = document.getElementById('account-notifications');
    if (acctBox) acctBox.style.display = 'none';
}

function clearNotifications() {
    // In a real system, this would delete all purchase request notifications via API
    // For now, just clear the UI
    const notifList = document.getElementById('notifications-list');
    if (notifList) notifList.innerHTML = '';
    const notifBox = document.getElementById('seller-notifications');
    if (notifBox) notifBox.style.display = 'none';
}

// Ensure the app always starts at the login session when opened.
// Check server session and show login modal if not authenticated
(function ensureStartAtLogin() {
    fetch('api_auth.php?action=check', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (data.authenticated) {
            // User has active server session
            sessionStorage.setItem('user', data.user.username);
            sessionStorage.setItem('userId', data.user.id);
            sessionStorage.setItem('userRole', data.user.role);
            
            const modal = document.getElementById('login-modal');
            const main = document.getElementById('main-page');
            
            if (modal) modal.style.display = 'none';
            if (main) {
                main.style.display = 'block';
                main.classList.add('show');
            }
            
            loadProfile();
            updateNavVisibility();
            loadFavorites();
            loadPageContent('home').then(() => activateSection('home'));
        } else {
            // No session, show login
            const modal = document.getElementById('login-modal');
            const main = document.getElementById('main-page');
            if (main) {
                main.style.display = 'none';
                main.classList.remove('show');
            }
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('modal-exit');
            }
        }
    })
    .catch(err => {
        console.error('Session check error:', err);
        // Show login on error
        const modal = document.getElementById('login-modal');
        const main = document.getElementById('main-page');
        if (main) main.style.display = 'none';
        if (modal) modal.style.display = 'flex';
    });
})();

// Attach profile button handlers (defensive: ensure elements exist)
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('save-profile-btn');
    if (saveBtn) saveBtn.addEventListener('click', saveProfile);
    const cancelBtn = document.getElementById('cancel-profile-btn');
    if (cancelBtn) cancelBtn.addEventListener('click', cancelEditProfile);
    // ensure profile loads if user is already logged in
    if (getCurrentUser()) {
        loadProfile();
    }
    // update nav visibility once DOM ready
    updateNavVisibility();
    // clear notifications button
    const clearBtn = document.getElementById('clear-notifications-btn');
    if (clearBtn) clearBtn.addEventListener('click', clearNotifications);
    // clear account notifications button
    const clearAcctBtn = document.getElementById('clear-account-notifs-btn');
    if (clearAcctBtn) clearAcctBtn.addEventListener('click', clearAccountNotifications);
    // attach be-seller handler in case DOMContentLoaded fires before our IIFE
    const beForm = document.getElementById('be-seller-form');
    if (beForm) {
        beForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // delegate to the same logic as the IIFE handler by reusing code path: trigger submit on the IIFE-attached listener if present
            // simple duplicate handling here to be safe
            const user = getCurrentUser();
            const msgEl = document.getElementById('be-seller-msg');
            if (!user) {
                if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'red'; msgEl.textContent = 'Please log in to request seller status.'; }
                return;
            }
            const business = document.getElementById('seller-business') ? document.getElementById('seller-business').value.trim() : '';
            const idVal = document.getElementById('seller-id') ? document.getElementById('seller-id').value.trim() : '';
            const contact = document.getElementById('seller-contact') ? document.getElementById('seller-contact').value.trim() : '';
            const desc = document.getElementById('seller-desc') ? document.getElementById('seller-desc').value.trim() : '';
            if (!business || !idVal || !contact) {
                if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'red'; msgEl.textContent = 'Please fill out the required fields.'; }
                return;
            }
            let users = JSON.parse(localStorage.getItem('users')) || [];
            let userObj = users.find(u => u.username === user);
            if (userObj) { userObj.role = 'seller'; } else { users.push({ username: user, password: '', role: 'seller' }); }
            localStorage.setItem('users', JSON.stringify(users));
            const profiles = JSON.parse(localStorage.getItem('profiles')) || {};
            profiles[user] = profiles[user] || {};
            profiles[user].role = 'seller';
            if (business) profiles[user].displayName = business;
            if (contact) profiles[user].email = contact;
            profiles[user].bio = desc || (profiles[user].bio || '');
            localStorage.setItem('profiles', JSON.stringify(profiles));
            localStorage.setItem('userRole', 'seller');
            updateNavVisibility();
            if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'green'; msgEl.textContent = 'Your account is now a seller. Redirecting to Sellers...'; }
            setTimeout(() => {
                try { showSection('sellers'); } catch (err) {}
                loadProfile(); loadProducts();
                const postBtn = document.getElementById('post-product-btn');
                if (postBtn) postBtn.style.display = 'inline-block';
                const beBox = document.getElementById('be-seller-box'); if (beBox) beBox.style.display = 'none';
            }, 900);
        });
    }
});
// Also try to attach immediately in case script runs after DOMContentLoaded
(() => {
    const saveBtn = document.getElementById('save-profile-btn');
    if (saveBtn) saveBtn.addEventListener('click', saveProfile);
    const cancelBtn = document.getElementById('cancel-profile-btn');
    if (cancelBtn) cancelBtn.addEventListener('click', cancelEditProfile);
    if (getCurrentUser()) {
        loadProfile();
        updateNavVisibility();
        renderFavorites();
    } else {
        updateNavVisibility();
    }
    // clear notifications button (IIFE attach)
    const clearBtn2 = document.getElementById('clear-notifications-btn');
    if (clearBtn2) clearBtn2.addEventListener('click', clearNotifications);
    // clear account notifications button (IIFE attach)
    const clearAcctBtn2 = document.getElementById('clear-account-notifs-btn');
    if (clearAcctBtn2) clearAcctBtn2.addEventListener('click', clearAccountNotifications);
    // Attach Be-a-Seller form handler if present
    const beForm = document.getElementById('be-seller-form');
    if (beForm) {
        beForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const user = getCurrentUser();
            const msgEl = document.getElementById('be-seller-msg');
            if (!user) {
                if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'red'; msgEl.textContent = 'Please log in to request seller status.'; }
                return;
            }

            const businessName = document.getElementById('seller-business') ? document.getElementById('seller-business').value.trim() : '';
            const businessId = document.getElementById('seller-id') ? document.getElementById('seller-id').value.trim() : '';
            const contact = document.getElementById('seller-contact') ? document.getElementById('seller-contact').value.trim() : '';
            const description = document.getElementById('seller-desc') ? document.getElementById('seller-desc').value.trim() : '';

            // Basic validation
            if (!businessName || !businessId || !contact) {
                if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'red'; msgEl.textContent = 'Please fill out the required fields.'; }
                return;
            }

            const formData = new URLSearchParams();
            formData.append('businessName', businessName);
            formData.append('businessId', businessId);
            formData.append('contact', contact);
            formData.append('description', description);

            fetch('api_profiles.php?action=become-seller', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('userRole', 'seller');
                    updateNavVisibility();
                    if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'green'; msgEl.textContent = 'Your account is now a seller. Redirecting to Sellers...'; }
                    
                    setTimeout(() => {
                        try { showSection('sellers'); } catch (err) {}
                        loadProfile();
                        loadProducts();
                        const postBtn = document.getElementById('post-product-btn');
                        if (postBtn) postBtn.style.display = 'inline-block';
                        const beBox = document.getElementById('be-seller-box');
                        if (beBox) beBox.style.display = 'none';
                    }, 900);
                } else {
                    if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'red'; msgEl.textContent = data.message || 'Error upgrading to seller'; }
                }
            })
            .catch(err => {
                console.error('Seller upgrade error:', err);
                if (msgEl) { msgEl.style.display = 'block'; msgEl.style.color = 'red'; msgEl.textContent = 'Error upgrading to seller'; }
            });
        });
    }
})();