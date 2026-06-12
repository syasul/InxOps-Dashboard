<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileExplorerController extends Controller
{
    protected $basePath;
    public function __construct()
    {
        // Dynamically resolve Home directory (compatible with Mac and Linux)
        $home = env('HOME', $_SERVER['HOME'] ?? '/home/inxdvi');
        $defaultPath = $home . '/webapps/InxOps-Storage';
        
        $this->basePath = env('STORAGE_EXPLORER_PATH', $defaultPath);
        
        try {
            if (!File::exists($this->basePath)) {
                File::makeDirectory($this->basePath, 0775, true);
            }
            $this->basePath = realpath($this->basePath);
        } catch (\Exception $e) {
            // Ultimate fallback to internal storage
            $this->basePath = storage_path('app/explorer');
            if (!File::exists($this->basePath)) {
                File::makeDirectory($this->basePath, 0775, true);
            }
            $this->basePath = realpath($this->basePath);
        }
    }

    public function index(Request $request)
    {
        $path = $request->query('path', '');
        $fullPath = realpath($this->basePath . DIRECTORY_SEPARATOR . $path);

        // Security check: Don't allow going above base path
        if (!$fullPath || !str_starts_with($fullPath, $this->basePath)) {
            $path = '';
            $fullPath = $this->basePath;
        }

        $items = [];
        $files = File::files($fullPath);
        $directories = File::directories($fullPath);

        foreach ($directories as $dir) {
            $items[] = [
                'name' => basename($dir),
                'type' => 'directory',
                'size' => '-',
                'modified' => date('Y-m-d H:i', File::lastModified($dir)),
                'path' => ltrim($path . '/' . basename($dir), '/')
            ];
        }

        foreach ($files as $file) {
            $items[] = [
                'name' => $file->getFilename(),
                'type' => 'file',
                'extension' => $file->getExtension(),
                'size' => $this->formatBytes($file->getSize()),
                'modified' => date('Y-m-d H:i', $file->getMTime()),
                'path' => ltrim($path . '/' . $file->getFilename(), '/')
            ];
        }

        $breadcrumbs = $this->getBreadcrumbs($path);

        return view('storage.index', compact('items', 'path', 'breadcrumbs'));
    }

    public function upload(Request $request)
    {
        $path = $request->input('path', '');
        $fullPath = $this->basePath . DIRECTORY_SEPARATOR . $path;

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $file->move($fullPath, $file->getClientOriginalName());
            }
        }

        return response()->json(['success' => true]);
    }

    public function download(Request $request)
    {
        $path = $request->query('path');
        $fullPath = realpath($this->basePath . DIRECTORY_SEPARATOR . $path);

        if ($fullPath && str_starts_with($fullPath, $this->basePath) && is_file($fullPath)) {
            return response()->download($fullPath);
        }

        return back()->with('error', 'File not found or access denied.');
    }

    public function createFolder(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $path = $request->input('path', '');
        $newFolderName = $request->input('name');
        
        $fullPath = $this->basePath . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $newFolderName;

        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
            return back()->with('success', "Folder '$newFolderName' created successfully.");
        }

        return back()->with('error', 'Folder already exists.');
    }

    public function destroy(Request $request)
    {
        $path = $request->input('path');
        $fullPath = realpath($this->basePath . DIRECTORY_SEPARATOR . $path);

        if ($fullPath && str_starts_with($fullPath, $this->basePath)) {
            if (is_dir($fullPath)) {
                File::deleteDirectory($fullPath);
            } else {
                File::delete($fullPath);
            }
            return back()->with('success', 'Item deleted successfully.');
        }

        return back()->with('error', 'Unauthorized or invalid path.');
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    protected function getBreadcrumbs($path)
    {
        $parts = array_filter(explode('/', $path));
        $breadcrumbs = [['name' => 'Root', 'path' => '']];
        $current = '';
        foreach ($parts as $part) {
            $current .= ($current ? '/' : '') . $part;
            $breadcrumbs[] = ['name' => $part, 'path' => $current];
        }
        return $breadcrumbs;
    }
}
