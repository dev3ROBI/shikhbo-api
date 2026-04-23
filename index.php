<?php
// Admin Panel Interface for API Endpoints

// Array of API Endpoints
$api_endpoints = [
    [
        'endpoint' => '/api/users',
        'description' => 'Fetches all users',
        'method' => 'GET',
        'parameters' => 'None',
        'test_functionality' => 'Click Here',
    ],
    [
        'endpoint' => '/api/users/{id}',
        'description' => 'Fetch user by ID',
        'method' => 'GET',
        'parameters' => '{id} - User ID',
        'test_functionality' => 'Click Here',
    ],
    [
        'endpoint' => '/api/users',
        'description' => 'Create a new user',
        'method' => 'POST',
        'parameters' => 'name, email, password',
        'test_functionality' => 'Click Here',
    ],
    // Add more endpoints as necessary
];

// Function to display the admin panel
function display_admin_panel($endpoints) {
    echo '<h1>Admin Panel - API Endpoints</h1>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Endpoint</th><th>Description</th><th>Method</th><th>Parameters</th><th>Testing</th></tr>';
    foreach ($endpoints as $endpoint) {
        echo '<tr>';
        echo '<td>' . $endpoint['endpoint'] . '</td>';
        echo '<td>' . $endpoint['description'] . '</td>';
        echo '<td>' . $endpoint['method'] . '</td>';
        echo '<td>' . $endpoint['parameters'] . '</td>';
        echo '<td><a href="#">' . $endpoint['test_functionality'] . '</a></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Display the admin panel
display_admin_panel($api_endpoints);