<?php

declare(strict_types=1);

namespace App\Services;

final class ImageProcessor
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const MAX_SOURCE_DIMENSION = 10000;
    public const MAX_LONG_EDGE = 2200;
    public const WEBP_QUALITY = 82;
    private const MIME_TYPES = ['image/jpeg','image/png','image/webp'];

    public function process(array $upload, string $destination): array
    {
        if (!class_exists('finfo') || !extension_loaded('gd') || !function_exists('imagewebp')) throw new ImageProcessingException('Image processing is unavailable on this server.');
        $error=$upload['error']??UPLOAD_ERR_NO_FILE;
        if (!is_int($error) || $error!==UPLOAD_ERR_OK) throw new ImageProcessingException($this->uploadError($error));
        $size=$upload['size']??0; $tmp=$upload['tmp_name']??'';
        if (!is_int($size) || $size<1 || $size>self::MAX_BYTES) throw new ImageProcessingException('Image must be no larger than 10 MB.');
        if (!is_string($tmp) || $tmp==='' || !is_file($tmp)) throw new ImageProcessingException('Uploaded image could not be read.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!is_string($mime) || !in_array($mime,self::MIME_TYPES,true)) throw new ImageProcessingException('Only JPEG, PNG, and WebP images are allowed.');
        $info=@getimagesize($tmp);
        if (!is_array($info) || ($info[0]??0)<1 || ($info[1]??0)<1) throw new ImageProcessingException('Image is malformed or cannot be decoded.');
        $width=(int)$info[0]; $height=(int)$info[1];
        if ($width>self::MAX_SOURCE_DIMENSION || $height>self::MAX_SOURCE_DIMENSION) throw new ImageProcessingException('Image dimensions must not exceed 10,000 pixels per side.');
        $bytes=@file_get_contents($tmp);
        $source=is_string($bytes)?@imagecreatefromstring($bytes):false;
        if (!$source instanceof \GdImage) throw new ImageProcessingException('Image is malformed or cannot be decoded.');
        try {
            if ($mime==='image/jpeg') $source=$this->orientJpeg($source,$tmp);
            $width=imagesx($source); $height=imagesy($source); $scale=min(1,self::MAX_LONG_EDGE/max($width,$height));
            $targetWidth=max(1,(int)round($width*$scale)); $targetHeight=max(1,(int)round($height*$scale));
            $target=imagecreatetruecolor($targetWidth,$targetHeight);
            if (!$target instanceof \GdImage) throw new ImageProcessingException('Image could not be processed.');
            imagealphablending($target,false); imagesavealpha($target,true); $transparent=imagecolorallocatealpha($target,0,0,0,127); imagefilledrectangle($target,0,0,$targetWidth,$targetHeight,$transparent);
            if (!imagecopyresampled($target,$source,0,0,0,0,$targetWidth,$targetHeight,$width,$height) || !imagewebp($target,$destination,self::WEBP_QUALITY)) { imagedestroy($target); throw new ImageProcessingException('Optimized image could not be stored.'); }
            imagedestroy($target);
            return ['width'=>$targetWidth,'height'=>$targetHeight];
        } finally { imagedestroy($source); }
    }

    private function orientJpeg(\GdImage $image,string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) return $image;
        $exif=@exif_read_data($path,'IFD0',true); $orientation=(int)($exif['IFD0']['Orientation']??1); $rotated=false;
        if ($orientation===3) $rotated=imagerotate($image,180,0);
        elseif ($orientation===6) $rotated=imagerotate($image,-90,0);
        elseif ($orientation===8) $rotated=imagerotate($image,90,0);
        if ($rotated instanceof \GdImage) { imagedestroy($image); return $rotated; }
        return $image;
    }

    private function uploadError(mixed $error): string
    {
        return $error===UPLOAD_ERR_INI_SIZE || $error===UPLOAD_ERR_FORM_SIZE ? 'Image exceeds the server upload limit.' : ($error===UPLOAD_ERR_NO_FILE ? 'Choose at least one image.' : 'Image upload failed. Please try again.');
    }
}
