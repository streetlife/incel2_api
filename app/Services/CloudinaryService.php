<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use PhpParser\Node\Scalar\String_;

class CloudinaryService
{
    protected string $key;
    protected string $secret;
    protected String $url;
    protected Cloudinary $cloudinary;
    public function __construct()
    {

        $this->key = config('cloudinary.api_key');
        $this->secret = config('cloudinary.api_secret');
        $this->url = config('cloudinary.url');
        $this->cloudinary = new Cloudinary($this->url);
    }
    public function upload($file, $folder = 'uploads')
    {
        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder' => $folder,
            ]
        );

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }
    public function uploadVideo($file, $folder = 'videos')
    {
        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'resource_type' => 'video',
                'folder' => $folder,
                'timeout' => 300,
            ]
        );

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }
    public function delete($publicId)
    {
        return $this->cloudinary->uploadApi()->destroy($publicId);
    }
}
