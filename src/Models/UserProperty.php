<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


class UserProperty extends Model
{
    protected $table = "user_properties";



    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'up_user',
        'up_property',
        'up_value'
    ];


    public function user(){
        return $this->belongsTo('App\Models\MediaWikiUser','up_user','user_id');
    }

}
