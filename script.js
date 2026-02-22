// ========================================
// GLOBAL STATE
// ========================================
let currentUser = null;
let allMembers = [];
let allLeaders = [];

// ========================================
// HELPER FUNCTIONS
// ========================================

function getAvatarPath(username) {
    // Check if avatar exists in images/RZN_MEMBERS_AVATAR folder
    // Avatar files are named: USERNAME_Avatar.jpg (e.g., RZN.J3em_Avatar.jpg)
    if (username) {
        const avatarPath = `images/RZN_MEMBERS_AVATAR/${username}_Avatar.jpg`;
        return avatarPath;
    }
    return 'images/RZN_LOGO.png'; // Fallback to logo if no username
}

// ========================================
// API FUNCTIONS
// ========================================

async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    
    for (let key in data) {
        formData.append(key, data[key]);
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
    } catch(error) {
        console.error('API Error:', error);
        return { success: false, message: 'API request failed', error: error.message };
    }
}

// ========================================
// INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', async () => {
    // Setup event listeners FIRST - they must work even if API fails
    setupEventListeners();
    
    // Then try to load data - these may fail if database isn't set up yet
    try {
        await checkLoginStatus();
    } catch(e) {
        console.log('Login check failed:', e);
    }
    
    try {
        await loadLeaders();
    } catch(e) {
        console.log('Leaders load failed:', e);
    }
    
    try {
        await loadMembers();
    } catch(e) {
        console.log('Members load failed:', e);
    }
});

// ========================================
// AUTH FUNCTIONS
// ========================================

async function checkLoginStatus() {
    const result = await apiCall('getCurrentUser');
    if (result.success) {
        currentUser = result.user;
        updateAuthUI();
    }
}

function updateAuthUI() {
    const loginNavBtn = document.getElementById('loginNavBtn');
    const registerNavBtn = document.getElementById('registerNavBtn');
    const profileNavBtn = document.getElementById('profileNavBtn');
    const adminNavBtn = document.getElementById('adminNavBtn');
    const logoutBtn = document.getElementById('logoutBtn');

    if (currentUser) {
        loginNavBtn.style.display = 'none';
        registerNavBtn.style.display = 'none';
        profileNavBtn.style.display = 'block';
        logoutBtn.style.display = 'block';
        
        if (currentUser.role === 'admin' || currentUser.role === 'leader') {
            adminNavBtn.style.display = 'block';
        }
    } else {
        loginNavBtn.style.display = 'block';
        registerNavBtn.style.display = 'block';
        profileNavBtn.style.display = 'none';
        adminNavBtn.style.display = 'none';
        logoutBtn.style.display = 'none';
    }
}

async function handleRegister() {
    const username = document.getElementById('regUsername').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    const errorEl = document.getElementById('registerError');
    const successEl = document.getElementById('registerSuccess');
    
    errorEl.style.display = 'none';
    successEl.style.display = 'none';
    
    if (!username || !email || !password) {
        errorEl.textContent = 'All fields are required';
        errorEl.style.display = 'block';
        return;
    }
    
    const result = await apiCall('register', { username, email, password });
    
    if (result.success) {
        successEl.textContent = result.message;
        successEl.style.display = 'block';
        
        document.getElementById('regUsername').value = '';
        document.getElementById('regEmail').value = '';
        document.getElementById('regPassword').value = '';
        
        setTimeout(() => {
            navigateTo('login');
        }, 2000);
    } else {
        errorEl.textContent = result.message;
        errorEl.style.display = 'block';
    }
}

async function handleLogin() {
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    const errorEl = document.getElementById('loginError');
    
    errorEl.style.display = 'none';
    
    if (!username || !password) {
        errorEl.textContent = 'Username and password are required';
        errorEl.style.display = 'block';
        return;
    }
    
    const result = await apiCall('login', { username, password });
    
    if (result.success) {
        currentUser = result.user;
        updateAuthUI();
        navigateTo('profile');
        loadProfileData();
    } else {
        errorEl.textContent = result.message;
        errorEl.style.display = 'block';
    }
}

