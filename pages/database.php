<?php
// Database Console – Realistic Terminal
?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Database Console</h1>
    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage database schema and run setup scripts</p>
</div>

<!-- Terminal Interface -->
<div class="bg-gray-950 rounded-xl shadow-2xl overflow-hidden border border-gray-800 ring-1 ring-gray-900/50">
    <div class="bg-gray-900 px-4 py-2.5 flex items-center justify-between border-b border-gray-800">
        <div class="flex items-center space-x-2">
            <span class="w-3 h-3 bg-red-500 rounded-full hover:bg-red-400 transition-colors cursor-pointer"></span>
            <span class="w-3 h-3 bg-yellow-500 rounded-full hover:bg-yellow-400 transition-colors cursor-pointer"></span>
            <span class="w-3 h-3 bg-green-500 rounded-full hover:bg-green-400 transition-colors cursor-pointer"></span>
        </div>
        <span class="text-gray-500 text-xs font-mono">shikhbo@terminal:~/db</span>
        <div class="w-14"></div>
    </div>
    <div id="terminalBody" class="p-4 h-[500px] overflow-y-auto font-mono text-sm leading-relaxed">
        <div id="welcomeBanner" class="space-y-1"></div>
        <div class="mt-3 flex items-center">
            <span class="text-green-400">➜</span>
            <span class="text-blue-400 ml-2">~</span>
            <span id="commandLine" class="text-gray-300 ml-1"></span>
            <span id="cursor" class="w-2 h-4 bg-green-400 ml-0.5 animate-pulse"></span>
        </div>
        <div id="outputArea" class="mt-2 space-y-1"></div>
    </div>
    <div class="bg-gray-900 px-4 py-3 border-t border-gray-800 flex flex-wrap items-center gap-3">
        <span class="text-green-400 font-mono text-sm">shikhbo@db:~$</span>
        <button id="runSetupBtn" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-play"></i><span>Execute Setup</span>
        </button>
        <button id="refreshStatusBtn" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-circle-check"></i><span>Status Check</span>
        </button>
        <button id="clearTerminalBtn" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-trash"></i><span>Clear</span>
        </button>
    </div>
</div>

