<?php

namespace Dinithoshan\Racelab\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

class AssetController extends Controller
{
    /**
     * Serve assets from package dist folder
     */
    public function serve(string $file)
    {
        $distPath = __DIR__ . '/../../../dist/' . basename($file);

        if (!File::exists($distPath)) {
            abort(404, "Asset not found: {$file}");
        }

        $content = File::get($distPath);
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        // Determine content type based on extension
        $contentTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
        ];
        
        $contentType = $contentTypes[$extension] ?? File::mimeType($distPath) ?? 'application/octet-stream';

        return Response::make($content, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
        ]);
    }
}
