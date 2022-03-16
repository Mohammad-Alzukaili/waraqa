<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


class ArticlePicture extends Model
{
    protected $table = "article_pictures";

    protected $primaryKey = 'pic_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = ['pic_id', 'article_id', 'pic', 'keywords', 'isgallery', 'ismain', 'isthumb', 'isdeleted', 'time', 'cloud_url', 'cloud_object', 'cloud_container', 'isapproved', 'has_fit_thumb', 'user_id', 'rejection_reason', 'from_gallery', 'image_url', 'image_link', 'image_author_name', 'waraqa_image'];



    public function article(){
        return $this->belongsTo(Page::class,'page_id','article_id');
    }

}



