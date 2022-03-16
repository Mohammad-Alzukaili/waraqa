<?php

namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;


class WaraqaUser extends Model
{
    protected $table = "waraqa_users";

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'user_id',
        'user_name',
        'jobs',
        'social',
        'speciality',
        'gender',
        'bio',
        'picture'
    ];



    public function user(){
        return $this->hasOne(MediaWikiUser::class,'user_id','user_id');
    }

}
