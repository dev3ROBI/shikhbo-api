<?php
// Database Console – Enhanced Terminal
?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Database Console</h1>
    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage database schema and run setup scripts</p>
</div>

<!-- Terminal Interface -->
<div class="bg-gray-900 dark:bg-gray-950 rounded-xl shadow-xl overflow-hidden border border-gray-800">
    <div class="bg-gray-800 dark:bg-gray-900 px-4 py-2 flex items-center space-x-2">
        <span class="w-3 h-3 bg-red-500 rounded-full"></span>
        <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
        <span class="text-gray-400 text-xs ml-2">shikhbo-db-console</span>
    </div>
    <div id="terminalBody" class="p-4 h-96 overflow-y-auto font-mono text-sm text-green-400 space-y-1">
        <div class="text-gray-500">Shikhbo DB Console [Version 2.0]</div>
        <div class="text-gray-500">(c) Shikhbo API. All rights reserved.</div>
        <div class="mt-2">C:\> <span id="dynamicLine" class="text-white"></span></div>
        <div class="mt-2 text-gray-500">========================================</div>
        <div id="outputLog"></div>
    </div>
    <div class="bg-gray-800 dark:bg-gray-900 px-4 py-3 border-t border-gray-700 flex flex-wrap items-center gap-3">
        <span class="text-green-400 font-mono text-sm">root@shikhbo:~$</span>
        <button id="runSetupBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-database"></i><span>Run Setup Database</span>
        </button>
        <button id="refreshStatusBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-arrows-rotate"></i><span>Check Status</span>
        </button>
        <button id="clearTerminalBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-eraser"></i><span>Clear</span>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const outputLog = document.getElementById('outputLog');
    const terminalBody = document.getElementById('terminalBody');
    const dynamicLine = document.getElementById('dynamicLine');
    const csrfToken = document.getElementById('csrf_token').value;

    function addLog(type, message) {
        const now = new Date().toLocaleTimeString();
        let colour = '#a0aec0';
        if (type === 'success') colour = '#48bb78';
        else if (type === 'error') colour = '#f56565';
        else if (type === 'warning') colour = '#ecc94b';
        else if (type === 'info') colour = '#4299e1';
        outputLog.innerHTML += `<div style="color:${colour}">[${now}] ${escapeHtml(message)}</div>`;
        terminalBody.scrollTop = terminalBody.scrollHeight;
    }

    function escapeHtml(text) {
        const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Typing animation for dynamic line
    const texts = [
        'Ready for database operations.',
        'Type a command or use buttons below.',
        'All systems nominal.',
        'Railway MySQL connected.',
        'Admin panel database activated.'
    ];
    let textIdx = 0;
    function typeText(text, i = 0) {
        if (i < text.length) {
            dynamicLine.innerHTML = text.substring(0, i+1) + '<span class="blinking-cursor">|</span>';
            setTimeout(() => typeText(text, i+1), 50);
        } else {
            dynamicLine.innerHTML = text;
            setTimeout(() => {
                textIdx = (textIdx + 1) % texts.length;
                dynamicLine.innerHTML = '';
                typeText(texts[textIdx], 0);
            }, 3000);
        }
    }
    typeText(texts[0], 0);

    // Blinking cursor
    const style = document.createElement('style');
    style.innerHTML = '@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} } .blinking-cursor{animation:blink 1s infinite;}';
    document.head.appendChild(style);

    // Run Setup Database
    document.getElementById('runSetupBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Running setup...</span>';
        addLog('info', 'Starting database setup...');
        fetch('/api/setup_database.php', {
            method: 'GET',
            headers: {'Accept': 'application/json'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                addLog('success', data.message);
                addLog('info', 'Database: ' + data.database);
                if (data.tables_created) {
                    addLog('info', 'Tables: ' + data.tables_created.join(', '));
                }
                if (data.default_admin) {
                    addLog('warning', 'Default admin: ' + data.default_admin);
                }
            } else {
                addLog('error', data.message || 'Unknown error');
            }
        })
        .catch(err => addLog('error', 'Failed to reach API: ' + err.message))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-database"></i><span>Run Setup Database</span>';
        });
    });

    // Refresh status
    document.getElementById('refreshStatusBtn').addEventListener('click', function() {
        addLog('info', 'Checking API status...');
        fetch('/api/connection.php?test=1')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    addLog('success', 'API connection OK. Database live.');
                } else {
                    addLog('error', 'API connection issue.');
                }
            })
            .catch(err => addLog('error', 'Connection test failed: ' + err.message));
    });

    // Clear terminal
    document.getElementById('clearTerminalBtn').addEventListener('click', function() {
        outputLog.innerHTML = '';
        addLog('info', 'Terminal cleared');
    });

    addLog('info', 'Console ready. Use buttons to execute commands.');
});
</script>