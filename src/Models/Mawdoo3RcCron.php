<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Mawdoo3RcCron extends Model
{
    protected $table = "mawdoo3_rc_cron";

    protected $primaryKey = 'rc_cur_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'rc_cur_id',
        'rc_deleted',
        'rc_title',
        'rc_namespace',
        'hide_approving',
        'rc_user_text',
        'rc_timestamp',
        'status',
        'rc_bot',
        'rc_type',
        'rc_type_0',
        'rc_type_1',
        'rc_type_2',
        'rc_type_3',
        'rc_patrolled',
        'reviews_count',
        'rc_original_user_text',
        'rc_original_user',
        'rc_first_timestamp',
        'rc_latest_user_text',
        'max_not_deleted_article',
        'patrolled_max_not_deleted_article',
        'latest_modifyer',
        'current_rev_id',
        'has_hide_approving',
        'has_rc_patrolled',
        'last_updated',
        'needs_update'
    ];

}
