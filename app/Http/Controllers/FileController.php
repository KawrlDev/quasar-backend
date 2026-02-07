<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index()
    {
        $files = Storage::files('uploads');

        return collect($files)->map(function ($file) {
            return [
                'name' => basename($file),
                'url'  => url('/storage/' . $file),
                'size' => Storage::size($file),
                'type' => pathinfo($file, PATHINFO_EXTENSION),
            ];
        });
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $request->file('file')->store('uploads');

        return response()->json(['message' => 'File uploaded']);
    }

    public function destroy($filename)
    {
        Storage::delete('uploads/' . $filename);
        return response()->json(['message' => 'File deleted']);
    }
}
