<?php
// Database Management Page (Terminal Style)
?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Database Console</h1>
    <p class="text-gray-500 mt-1">Manage database schema and run setup scripts</p>
</div>

<!-- Terminal Interface -->
<div class="bg-gray-900 rounded-xl shadow-xl overflow-hidden">
    <!-- Terminal Header -->
    <div class="bg-gray-800 px-4 py-2 flex items-center space-x-2">
        <span class="w-3 h-3 bg-red-500 rounded-full"></span>
        <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
        <span class="text-gray-400 text-xs ml-2">shikhbo-db-console</span>
    </div>
    
    <!-- Terminal Body -->
    <div id="terminalBody" class="p-4 h-96 overflow-y-auto font-mono text-sm text-green-400 space-y-1">
        <div class="text-gray-500">Shikhbo DB Console [Version 1.0]</div>
        <div class="text-gray-500">(c) Shikhbo API. All rights reserved.</div>
        <div class="mt-2">C:\> <span class="text-white">Ready for database operations.</span></div>
        <div class="mt-2 text-gray-500">========================================</div>
        <div id="outputLog">
            <!-- Dynamic output will appear here -->
        </div>
    </div>

    <!-- Terminal Input Area -->
    <div class="bg-gray-800 px-4 py-3 border-t border-gray-700 flex items-center flex-wrap gap-3">
        <span class="text-green-400 font-mono">root@shikhbo:~$</span>
        <button id="runSetupBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-database"></i>
            <span>Run Setup Database</span>
        </button>
        <button id="refreshStatusBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span>Check Status</span>
        </button>
        <button id="clearTerminalBtn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors flex items-center space-x-2">
            <i class="fa-solid fa-eraser"></i>
            <span>Clear</span>
        </button>
    </div>
</div>

<!-- Database Tables Info -->
<div class="bg-white rounded-xl shadow-md p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Database Schema Overview</h3>
    <div id="schemaInfo" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">users</span>
                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Active</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Students, Admins, Users</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">exam_categories</span>
                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">New</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">Multi-level categories</p>
        </div>
        <!-- Add more schema blocks as needed -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const outputLog = document.getElementById('outputLog');
    const terminalBody = document.getElementById('terminalBody');
    const csrfToken = document.getElementById('csrf_token').value;

    function addLog(type, message) {
        const now = new Date().toLocaleTimeString();
        let color = '#a0aec0';
        if (type === 'success') color = '#48bb78';
        else if (type === 'error') color = '#f56565';
        else if (type === 'warning') color = '#ecc94b';
        else if (type === 'info') color = '#4299e1';

        outputLog.innerHTML += `<div style="color: ${color}">[${now}] ${escapeHtml(message)}</div>`;
        terminalBody.scrollTop = terminalBody.scrollHeight;
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Run Setup Database
    document.getElementById('runSetupBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Running setup...</span>';
        addLog('info', 'Starting database setup...');

        fetch('/api/setup_database.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
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
        .catch(err => {
            addLog('error', 'Failed to reach API: ' + err.message);
        })
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

    // Initial message
    addLog('info', 'Console ready. Use buttons to execute commands.');
});
</script>