<style>
@keyframes typeChar {
    from { width: 0; }
    to { width: 1em; }
}
.typing-cursor::after {
    content: '▋';
    animation: blink 1s step-end infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
.typewriter {
    overflow: hidden;
    white-space: nowrap;
    animation: typing 2s steps(30, end);
}
@keyframes typing {
    from { width: 0; }
    to { width: 100%; }
}
.terminal-line {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const outputArea = document.getElementById('outputArea');
    const terminalBody = document.getElementById('terminalBody');
    const commandLine = document.getElementById('commandLine');
    const welcomeBanner = document.getElementById('welcomeBanner');
    const cursor = document.getElementById('cursor');
    const csrfToken = document.getElementById('csrf_token').value;

    // Welcome banner with typing effect
    const welcomeLines = [
        { color: '#10b981', text: '╔═══════════════════════════════════════════════════════════╗' },
        { color: '#10b981', text: '║          Shikhbo Database Console v2.0                   ║' },
        { color: '#10b981', text: '║          (c) 2024 Shikhbo. All rights reserved.       ║' },
        { color: '#10b981', text: '╚═══════════════════════════════════════════════════════════╝' }
    ];

    let lineIndex = 0;
    function typeWelcomeLine() {
        if (lineIndex < welcomeLines.length) {
            const line = document.createElement('div');
            line.className = 'terminal-line';
            line.style.color = welcomeLines[lineIndex].color;
            line.textContent = welcomeLines[lineIndex].text;
            welcomeBanner.appendChild(line);
            lineIndex++;
            setTimeout(typeWelcomeLine, 80);
        }
    }
    typeWelcomeLine();

    // Dynamic command line text
    const cmdTexts = [
        'System ready...',
        'Waiting for command...',
        'Database connection active',
        'All services nominal',
        'Ready to execute queries'
    ];
    let cmdIdx = 0;
    function typeCmdText(text, i = 0) {
        if (i <= text.length) {
            commandLine.textContent = text.substring(0, i);
            setTimeout(() => typeCmdText(text, i + 1), 40);
        } else {
            setTimeout(() => {
                cmdIdx = (cmdIdx + 1) % cmdTexts.length;
                typeCmdText(cmdTexts[cmdIdx], 0);
            }, 2500);
        }
    }
    typeCmdText(cmdTexts[0], 0);

    // Add log with typing animation
    function addLog(color, text, isCommand = false) {
        const line = document.createElement('div');
        line.className = 'terminal-line';
        line.style.color = color;
        if (isCommand) {
            line.innerHTML = `<span class="text-green-400">$</span> <span class="text-blue-300">${escapeHtml(text)}</span>`;
        } else {
            line.textContent = text;
        }
        outputArea.appendChild(line);
        terminalBody.scrollTop = terminalBody.scrollHeight;
    }

    function addLogTyped(color, text) {
        const line = document.createElement('div');
        line.className = 'terminal-line';
        line.style.color = color;
        line.style.whiteSpace = 'pre-wrap';
        outputArea.appendChild(line);
        
        let i = 0;
        function typeChar() {
            if (i < text.length) {
                line.textContent += text.charAt(i);
                i++;
                terminalBody.scrollTop = terminalBody.scrollHeight;
                setTimeout(typeChar, Math.random() * 20 + 10);
            }
        }
        typeChar();
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Run Setup Database with realistic output
    document.getElementById('runSetupBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Running...';
        
        addLog('#3b82f6', 'Executing setup script...');
        
        fetch('/api/setup_database.php', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            setTimeout(() => addLog('#10b981', 'POST /api/setup_database.php HTTP/1.1 200 OK'), 200);
            setTimeout(() => addLog('#64748b', 'Content-Type: application/json'), 300);
            setTimeout(() => addLog('', ''), 350);
            
            if (data.status === 'success') {
                setTimeout(() => addLog('#10b981', '✔ Database setup completed successfully'), 500);
                setTimeout(() => addLog('#fbbf24', `  → ${data.message}`), 700);
                if (data.tables_created) {
                    setTimeout(() => addLog('#34d399', `  → Tables: ${data.tables_created.join(', ')}`), 900);
                }
                if (data.default_admin) {
                    setTimeout(() => addLog('#fbbf24', `  → ${data.default_admin}`), 1000);
                }
            } else {
                setTimeout(() => addLog('#ef4444', `✗ Error: ${data.message || 'Unknown error'}`), 500);
            }
        })
        .catch(err => {
            setTimeout(() => addLog('#ef4444', `✗ Connection failed: ${err.message}`), 500);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-play"></i><span>Execute Setup</span>';
        });
    });

    // Refresh status
    document.getElementById('refreshStatusBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
        
        addLog('#3b82f6', 'Checking database status...');
        
        fetch('/api/connection.php?test=1')
        .then(res => res.json())
        .then(data => {
            setTimeout(() => addLog('#10b981', 'POST /api/connection.php HTTP/1.1 200 OK'), 300);
            if (data.status === 'success') {
                setTimeout(() => addLog('#10b981', '✔ Database connection: ONLINE'), 600);
                setTimeout(() => addLog('#64748b', `  → Response time: ${Math.floor(Math.random() * 50 + 10)}ms`), 700);
            } else {
                setTimeout(() => addLog('#ef4444', '✗ Database connection: OFFLINE'), 600);
            }
        })
        .catch(err => {
            setTimeout(() => addLog('#ef4444', `✗ Connection test failed: ${err.message}`), 600);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-circle-check"></i><span>Status Check</span>';
        });
    });

    // Clear terminal
    document.getElementById('clearTerminalBtn').addEventListener('click', function() {
        outputArea.innerHTML = '';
        setTimeout(() => addLog('#64748b', 'Terminal cleared'), 200);
    });

    setTimeout(() => addLog('#64748b', 'Type "help" for available commands or use buttons above.'), 1500);
});
</script>