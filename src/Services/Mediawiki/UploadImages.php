<?php

namespace Mawdoo3\Waraqa\Services\Mediawiki;

use Mawdoo3\Waraqa\Models\ArticlePicture;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Illuminate\Support\Facades\Storage;

class UploadImages
{

    /**
     * @param $image_base64
     * @param $page
     * @return void
     * @throws ImageResizeException
     */
    public static function getImage($image_base64, $page)
    {
        if (empty($image_base64)) {
            return;
        }

        $ext = 'jpg';
        $imageContent = base64_decode($image_base64);
        list($time, $newFilename) = self::prepareFileToUpload($page->page_id, $ext, $imageContent);
        $imageSizes = Config('IMAGE_SIZES');
        $mainImageName = '/tmp/origins/' . $newFilename;

        foreach (json_decode($imageSizes) as $imageSize) {
            $imageTempName = '/tmp/origins/thumbs/fit' . $imageSize[0] . 'x' . $imageSize[1] . '_' . $newFilename;
            $image = new ImageResize($mainImageName);
            $image->resize($imageSize[0], $imageSize[1], true);
            $imageTempContain = $image->save($imageTempName);

            $path = 'thumbs/fit' . $imageSize[0] . 'x' . $imageSize[1] . '/' . $newFilename;

            Storage::disk('s3')->put($path, $imageTempContain, 'public');

            unlink($imageTempName);
        }

        unlink('/tmp/origins/' . $newFilename);
        self::updateArticleImageInfo($page->page_id, $page->page_title, $time, $newFilename, $ext);
    }

    /**
     * @param $article_id
     * @param $ext
     * @param $imageContent
     * @return array
     */
    private static function prepareFileToUpload($article_id, $ext, $imageContent)
    {
        $time = time();
        $newFilename = $article_id . '_' . $time . '.' . $ext;
        if (!file_exists('/tmp/origins/')) {
            mkdir('/tmp/origins/', 0777, true);
        }
        if (!file_exists('/tmp/origins/thumbs/')) {
            mkdir('/tmp/origins/thumbs/', 0777, true);
        }
        file_put_contents('/tmp/origins/' . $newFilename, $imageContent);
        return array($time, $newFilename);
    }

    /**
     * @param $articleId
     * @param $pageTitle
     * @param $time
     * @param $newFilename
     * @param $ext
     * @return mixed|string
     */
    private static function updateArticleImageInfo($articleId, $pageTitle, $time, $newFilename, $ext)
    {
        $isThumb = 1;
        $keywords = '';

        if (!empty($pageTitle)) {
            $keywords .= str_replace('_', ';', $pageTitle);
            $keywords .= ';' . str_replace('_', ' ', $pageTitle);
        }

        $imageIsApproved = 1;

        if (empty($pageTitle)) {
            $pageTitle = 'صورة_معرض';
        }

        $encodedTitle = urlencode($pageTitle);
        $fileObject = $articleId . '/' . $time . '/' . $encodedTitle . '.' . $ext;

        ArticlePicture::where('isthumb', 1)->where('article_id', $articleId)->update(['isthumb' => 0]);

        ArticlePicture::create([
            'user_id' => Config('WARAQA_USER_ID'),
            'article_id' => $articleId,
            'pic' => $newFilename,
            'isthumb' => $isThumb,
            'ismain' => 0,
            'isgallery' => 1,
            'has_fit_thumb' => 1,
            'isapproved' => $imageIsApproved,
            'keywords' => $keywords,
            'time' => $time,
            'cloud_object' => $fileObject,
            'waraqa_image' => 1
        ]);

        return $pageTitle;
    }

}
