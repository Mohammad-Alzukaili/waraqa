<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ExternalLinks extends Model
{
    protected $table = "externallinks";

    protected $primaryKey = false;
    public $incrementing = false;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'el_id',
        'el_from',
        'el_to',
        'el_index',
    ];


    /**
     * @return BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'el_from', 'page_id');
    }
}
