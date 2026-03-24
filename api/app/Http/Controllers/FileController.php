<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    private function getNextFileNumber($directory)
    {
        $files = Storage::disk('public')->files($directory);
        $maxNumber = 0;

        foreach ($files as $file) {
            $filename = basename($file);
            //extract the number from the beginning of the filename (e.g., 00136 from "00136_eumvPxpSTW.jpg")
            if (preg_match('/^(\d{5})_/', $filename, $matches)) {
                $number = (int)$matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return $maxNumber + 1;
    }

    private function generateNumberedFilename($directory, $extension)
    {
        $number = $this->getNextFileNumber($directory);
        $randomString = Str::random(10);
        return sprintf('%05d_%s.%s', $number, $randomString, $extension);
    }

    public function uploadUserPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $file = $request->file('photo');
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateNumberedFilename('photos_avatars', $extension);

        $path = $file->storeAs('photos_avatars', $filename, 'public');

        return response()->json([
            'photo_url' => '/storage/' . $path,
        ]);
    }

    public function uploadCardFaces(Request $request)
    {
        $request->validate([
            'cardfaces.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $uploadedFiles = [];

        $files = is_array($request->file('cardfaces'))
            ? $request->file('cardfaces')
            : [$request->file('cardfaces')];

        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            $filename = $this->generateNumberedFilename('cardfaces', $extension);

            $path = $file->storeAs('cardfaces', $filename, 'public');
            $uploadedFiles[] = [
                'cardface_url' => '/storage/' . $path,
            ];
        }

        return response()->json([
            'files' => $uploadedFiles,
        ], 200);
    }
}
