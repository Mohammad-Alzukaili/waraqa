<?php

namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;


class MediaWikiUser extends Model
{
    protected $table = "user";

    protected $primaryKey = null;
    public $incrementing = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_real_name',
        'user_password',
        'user_newpassword',
        'user_newpass_time',
        'user_email',
        'user_touched',
        'user_token',
        'user_email_authenticated',
        'user_email_token',
        'user_email_token_expires',
        'user_registration',
        'user_editcount',
        'user_password_expires',
    ];

    protected $hidden = [
        'user_password',
        'user_newpassword'
    ];



    public function userProperties(){
        return $this->hasMany(UserProperty::class);
    }

    public function waraqaUser(){
        return $this->belongsTo(WaraqaUser::class);
    }

}
