<?php


namespace Mawdoo3\Waraqa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ApprovedRevision extends Model
{
    protected $table = "approved_revs";

    protected $primaryKey = 'page_id';

    protected $fillable = [
        'page_id',
        'rev_id',
    ];

    /**
     * @return BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'rev_id');
    }

}
