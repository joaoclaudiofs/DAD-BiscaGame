<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Customization extends Model
{
    protected $table = 'customizations';

    protected $fillable = [
        'type',
        'name',
        'price',
        'image_filename',
        'custom',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $filename = $this->image_filename;
        if (!$filename) {
            return null;
        }
        // Resolve a public URL for files stored on the 'public' disk.
        // If image_filename contains subfolders, it will be preserved.
        return Storage::disk('public')->url($filename);
    }
}
