<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Text extends Model
{
    protected $table = "text";

    protected $primaryKey = 'old_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'old_id',
        'old_text',
        'old_flags',

    ];

    /**
     * @return HasOne
     */
    public function revision(): HasOne
    {
        return $this->hasOne(Revision::class, 'rev_text_id', 'old_id');
    }


    public function page(): HasOne
    {
        return $this->hasOne(Page::class, 'rev_text_id');
    }

}
