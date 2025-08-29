<?php namespace Tobuli\Entities;

use Eloquent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class DeviceType extends Eloquent
{
    protected $table = 'device_types';

    protected $fillable = [
        'active',
        'title',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function hasImage()
    {
        return $this->path ? true : false;
    }

    public function getImageUrl()
    {
        return asset($this->path);
    }

    public function saveImage(UploadedFile $image)
    {
        if ($this->path)
            $this->deleteImage();

        $extension = strtolower($image->getClientOriginalExtension());
        $name      = str_random();
        $path      = 'images/deviceTypes/';
        $dir       = public_path($path);

        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $image->move($dir, $name . '.' . $extension);

        $this->path = $path . $name . '.' . $extension;
        $this->save();
    }

    public function deleteImage()
    {
        File::delete(public_path($this->path));
    }
}
