<?php
$title = 'API Documentation - Razor Library';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        .api-docs { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .api-docs h1 { margin-bottom: 0.5rem; }
        .api-docs .subtitle { color: var(--color-text-muted); margin-bottom: 2rem; }
        .api-section { margin-bottom: 3rem; }
        .api-section h2 { border-bottom: 2px solid var(--color-border); padding-bottom: 0.5rem; margin-bottom: 1rem; }
        .api-section h3 { margin-top: 1.5rem; margin-bottom: 0.5rem; }
        .endpoint { background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1rem; }
        .endpoint-method { display: inline-block; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.75rem; margin-right: 0.5rem; }
        .endpoint-method.get { background: #e3f2fd; color: #1565c0; }
        .endpoint-method.post { background: #e8f5e9; color: #2e7d32; }
        .endpoint-method.put { background: #fff3e0; color: #ef6c00; }
        .endpoint-method.delete { background: #ffebee; color: #c62828; }
        .endpoint-path { font-family: monospace; font-size: 0.9rem; }
        .endpoint-desc { margin-top: 0.5rem; color: var(--color-text-muted); }
        pre { background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem; overflow-x: auto; font-size: 0.85rem; }
        code { font-family: 'SF Mono', Monaco, 'Courier New', monospace; }
        .param-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .param-table th, .param-table td { text-align: left; padding: 0.5rem; border-bottom: 1px solid var(--color-border); }
        .param-table th { background: var(--color-bg); font-weight: 600; }
        .param-name { font-family: monospace; }
        .param-required { color: #c62828; font-size: 0.75rem; }
        .note { background: #fff3e0; border-left: 4px solid #ef6c00; padding: 1rem; margin: 1rem 0; border-radius: 0 var(--radius-md) var(--radius-md) 0; }
        .back-link { margin-bottom: 2rem; display: inline-block; }
    </style>
</head>
<body>
    <div class="api-docs">
        <a href="<?= url('/') ?>" class="back-link">&larr; Back to Razor Library</a>

        <h1>Razor Library API</h1>
        <p class="subtitle">REST API for programmatic access to your shaving collection</p>

        <div class="api-section">
            <h2>Authentication</h2>
            <p>All API requests require authentication using an API key. Include your key in the <code>Authorization</code> header:</p>
            <pre>Authorization: Bearer rl_your_api_key_here</pre>

            <h3>Generating an API Key</h3>
            <ol>
                <li>Log in to your Razor Library account</li>
                <li>Go to <strong>Profile</strong> &rarr; <strong>API Keys</strong></li>
                <li>Click <strong>Generate New Key</strong></li>
                <li>Give your key a descriptive name</li>
                <li>Copy the key immediately &mdash; it won't be shown again</li>
            </ol>

            <div class="note">
                <strong>Note:</strong> API access requires an active subscription. Requests from expired accounts will receive a <code>403 SUBSCRIPTION_REQUIRED</code> error.
            </div>
        </div>

        <div class="api-section">
            <h2>Rate Limiting</h2>
            <p>API requests are limited to <strong>100 requests per minute</strong> per API key. If you exceed this limit, you'll receive a <code>429 Too Many Requests</code> response.</p>
        </div>

        <div class="api-section">
            <h2>Response Format</h2>
            <p>All responses are JSON. Successful responses have this structure:</p>
            <pre>{
  "success": true,
  "data": { ... }
}</pre>

            <p>Error responses have this structure:</p>
            <pre>{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}</pre>

            <h3>Error Codes</h3>
            <table class="param-table">
                <tr><th>Code</th><th>HTTP Status</th><th>Description</th></tr>
                <tr><td><code>UNAUTHORIZED</code></td><td>401</td><td>Invalid or missing API key</td></tr>
                <tr><td><code>SUBSCRIPTION_REQUIRED</code></td><td>403</td><td>Subscription expired</td></tr>
                <tr><td><code>NOT_FOUND</code></td><td>404</td><td>Resource not found</td></tr>
                <tr><td><code>VALIDATION_ERROR</code></td><td>400</td><td>Invalid input data</td></tr>
                <tr><td><code>RATE_LIMITED</code></td><td>429</td><td>Too many requests</td></tr>
            </table>
        </div>

        <div class="api-section">
            <h2>Endpoints</h2>

            <h3>Razors</h3>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/razors</span>
                <p class="endpoint-desc">List all your razors</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/razors/{id}</span>
                <p class="endpoint-desc">Get a single razor by ID</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method post">POST</span>
                <span class="endpoint-path">/api/razors</span>
                <p class="endpoint-desc">Create a new razor</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method put">PUT</span>
                <span class="endpoint-path">/api/razors/{id}</span>
                <p class="endpoint-desc">Update an existing razor</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method delete">DELETE</span>
                <span class="endpoint-path">/api/razors/{id}</span>
                <p class="endpoint-desc">Delete a razor (soft delete)</p>
            </div>

            <h4>Razor Fields</h4>
            <table class="param-table">
                <tr><th>Field</th><th>Type</th><th>Description</th></tr>
                <tr><td class="param-name">name</td><td>string <span class="param-required">required</span></td><td>Razor name/model</td></tr>
                <tr><td class="param-name">brand</td><td>string</td><td>Manufacturer brand</td></tr>
                <tr><td class="param-name">year_manufactured</td><td>integer</td><td>Year of manufacture</td></tr>
                <tr><td class="param-name">country_manufactured</td><td>string</td><td>Country of manufacture</td></tr>
                <tr><td class="param-name">description</td><td>string</td><td>Detailed description</td></tr>
                <tr><td class="param-name">notes</td><td>string</td><td>Personal notes</td></tr>
            </table>

            <h3>Blades</h3>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/blades</span>
                <p class="endpoint-desc">List all your blades</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/blades/{id}</span>
                <p class="endpoint-desc">Get a single blade by ID</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method post">POST</span>
                <span class="endpoint-path">/api/blades</span>
                <p class="endpoint-desc">Create a new blade</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method put">PUT</span>
                <span class="endpoint-path">/api/blades/{id}</span>
                <p class="endpoint-desc">Update an existing blade</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method delete">DELETE</span>
                <span class="endpoint-path">/api/blades/{id}</span>
                <p class="endpoint-desc">Delete a blade (soft delete)</p>
            </div>

            <h4>Blade Fields</h4>
            <table class="param-table">
                <tr><th>Field</th><th>Type</th><th>Description</th></tr>
                <tr><td class="param-name">name</td><td>string <span class="param-required">required</span></td><td>Blade name/model</td></tr>
                <tr><td class="param-name">brand</td><td>string</td><td>Manufacturer brand</td></tr>
                <tr><td class="param-name">country_manufactured</td><td>string</td><td>Country of manufacture</td></tr>
                <tr><td class="param-name">description</td><td>string</td><td>Detailed description</td></tr>
                <tr><td class="param-name">notes</td><td>string</td><td>Personal notes</td></tr>
            </table>

            <h3>Brushes</h3>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/brushes</span>
                <p class="endpoint-desc">List all your brushes</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/brushes/{id}</span>
                <p class="endpoint-desc">Get a single brush by ID</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method post">POST</span>
                <span class="endpoint-path">/api/brushes</span>
                <p class="endpoint-desc">Create a new brush</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method put">PUT</span>
                <span class="endpoint-path">/api/brushes/{id}</span>
                <p class="endpoint-desc">Update an existing brush</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method delete">DELETE</span>
                <span class="endpoint-path">/api/brushes/{id}</span>
                <p class="endpoint-desc">Delete a brush (soft delete)</p>
            </div>

            <h4>Brush Fields</h4>
            <table class="param-table">
                <tr><th>Field</th><th>Type</th><th>Description</th></tr>
                <tr><td class="param-name">name</td><td>string <span class="param-required">required</span></td><td>Brush name/model</td></tr>
                <tr><td class="param-name">brand</td><td>string</td><td>Manufacturer brand</td></tr>
                <tr><td class="param-name">bristle_type</td><td>string</td><td>Type of bristles (badger, boar, synthetic, etc.)</td></tr>
                <tr><td class="param-name">handle_material</td><td>string</td><td>Handle material</td></tr>
                <tr><td class="param-name">knot_size</td><td>string</td><td>Knot size (e.g., "24mm")</td></tr>
                <tr><td class="param-name">description</td><td>string</td><td>Detailed description</td></tr>
                <tr><td class="param-name">notes</td><td>string</td><td>Personal notes</td></tr>
            </table>

            <h3>Other Items</h3>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/other</span>
                <p class="endpoint-desc">List all your other items</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method get">GET</span>
                <span class="endpoint-path">/api/other/{id}</span>
                <p class="endpoint-desc">Get a single other item by ID</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method post">POST</span>
                <span class="endpoint-path">/api/other</span>
                <p class="endpoint-desc">Create a new other item</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method put">PUT</span>
                <span class="endpoint-path">/api/other/{id}</span>
                <p class="endpoint-desc">Update an existing other item</p>
            </div>

            <div class="endpoint">
                <span class="endpoint-method delete">DELETE</span>
                <span class="endpoint-path">/api/other/{id}</span>
                <p class="endpoint-desc">Delete an other item (soft delete)</p>
            </div>

            <h4>Other Item Fields</h4>
            <table class="param-table">
                <tr><th>Field</th><th>Type</th><th>Description</th></tr>
                <tr><td class="param-name">name</td><td>string <span class="param-required">required</span></td><td>Item name</td></tr>
                <tr><td class="param-name">category</td><td>string</td><td>Category (soap, aftershave, etc.)</td></tr>
                <tr><td class="param-name">brand</td><td>string</td><td>Manufacturer brand</td></tr>
                <tr><td class="param-name">description</td><td>string</td><td>Detailed description</td></tr>
                <tr><td class="param-name">notes</td><td>string</td><td>Personal notes</td></tr>
            </table>
        </div>

        <div class="api-section">
            <h2>Examples</h2>

            <h3>List All Razors</h3>
            <pre>curl -X GET "https://your-domain.com/api/razors" \
  -H "Authorization: Bearer rl_your_api_key"</pre>

            <h3>Create a Razor</h3>
            <pre>curl -X POST "https://your-domain.com/api/razors" \
  -H "Authorization: Bearer rl_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Slim Adjustable",
    "brand": "Gillette",
    "year_manufactured": 1965,
    "country_manufactured": "USA",
    "description": "Classic adjustable safety razor"
  }'</pre>

            <h3>Update a Razor</h3>
            <pre>curl -X PUT "https://your-domain.com/api/razors/123" \
  -H "Authorization: Bearer rl_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "My daily driver"
  }'</pre>

            <h3>Delete a Razor</h3>
            <pre>curl -X DELETE "https://your-domain.com/api/razors/123" \
  -H "Authorization: Bearer rl_your_api_key"</pre>
        </div>

        <div class="api-section">
            <h2>Image Access</h2>
            <p>API responses include image URLs that can be accessed directly. Each item may include:</p>
            <ul>
                <li><code>hero_image</code> - The main/featured image filename</li>
                <li><code>hero_image_url</code> - Full URL to the main image</li>
                <li><code>images</code> - Array of additional images with URLs and thumbnails</li>
            </ul>

            <div class="note">
                <strong>Note:</strong> Image upload is not currently supported via the API. Use the web interface to manage images.
            </div>
        </div>

        <p style="margin-top: 3rem; color: var(--color-text-muted); text-align: center;">
            Razor Library API v1
        </p>
    </div>
</body>
</html>
