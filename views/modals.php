<div class="fixed bottom-8 right-8 z-40 flex flex-col items-end">
        <div class="bg-white py-2 px-4 rounded-xl shadow-lg mb-2 animate-bounce text-sm font-bold text-accent hidden md:block"><?php echo $txt['chatbot_invite']; ?> 👇</div>
        <button onclick="toggleChatbot()" class="bg-accent text-white p-4 rounded-full shadow-2xl hover:bg-primary transition hover:scale-110 flex items-center justify-center w-16 h-16 relative z-20"><i class="bi bi-robot text-3xl"></i></button>
        <div id="chatbotFrame" class="hidden fixed bottom-24 right-4 md:right-8 w-[90%] md:w-[350px] h-[500px] max-h-[80vh] bg-white rounded-3xl shadow-2xl border border-gray-200 overflow-hidden z-50 animate-fadeInUp origin-bottom-right flex flex-col">
            <div class="bg-gray-50 p-3 flex justify-between items-center border-b flex-none"><span class="font-bold text-sm">Ferry AI Assistant</span><button onclick="toggleChatbot()" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button></div>
            <iframe src="apps/api/chat.php" class="w-full flex-1 border-0" loading="lazy"></iframe>
        </div>
    </div>

    <div id="projectModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-primary/95 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-5xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out] relative h-auto max-h-[90vh] flex flex-col md:flex-row">
                <div class="w-full md:w-5/12 bg-gray-100 h-64 md:h-auto relative"><img id="modalImg" src="" class="w-full h-full object-cover"><button onclick="closeModal()" class="absolute top-4 left-4 bg-white/50 p-2 rounded-full md:hidden"><i class="bi bi-x-lg"></i></button></div>
                <div class="w-full md:w-7/12 flex flex-col h-full overflow-hidden">
                    <div class="p-8 md:p-10 flex-1 overflow-y-auto custom-scroll">
                        <div class="flex justify-between items-start mb-6">
                            <div><span id="modalCat" class="text-[10px] font-bold bg-accent/10 text-accent px-3 py-1 rounded-full uppercase tracking-widest mb-3 inline-block">Category</span><h3 id="modalTitle" class="text-3xl md:text-4xl font-black text-primary leading-tight"></h3></div>
                            <button onclick="closeModal()" class="hidden md:block bg-gray-100 hover:bg-red-50 hover:text-red-500 w-10 h-10 rounded-full flex-none flex items-center justify-center transition"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <div class="mb-8"><h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Tech Stack</h5><div id="modalTech" class="flex flex-wrap gap-2"></div></div>
                        <div class="space-y-8">
                            <div><h5 class="font-bold text-primary text-lg mb-2 flex items-center gap-2"><i class="bi bi-info-circle text-accent"></i> Overview</h5><div id="modalDesc" class="text-gray-600 leading-relaxed db-desc prose prose-sm max-w-none"></div></div>
                            <div id="boxChallenge" class="bg-red-50 p-6 rounded-2xl border border-red-100"><h5 class="font-bold text-red-600 text-sm uppercase tracking-wider mb-2">The Challenge</h5><div id="modalChallenge" class="text-gray-700 text-sm leading-relaxed db-desc"></div></div>
                            <div id="boxImpact" class="bg-green-50 p-6 rounded-2xl border border-green-100"><h5 class="font-bold text-green-600 text-sm uppercase tracking-wider mb-2">The Impact</h5><div id="modalImpact" class="text-gray-700 text-sm leading-relaxed db-desc"></div></div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-100 bg-white sticky bottom-0 z-10 flex gap-4"><a id="modalLink" href="#" target="_blank" class="flex-1 bg-primary text-white py-4 rounded-xl font-bold text-center hover:bg-accent transition shadow-lg">Visit Project <i class="bi bi-arrow-up-right ms-1"></i></a></div>
                </div>
            </div>
        </div>
    </div>

    <div id="imageModal" class="fixed inset-0 z-[110] hidden" onclick="closeImageModal()">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-md"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <img id="lightboxImg" src="" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl animate-[fadeInUp_0.3s_ease-out]" loading="lazy">
            <button class="absolute top-6 right-6 text-white text-4xl hover:text-accent">&times;</button>
        </div>
    </div>

    <div id="careerModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-primary/80 backdrop-blur-sm transition-opacity" onclick="closeCareerModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-5xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out] relative h-auto md:h-[80vh] flex flex-col md:flex-row max-h-[85vh]">
                <div class="w-full md:w-1/3 bg-blue-50 flex items-center justify-center p-4 md:p-8 border-b md:border-b-0 md:border-r border-gray-100 relative min-h-[60px] md:min-h-0">
                    <img id="careerCartoon" src="" class="hidden md:block w-48 md:w-full drop-shadow-xl animate-[bounce_4s_infinite]" onerror="this.style.display='none'">
                    <div class="relative md:absolute md:bottom-4 text-center w-full text-xs font-bold uppercase tracking-widest text-black md:text-gray-400 opacity-100 md:opacity-50">Career Highlights</div>
                </div>
                <div class="w-full md:w-2/3 flex flex-col h-full overflow-hidden">
                    <div class="p-6 border-b flex justify-between items-center bg-white sticky top-0 z-10 flex-none"><h3 id="careerCompany" class="text-xl md:text-2xl font-black text-primary"></h3><button onclick="closeCareerModal()" class="bg-gray-100 hover:bg-red-50 hover:text-red-500 w-10 h-10 rounded-full flex items-center justify-center transition"><i class="bi bi-x-lg"></i></button></div>
                    <div class="flex-1 overflow-y-auto custom-scroll"><div id="careerContent" class="p-6 md:p-8"></div></div>
                </div>
            </div>
        </div>
    </div>