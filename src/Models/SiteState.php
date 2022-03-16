<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class SiteState extends Model
{
    protected $table = "site_stats";

    protected $primaryKey = 'ss_row_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'ss_row_id',
        'ss_total_edits',
        'ss_good_articles',
        'ss_total_pages',
        'ss_users',
        'ss_active_users',
        'ss_images'
    ];
}
