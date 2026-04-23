<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Panel</h1>
            <div class="stats">
                <div class="stat-card">Current Server Time: <span id="server-time">2026-04-23 18:12:21</span></div>
                <div class="stat-card">Total Endpoints Count: <span id="endpoints-count">8</span></div>
                <div class="stat-card">Authentication Methods Count: <span id="auth-methods-count">2</span></div>
                <div class="stat-card">API Version: <span id="api-version">1.0.0</span></div>
            </div>
        </header>
        <main>
            <section class="endpoints">
                <h2>API Endpoints</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Description</th>
                            <th>Test</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>/login</td>
                            <td><span class="badge get">POST</span></td>
                            <td>User login</td>
                            <td><button class="test-btn" data-endpoint="/login">Test</button></td>
                        </tr>
                        <tr>
                            <td>/signup</td>
                            <td><span class="badge get">POST</span></td>
                            <td>User signup</td>
                            <td><button class="test-btn" data-endpoint="/signup">Test</button></td>
                        </tr>
                        <tr>
                            <td>/google_login</td>
                            <td><span class="badge get">POST</span></td>
                            <td>Login via Google</td>
                            <td><button class="test-btn" data-endpoint="/google_login">Test</button></td>
                        </tr>
                        <tr>
                            <td>/logout</td>
                            <td><span class="badge get">POST</span></td>
                            <td>User logout</td>
                            <td><button class="test-btn" data-endpoint="/logout">Test</button></td>
                        </tr>
                        <tr>
                            <td>/update_profile</td>
                            <td><span class="badge get">POST</span></td>
                            <td>Update user profile</td>
                            <td><button class="test-btn" data-endpoint="/update_profile">Test</button></td>
                        </tr>
                        <tr>
                            <td>/get_app_settings</td>
                            <td><span class="badge get">GET</span></td>
                            <td>Retrieve app settings</td>
                            <td><button class="test-btn" data-endpoint="/get_app_settings">Test</button></td>
                        </tr>
                        <tr>
                            <td>/setup_database</td>
                            <td><span class="badge get">POST</span></td>
                            <td>Setup database</td>
                            <td><button class="test-btn" data-endpoint="/setup_database">Test</button></td>
                        </tr>
                        <tr>
                            <td>/connection</td>
                            <td><span class="badge get">GET</span></td>
                            <td>Test database connection</td>
                            <td><button class="test-btn" data-endpoint="/connection">Test</button></td>
                        </tr>
                    </tbody>
                </table>
                <div class="endpoint-cards">
                    <!-- Individual endpoint cards will be dynamically inserted here -->
                </div>
            </section>
        </main>
    </div>
</body>
</html>

<style>
body {
    background: linear-gradient(#74ebd5, #9face6);
    font-family: Arial, sans-serif;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
header {
    text-align: center;
}
.stats {
    display: flex;
    justify-content: space-around;
    margin: 20px 0;
}
.stat-card {
    background: white;
    border-radius: 5px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
table th, table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}
.badge {
    padding: 2px 5px;
    border-radius: 3px;
}
.badge.get {
    background-color: green;
    color: white;
}
.badge.post {
    background-color: blue;
    color: white;
}
.test-btn {
    padding: 5px 10px;
    border: none;
    background-color: #007BFF;
    color: white;
    border-radius: 4px;
    cursor: pointer;
}
.test-btn:hover {
    background-color: #0056b3;
}
</style>
