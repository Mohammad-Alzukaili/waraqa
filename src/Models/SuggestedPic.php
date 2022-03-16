<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class SuggestedPic extends Model
{
    protected $table = "suggested_pics";

    protected $primaryKey = 'sa_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'sa_id', 'article_title', 'time', 'ishidden', 'user_id', 'cat_title'

    ];

}
