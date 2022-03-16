<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Page extends Model
{
    protected $table = "page";

    protected $primaryKey = 'page_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'page_id',
        'page_namespace',
        'page_title',
        'page_restrictions',
        'page_is_redirect',
        'page_is_new',
        'page_random',
        'page_touched',
        'page_latest',
        'page_len',
        'page_content_model',
        'page_links_updated',
        'page_lang',
        'waraqa_article_id',
        'waraqa_users_id',
        'waraqa_proofreader_id',

    ];


    /**
     * @return HasOne
     */
    public function revision(): HasOne
    {
        return $this->hasOne(Revision::class, 'rev_page', 'rev_id');
    }


    public function pictures() : HasMany
    {
        return $this->hasMany(ArticlePicture::class,'article_id');
    }
    /**
     * @return HasMany
     */
    public function externalLinks(): HasMany
    {
        return $this->hasMany(ExternalLinks::class, 'el_from', 'page_id');
    }
}
