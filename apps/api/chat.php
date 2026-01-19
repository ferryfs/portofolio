<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,400&display=swap" rel="stylesheet">

    <style>
        body { background-color: #ffffff; font-family: 'Satoshi', sans-serif; overflow: hidden; }
        .chat-container { height: 100vh; display: flex; flex-direction: column; }
        
        /* Area Chatting */
        #chat-box { 
            flex: 1; 
            overflow-y: auto; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 15px;
            scroll-behavior: smooth;
        }
        /* Hide Scrollbar */
        #chat-box::-webkit-scrollbar { width: 5px; }
        #chat-box::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }

        /* Bubble Chat */
        .bubble { max-width: 85%; padding: 12px 16px; border-radius: 20px; font-size: 14px; line-height: 1.5; word-wrap: break-word; }
        
        /* User (Kanan, Biru) */
        .bubble.user { align-self: flex-end; background-color: #2563EB; color: white; border-bottom-right-radius: 2px; }
        
        /* AI (Kiri, Abu) */
        .bubble.ai { align-self: flex-start; background-color: #F3F4F6; color: #1f2937; border-bottom-left-radius: 2px; border: 1px solid #e5e7eb; }

        /* Loading Animation */
        .typing { display: flex; gap: 5px; align-self: flex-start; background: #F3F4F6; padding: 12px 16px; border-radius: 20px; border-bottom-left-radius: 2px; }
        .dot { width: 6px; height: 6px; background: #9CA3AF; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; }
        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }

        /* Input Area */
        .input-area { padding: 15px; background: white; border-top: 1px solid #f0f0f0; display: flex; gap: 10px; align-items: center; }
        .input-area input { border-radius: 50px; padding: 12px 20px; font-size: 14px; border: 1px solid #e5e7eb; background: #f9fafb; }
        .input-area input:focus { box-shadow: none; border-color: #2563EB; background: #fff; }
        .btn-send { border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background: #2563EB; color: white; border: none; transition: 0.2s; flex-shrink: 0; }
        .btn-send:hover { background: #1d4ed8; transform: scale(1.05); }
        .btn-send:disabled { background: #ccc; cursor: not-allowed; transform: none; }
    </style>
</head>
<body>

<div class="chat-container">
    <div id="chat-box">
        <div class="bubble ai">
            Halo! 👋 Saya Ferry AI.<br>Tanya saya apa saja tentang pengalaman, skill, atau project Ferry Fernando.
        </div>
    </div>

    <div class="input-area">
        <input type="text" id="user-input" class="form-control" placeholder="Ketik pertanyaan..." autocomplete="off">
        <button id="send-btn" class="btn-send"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');

    function addBubble(text, sender) {
        const div = document.createElement('div');
        div.classList.add('bubble', sender);
        // Format Teks biar rapi (Bold & Newline)
        let formatted = text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
        div.innerHTML = formatted;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function showLoading() {
        const div = document.createElement('div');
        div.classList.add('typing');
        div.id = 'loading-bubble';
        div.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function removeLoading() {
        const loading = document.getElementById('loading-bubble');
        if(loading) loading.remove();
    }

    async function sendMessage() {
        const message = userInput.value.trim();
        if (!message) return;

        addBubble(message, 'user');
        userInput.value = '';
        userInput.disabled = true;
        sendBtn.disabled = true;
        showLoading();

        try {
            // 🔥 INI BAGIAN PENTING: DIA NGOBROL SAMA CHAT_BRAIN.PHP
            const response = await fetch('chat_brain.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();
            removeLoading();
            
            if (data.reply) {
                addBubble(data.reply, 'ai');
            } else {
                addBubble("Maaf, AI lagi pusing (Server Error).", 'ai');
            }

        } catch (error) {
            removeLoading();
            addBubble("Gagal koneksi ke otak AI. Cek console.", 'ai');
            console.error(error);
        } finally {
            userInput.disabled = false;
            sendBtn.disabled = false;
            userInput.focus();
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });
</script>

</body>
</html>