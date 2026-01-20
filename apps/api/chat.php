<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Assistant</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        /* --- RESET & BASIC --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100%; /* KUNCI BIAR GAK KEPOTONG */
            width: 100%;
            font-family: 'Outfit', sans-serif;
            background-color: #ffffff;
            overflow: hidden; /* Hilangkan scroll body utama */
        }

        /* --- LAYOUT UTAMA (FLEXBOX) --- */
        .chat-wrapper {
            display: flex;
            flex-direction: column;
            height: 100%; /* Full tinggi iframe */
            background-color: #fff;
        }

        /* --- 1. HEADER (BIRU) --- */
        .chat-header {
            flex-shrink: 0; /* Gak boleh gepeng */
            background-color: #2563EB; /* Biru Solid */
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .bot-info { display: flex; align-items: center; gap: 12px; }
        .bot-avatar {
            width: 35px; height: 35px;
            background: white;
            color: #2563EB;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .bot-text h4 { font-size: 16px; font-weight: 700; margin: 0; line-height: 1.2; }
        .bot-text span { font-size: 12px; opacity: 0.9; display: flex; align-items: center; gap: 5px; }
        .online-dot { width: 6px; height: 6px; background: #4ade80; border-radius: 50%; }

        /* --- 2. CHAT AREA (TENGAH) --- */
        #chat-messages {
            flex-grow: 1; /* Isi ruang kosong */
            overflow-y: auto; /* Scroll cuma di sini */
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background-color: #f8fafc; /* Abu sangat muda */
        }
        
        /* Scrollbar Halus */
        #chat-messages::-webkit-scrollbar { width: 5px; }
        #chat-messages::-webkit-scrollbar-track { background: transparent; }
        #chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* Bubbles */
        .msg {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.5;
            position: relative;
            animation: fadeIn 0.3s ease;
        }

        /* AI Bubble (Abu) */
        .msg.ai {
            align-self: flex-start;
            background: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        /* User Bubble (Biru) */
        .msg.user {
            align-self: flex-end;
            background: #2563EB; /* Biru sama kayak Header */
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 2px 5px rgba(37, 99, 235, 0.2);
        }

        /* --- 3. INPUT AREA (BAWAH) --- */
        .input-box {
            flex-shrink: 0; /* Gak boleh gepeng */
            padding: 15px;
            background: white;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .input-box input {
            flex: 1;
            padding: 12px 20px;
            border-radius: 50px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }
        .input-box input:focus {
            border-color: #2563EB;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-send {
            width: 45px; height: 45px;
            border-radius: 50%;
            background: #2563EB;
            color: white;
            border: none;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-send:hover { background: #1d4ed8; transform: scale(1.05); }
        .btn-send:disabled { background: #94a3b8; cursor: not-allowed; }

        /* Typing Dots */
        .typing { display: flex; gap: 4px; padding: 15px; background: white; width: fit-content; border-radius: 18px; border-bottom-left-radius: 4px; border: 1px solid #e2e8f0; }
        .dot { width: 6px; height: 6px; background: #94a3b8; border-radius: 50%; animation: bounce 1.4s infinite; }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    </style>
</head>
<body>

    <div class="chat-wrapper">
        <div class="chat-header">
            <div class="bot-info">
                <div class="bot-avatar"><i class="bi bi-robot"></i></div>
                <div class="bot-text">
                    <h4>Ferry AI</h4>
                    <span><div class="online-dot"></div> Online</span>
                </div>
            </div>
            <button onclick="location.reload()" style="background:none; border:none; color:white; cursor:pointer; opacity:0.8;">
                <i class="bi bi-arrow-clockwise" style="font-size: 1.2rem;"></i>
            </button>
        </div>

        <div id="chat-messages">
            <div class="msg ai">
                Halo! 👋 Saya asisten AI Ferry.<br>Ada yang bisa saya bantu?
            </div>
        </div>

        <div class="input-box">
            <input type="text" id="user-input" placeholder="Tanya sesuatu..." autocomplete="off">
            <button id="send-btn" class="btn-send"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById('chat-messages');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');

        function addBubble(text, sender) {
            const div = document.createElement('div');
            div.classList.add('msg', sender);
            // Format Bold & Newline
            let formatted = text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
            div.innerHTML = formatted;
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function showLoading() {
            const div = document.createElement('div');
            div.id = 'loading-bubble';
            div.className = 'typing';
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
                // Fetch ke chat_brain.php (Satu folder)
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
                    addBubble("Maaf, sistem sedang sibuk.", 'ai');
                }

            } catch (error) {
                removeLoading();
                addBubble("Gagal koneksi.", 'ai');
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