async function handleLogout() {
    await apiCall('logout');
    currentUser = null;
    updateAuthUI();
    navigateTo('home');
}

// ========================================
// DATA LOADING
// ========================================

async function loadLeaders() {
    const result = await apiCall('getLeaders');
    if (result.success && result.leaders) {
        allLeaders = result.leaders;
        renderLeaders();
    } else {
        allLeaders = [];
    }
}

async function loadMembers() {
    const result = await apiCall('getMembers');
    if (result.success && result.members) {
        allMembers = result.members;
        renderMembers();
    } else {
        allMembers = [];
        // Show empty state
        const container = document.getElementById('membersContainer');
        if (container) {
            container.innerHTML = '<p style="text-align: center; color: #a0a0a0;">No members loaded. Please set up the database.</p>';
        }
    }
}

async function loadProfileData() {
    if (!currentUser) return;
    
    const profileName = document.getElementById('profileName');
    const profileRole = document.getElementById('profileRole');
    const profileRoleField = document.getElementById('profileRoleField');
    const profileAvatarPreview = document.getElementById('profileAvatarPreview');
    const facebookInput = document.getElementById('facebookInput');
    const youtubeInput = document.getElementById('youtubeInput');
    const tiktokInput = document.getElementById('tiktokInput');
    
    profileName.textContent = currentUser.username;
    
    // Show role if admin or leader
    if (currentUser.role === 'leader') {
        profileRole.textContent = '👑 LEADER';
        profileRole.style.color = '#ffd700';
        profileRoleField.style.display = 'flex';
    } else if (currentUser.role === 'admin') {
        profileRole.textContent = '⚔️ CO-FOUNDER';
        profileRole.style.color = '#c0c0c0';
        profileRoleField.style.display = 'flex';
    } else {
        profileRoleField.style.display = 'none';
    }
    
    profileAvatarPreview.src = currentUser.avatar || getAvatarPath(currentUser.username);
    facebookInput.value = currentUser.facebook_url || '';
    youtubeInput.value = currentUser.youtube_url || '';
    tiktokInput.value = currentUser.tiktok_url || '';
}

async function loadPendingMembers() {
    const result = await apiCall('getPendingMembers');
    const container = document.getElementById('pendingContainer');
    const noPending = document.getElementById('noPending');
    
    if (result.success && result.pending.length > 0) {
        noPending.style.display = 'none';
        container.innerHTML = result.pending.map(member => `
            <div class="pending-card">
                <div class="pending-info">
                    <h3>${member.username}</h3>
                    <p>Email: ${member.email}</p>
                    <p>Applied: ${new Date(member.created_at).toLocaleDateString()}</p>
                </div>
                <div class="pending-actions">
                    <button class="approve-btn" onclick="approveMember(${member.id})">✓ Approve</button>
                    <button class="decline-btn" onclick="declineMember(${member.id})">✗ Decline</button>
                </div>
            </div>
        `).join('');
    } else {
        noPending.style.display = 'block';
        container.innerHTML = '';
    }
}

