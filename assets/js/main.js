// assets/js/main.js

// Init AOS Animation
AOS.init({ duration: 800, offset: 50, once: true, easing: 'ease-out-cubic' });

// TAB SWITCHER LOGIC
function switchTab(tab) {
    const listWork = document.getElementById('list-work');
    const listPersonal = document.getElementById('list-personal');
    const btnWork = document.getElementById('btn-work');
    const btnPersonal = document.getElementById('btn-personal');

    if(tab === 'work') {
        listWork.classList.remove('hidden'); listPersonal.classList.add('hidden');
        btnWork.className = "tab-btn active px-6 py-2.5 rounded-full text-sm font-bold bg-white text-primary transition-all duration-300 border border-transparent shadow-md";
        btnPersonal.className = "tab-btn px-6 py-2.5 rounded-full text-sm font-bold bg-white/10 text-white hover:bg-white/20 transition-all duration-300 border border-transparent border-white/10";
    } else {
        listWork.classList.add('hidden'); listPersonal.classList.remove('hidden');
        btnPersonal.className = "tab-btn active px-6 py-2.5 rounded-full text-sm font-bold bg-white text-primary transition-all duration-300 border border-transparent shadow-md";
        btnWork.className = "tab-btn px-6 py-2.5 rounded-full text-sm font-bold bg-white/10 text-white hover:bg-white/20 transition-all duration-300 border border-transparent border-white/10";
    }
}

// PROJECT MODAL LOGIC
// Note: variable projectData & is_en dikirim dari PHP di footer
function openProjectModal(index) {
    const p = projectData[index];
    const desc = is_en && p.description_en ? p.description_en : p.description;
    
    document.getElementById('modalTitle').innerText = p.title;
    document.getElementById('modalCat').innerText = p.category;
    document.getElementById('modalImg').src = p.image_url;
    // DOMPurify sebaiknya dipasang disini nanti, untuk sekarang innerHTML manual dulu
    document.getElementById('modalDesc').innerHTML = desc;
    
    // Tech Stack
    let techHtml = '';
    if(p.tech_stack) {
        p.tech_stack.split(',').forEach(t => {
            techHtml += `<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-lg text-xs font-bold border border-gray-200">${t.trim()}</span>`;
        });
    }
    document.getElementById('modalTech').innerHTML = techHtml;

    // Challenge & Impact logic
    const chalBox = document.getElementById('boxChallenge');
    const impBox = document.getElementById('boxImpact');
    
    if(p.challenge && p.challenge.trim() !== '') {
        document.getElementById('modalChallenge').innerHTML = p.challenge;
        chalBox.classList.remove('hidden');
    } else { chalBox.classList.add('hidden'); }

    if(p.impact && p.impact.trim() !== '') {
        document.getElementById('modalImpact').innerHTML = p.impact;
        impBox.classList.remove('hidden');
    } else { impBox.classList.add('hidden'); }

    // Link logic
    const btn = document.getElementById('modalLink');
    if(p.link_demo && p.link_demo !== '#' && p.link_demo !== '') {
        btn.href = p.link_demo;
        btn.classList.remove('hidden'); 
    } else { btn.classList.add('hidden'); }

    document.getElementById('projectModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; 
}

function closeModal() { 
    document.getElementById('projectModal').classList.add('hidden'); 
    document.body.style.overflow = 'auto'; 
}

// IMAGE MODAL
function openImageModal(src) { 
    document.getElementById('lightboxImg').src = src; 
    document.getElementById('imageModal').classList.remove('hidden'); 
    document.body.style.overflow = 'hidden'; 
}
function closeImageModal() { 
    document.getElementById('imageModal').classList.add('hidden'); 
    document.body.style.overflow = 'auto'; 
}

// CAREER MODAL
function openCareerModal(company) {
    document.getElementById('careerModal').classList.remove('hidden'); 
    document.body.style.overflow = 'hidden';
    document.getElementById('careerCompany').innerText = company;
    const roles = careerData[company];
    
    let foundImg = defaultCartoon;
    if(roles) { 
        for (let i = 0; i < roles.length; i++) { 
            if (roles[i].image && roles[i].image.trim() !== "") { 
                foundImg = 'assets/img/' + roles[i].image; break; 
            } 
        } 
    }
    const imgEl = document.getElementById('careerCartoon'); 
    imgEl.src = foundImg; 
    imgEl.style.display = ''; 
    imgEl.classList.remove('hidden'); 
    
    if(window.innerWidth < 768) imgEl.classList.add('hidden'); 
    else imgEl.classList.add('md:block');
    
    let html = roles ? (roles.length > 1 ? '<div class="grid grid-cols-1 gap-6">' : '<div>') : '';
    if(roles) { 
        roles.forEach(role => { 
            html += `<div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:border-accent/30 transition shadow-sm hover:shadow-md"><div class="flex justify-between items-start mb-4 border-b border-gray-200 pb-3"><div><h4 class="font-bold text-lg text-primary">${role.role}</h4><div class="text-xs text-gray-500 font-bold uppercase tracking-wider mt-1">Full Time</div></div><span class="text-xs font-bold bg-primary text-white px-3 py-1 rounded-full text-center h-fit">${role.year}</span></div><div class="text-sm text-gray-600 leading-relaxed space-y-2 prose prose-sm max-w-none"><div class="db-desc">${role.description}</div></div></div>`; 
        }); 
        html += '</div>'; 
    }
    document.getElementById('careerContent').innerHTML = html;
}
function closeCareerModal() { 
    document.getElementById('careerModal').classList.add('hidden'); 
    document.body.style.overflow = 'auto'; 
}

// CHATBOT
function toggleChatbot() { 
    document.getElementById('chatbotFrame').classList.toggle('hidden'); 
}