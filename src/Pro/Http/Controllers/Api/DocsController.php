<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class DocsController extends Controller
{
    /**
     * Serve the API documentation page.
     */
    public function index(): Response
    {
        $html = $this->getSwaggerHtml();
        
        return response($html, 200)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Serve the OpenAPI specification.
     */
    public function spec(): Response
    {
        // Navigate from src/Pro/Http/Controllers/Api to docs/
        $specPath = dirname(__DIR__, 5) . '/docs/openapi.yaml';
        
        if (!file_exists($specPath)) {
            abort(404, 'OpenAPI specification not found at: ' . $specPath);
        }
        
        return response(file_get_contents($specPath), 200)
            ->header('Content-Type', 'application/x-yaml')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Get the Swagger UI HTML.
     */
    protected function getSwaggerHtml(): string
    {
        $specUrl = url('/api/commissions/docs/openapi.yaml');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Sales Commission Pro - API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        
        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }
        
        .swagger-ui .topbar {
            display: none;
        }
        
        .custom-header {
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .custom-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .custom-header .badge {
            background: #6366f1;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        
        .custom-header a {
            color: #a5b4fc;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .custom-header a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="custom-header">
        <div style="display: flex; align-items: center;">
            <h1>📊 Laravel Sales Commission</h1>
            <span class="badge">Pro API</span>
        </div>
        <div>
            <a href="https://github.com/ayangzy/laravel-sales-commission" target="_blank">GitHub</a>
        </div>
    </div>
    
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: "{$specUrl}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: 'list',
                filter: true,
                showExtensions: true,
                showCommonExtensions: true
            });
        };
    </script>
</body>
</html>
HTML;
    }
}