async function loadAllMembers() {
    const result = await apiCall('getAllMembers');
    const container = document.getElementById('allMembersContainer');
    const section = document.getElementById('allMembersSection');
    const subtitle = document.getElementById('adminSubtitle');
    
    if (result.success) {
        const isLeader = result.currentRole === 'leader';
        
        if (isLeader) {
            section.style.display = 'block';
            subtitle.textContent = '👑 Leader Panel - Full Control';
            
            container.innerHTML = result.members.map(member => {
                let roleDisplay = '';
                let roleClass = '';
                
                if (member.role === 'leader') {
                    roleDisplay = '👑 LEADER';
                    roleClass = 'role-leader';
                } else if (member.role === 'admin') {
                    roleDisplay = '⚔️ CO-FOUNDER';
                    roleClass = 'role-admin';
                } else if (member.role === 'member') {
                    roleDisplay = '👤 MEMBER';
                    roleClass = 'role-member';
                } else if (member.role === 'pending') {
                    roleDisplay = '⏳ PENDING';
                    roleClass = 'role-pending';
                }
                
                const canDelete = member.id != currentUser.id && member.role !== 'pending';
                
                return `
                    <div class="pending-card">
                        <div class="pending-info">
                            <h3>${member.username} <span class="role-badge ${roleClass}">${roleDisplay}</span></h3>
                            <p>Email: ${member.email}</p>
                            <p>Joined: ${new Date(member.created_at).toLocaleDateString()}</p>
                        </div>
                        ${canDelete ? `
                            <div class="pending-actions">
                                <button class="decline-btn" onclick="deleteMemberById(${member.id}, '${member.username}')">🗑️ Delete</button>
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        } else {
            section.style.display = 'none';
            subtitle.textContent = '⚔️ Co-Founder Panel';
        }
    }
}

// ========================================
// ADMIN FUNCTIONS
// ========================================

async function approveMember(memberId) {
    if (!confirm('Approve this member?')) return;
    
    const result = await apiCall('approveMember', { member_id: memberId });
    if (result.success) {
        alert(result.message);
        loadPendingMembers();
        loadMembers();
        loadAllMembers();
    } else {
        alert('Error: ' + result.message);
    }
}

async function declineMember(memberId) {
    if (!confirm('Decline this member? This will delete their account.')) return;
    
    const result = await apiCall('declineMember', { member_id: memberId });
    if (result.success) {
        alert(result.message);
        loadPendingMembers();
    } else {
        alert('Error: ' + result.message);
    }
}

async function deleteMemberById(memberId, username) {
    if (!confirm(`Are you sure you want to DELETE ${username}? This action cannot be undone!`)) return;
    
    const result = await apiCall('deleteMember', { member_id: memberId });
    if (result.success) {
        alert(result.message);
        loadAllMembers();
        loadMembers();
    } else {
        alert('Error: ' + result.message);
    }
}

// ========================================
// RENDER FUNCTIONS
// ========================================

function renderLeaders() {
    const container = document.getElementById('leadersContainer');
    if (allLeaders.length === 0) return;
    
    const order = allLeaders.length >= 3 ? [1, 0, 2] : [0];
    
    container.innerHTML = order.map(i => {
        const leader = allLeaders[i];
        let role = '';
        if (leader.role === 'leader') {
            role = 'Founder';
        } else {
            role = 'Co-Founder';
        }
        
        return `
            <div class="leader-card ${i === 0 ? 'founder-card' : ''}">
                <div class="leader-card-content ${i === 0 ? 'founder-content' : ''}">
                    <img src="${leader.avatar || getAvatarPath(leader.username)}" alt="${leader.username}" class="leader-image">
                    <h2 class="leader-name">${leader.username}</h2>
                    <p class="leader-role">${role}</p>
                </div>
            </div>
        `;
    }).join('');
}

function renderMembers() {
    const container = document.getElementById('membersContainer');
    const memberCount = document.getElementById('memberCount');
    
    memberCount.textContent = allMembers.length;
    
    container.innerHTML = allMembers.map(member => `
        <div class="member-card">
            <div class="member-card-content">
                <div class="member-avatar">
                    <div class="member-avatar-img">
                        <img src="${member.avatar || getAvatarPath(member.username)}" alt="${member.username}">
                    </div>
                </div>
                <h3 class="member-name">${member.username}</h3>
                <div class="member-socials">
                    <a href="${member.facebook_url || '#'}" class="social-link social-facebook" title="Facebook" target="_blank">
                        <span class="social-icon">f</span>
                        <span class="social-text">Facebook</span>
                    </a>
                    <a href="${member.youtube_url || '#'}" class="social-link social-youtube" title="YouTube" target="_blank">
                        <span class="social-icon">▶️</span>
                        <span class="social-text">YouTube</span>
                    </a>
                    <a href="${member.tiktok_url || '#'}" class="social-link social-tiktok" title="TikTok" target="_blank">
                        <span class="social-icon">♪</span>
                        <span class="social-text">TikTok</span>
                    </a>
                </div>
            </div>
        </div>
    `).join('');
}

// ========================================
// EVENT LISTENERS
// ========================================

function setupEventListeners() {
    document.getElementById('mobileMenuBtn')?.addEventListener('click', toggleMobileMenu);
    
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const page = e.currentTarget.dataset.page;
            if (page) navigateTo(page);
        });
    });
    
    document.querySelector('.view-members-btn')?.addEventListener('click', (e) => {
        navigateTo(e.currentTarget.dataset.page);
    });
    
    document.getElementById('searchInput')?.addEventListener('input', (e) => {
        searchMembers(e.target.value);
    });
    
    document.getElementById('registerBtn')?.addEventListener('click', handleRegister);
    document.getElementById('loginBtn')?.addEventListener('click', handleLogin);
    document.getElementById('logoutBtn')?.addEventListener('click', handleLogout);
    
    document.getElementById('profileNavBtn')?.addEventListener('click', () => {
        navigateTo('profile');
        loadProfileData();
    });
    
    document.getElementById('adminNavBtn')?.addEventListener('click', () => {
        navigateTo('admin');
        loadPendingMembers();
        loadAllMembers();
    });
    
    document.getElementById('avatarUpload')?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                document.getElementById('profileAvatarPreview').src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('saveProfileBtn')?.addEventListener('click', async () => {
        const avatar = document.getElementById('profileAvatarPreview').src;
        const facebook = document.getElementById('facebookInput').value;
        const youtube = document.getElementById('youtubeInput').value;
        const tiktok = document.getElementById('tiktokInput').value;
        
        const result = await apiCall('updateProfile', {
            avatar,
            facebook_url: facebook,
            youtube_url: youtube,
            tiktok_url: tiktok
        });
        
        const messageEl = document.getElementById('profileMessage');
        if (result.success) {
            messageEl.textContent = '✅ ' + result.message;
            messageEl.style.color = '#4ade80';
            messageEl.style.display = 'block';
            
            await loadMembers();
            
            setTimeout(() => {
                messageEl.style.display = 'none';
            }, 3000);
        } else {
            messageEl.textContent = '❌ ' + result.message;
            messageEl.style.color = '#ff6b6b';
            messageEl.style.display = 'block';
        }
    });
    
    document.querySelectorAll('.link-text').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            navigateTo(e.target.dataset.page);
        });
    });
}

