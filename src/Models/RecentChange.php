<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;


class RecentChange extends Model
{
    protected $table = "recentchanges";

    protected $primaryKey = null;
    public $incrementing = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'rc_id',
        'rc_timestamp',
        'rc_user',
        'rc_user_text',
        'rc_namespace',
        'rc_title',
        'rc_comment',
        'rc_minor',
        'rc_bot',
        'rc_new',
        'rc_cur_id',
        'rc_this_oldid',
        'rc_last_oldid',
        'rc_type',
        'rc_patrolled',
        'rc_ip',
        'rc_old_len',
        'rc_new_len',
        'rc_deleted',
        'rc_logid',
        'rc_log_type',
        'rc_log_action',
        'rc_params',
        'rc_source',

    ];


}
