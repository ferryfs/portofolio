// ============================================
// FERRY FERNANDO — IMPROVED MAIN.JS
// ============================================

// Init AOS
AOS.init({ duration: 800, offset: 60, once: true, easing: 'ease-out-cubic' });

// ---- MAIN TAB SWITCHER (fix garis biru) ----
function switchMainTab(tab) {
    ['work', 'personal'].forEach(t => {
        const content = document.getElementById('content-' + t);
        const btn = document.getElementById('main-tab-' + t);
        if (!content || !btn) return;

        if (t === tab) {
            content.classList.remove('hidden');
            btn.classList.add('active');
            btn.style.borderBottomColor = '#2563EB';
            btn.style.color = '#ffffff';
        } else {
            content.classList.add('hidden');
            btn.classList.remove('active');
            btn.style.borderBottomColor = 'transparent';
            btn.style.color = 'rgba(255,255,255,0.4)';
        }
    });
}

// ---- SUB TAB SWITCHER (fix sub-tab work) ----
function switchSubTab(slug) {
    // Hide semua sub-content
    document.querySelectorAll('[id^="sub-content-"]').forEach(el => {
        el.classList.add('hidden');
        el.style.display = 'none';
    });
    // Show yang dipilih
    const target = document.getElementById('sub-content-' + slug);
    if (target) {
        target.classList.remove('hidden');
        target.style.display = 'flex';
    }

    // Update button state
    document.querySelectorAll('[id^="sub-btn-"]').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBtn = document.getElementById('sub-btn-' + slug);
    if (activeBtn) activeBtn.classList.add('active');
}

// Init tab state saat halaman load
document.addEventListener('DOMContentLoaded', function () {
    switchMainTab('work');
    switchSubTab('all');

    // Init drag scroll
    document.querySelectorAll('.drag-scroll').forEach(el => {
        let isDown = false, startX, scrollLeft, moved = false;
        el.addEventListener('mousedown', e => {
            isDown = true;
            moved = false;
            startX = e.pageX - el.offsetLeft;
            scrollLeft = el.scrollLeft;
        });
        el.addEventListener('mouseleave', () => { isDown = false; });
        el.addEventListener('mouseup', () => { isDown = false; });
        el.addEventListener('mousemove', e => {
            if (!isDown) return;
            const x = e.pageX - el.offsetLeft;
            const walk = (x - startX) * 1.5;
            if (Math.abs(walk) > 5) moved = true;
            el.scrollLeft = scrollLeft - walk;
        });
        // Prevent click kalau lagi drag
        el.addEventListener('click', e => {
            if (moved) e.stopPropagation();
        }, true);
    });
});

// ---- IMAGE LIGHTBOX ----
function openImageModal(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// ---- CAREER MODAL ----
function openCareerModal(company) {
    const modal = document.getElementById('careerModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('careerCompany').innerText = company;

    const roles = careerData[company];

    const imgEl = document.getElementById('careerCartoon');
    if (imgEl) {
        let foundImg = (typeof defaultCartoon !== 'undefined') ? defaultCartoon : '';
        if (roles) {
            for (let i = 0; i < roles.length; i++) {
                if (roles[i].image && roles[i].image.trim() !== '') {
                    foundImg = 'assets/img/' + roles[i].image;
                    break;
                }
            }
        }
        imgEl.src = foundImg;
        imgEl.style.display = window.innerWidth < 768 ? 'none' : 'block';
    }

    let html = '';
    if (roles && roles.length > 0) {
        html = '<div class="space-y-5">';
        roles.forEach(role => {
            html += `
            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 hover:border-blue-100 hover:shadow-md transition-all duration-300">
                <div class="flex justify-between items-start mb-3 pb-3 border-b border-gray-100">
                    <div>
                        <h4 class="font-black text-base text-gray-900 leading-tight">${role.role}</h4>
                        <p class="text-xs text-gray-500 font-semibold mt-0.5 uppercase tracking-wider">Full Time</p>
                    </div>
                    <span class="text-[10px] font-black bg-blue-600 text-white px-3 py-1.5 rounded-full text-center shrink-0 ml-4">${role.year}</span>
                </div>
                <div class="db-desc text-sm text-gray-600">${role.description}</div>
            </div>`;
        });
        html += '</div>';
    } else {
        html = '<p class="text-gray-400 text-sm italic p-4">Tidak ada detail tersedia.</p>';
    }
    document.getElementById('careerContent').innerHTML = html;
}
function closeCareerModal() {
    document.getElementById('careerModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// ---- CHATBOT ----
function toggleChatbot() {
    const frame = document.getElementById('chatbotFrame');
    if (frame) frame.classList.toggle('hidden');
}

// ---- KEYBOARD SHORTCUTS ----
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeCareerModal();
        closeImageModal();
        if (typeof closeModal === 'function') closeModal();
    }
    if (e.ctrlKey && e.shiftKey && (e.key === 'X' || e.key === 'x')) {
        window.location.href = 'admin';
    }
});

// ---- LINK INTERCEPTOR (security) ----
document.querySelectorAll('a').forEach(link => {
    const href = link.getAttribute('href');
    if (
        href &&
        !href.includes('logout') &&
        link.getAttribute('target') !== '_blank' &&
        !href.startsWith('mailto') &&
        !href.startsWith('https://wa') &&
        !href.startsWith('javascript') &&
        !href.startsWith('#')
    ) {
        const urlTujuan = href;
        link.setAttribute('href', 'javascript:void(0);');
        link.addEventListener('click', () => { window.location.href = urlTujuan; });
    }
});