// ========================================
// NAVIGATION
// ========================================

function toggleMobileMenu() {
    const navLinks = document.getElementById('navLinks');
    const hamburger = document.getElementById('hamburger');
    navLinks.classList.toggle('active');
    hamburger.textContent = navLinks.classList.contains('active') ? '✕' : '☰';
}

function navigateTo(page) {
    document.getElementById('homePage').style.display = 'none';
    document.getElementById('membersPage').style.display = 'none';
    document.getElementById('registerPage').style.display = 'none';
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('profilePage').style.display = 'none';
    document.getElementById('adminPage').style.display = 'none';
    
    const pageMap = {
        home: 'homePage',
        members: 'membersPage',
        register: 'registerPage',
        login: 'loginPage',
        profile: 'profilePage',
        admin: 'adminPage'
    };
    
    const pageId = pageMap[page];
    if (pageId) {
        document.getElementById(pageId).style.display = 'flex';
    }
    
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.page === page) {
            btn.classList.add('active');
        }
    });
    
    document.getElementById('navLinks').classList.remove('active');
    document.getElementById('hamburger').textContent = '☰';
    
    window.scrollTo(0, 0);
}

function searchMembers(query) {
    const cards = document.querySelectorAll('.member-card');
    const noResults = document.getElementById('noResults');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const name = card.querySelector('.member-name').textContent.toLowerCase();
        if (name.includes(query.toLowerCase())) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    noResults.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';
}
