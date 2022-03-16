<?php

namespace App\Models;

use App\Traits\SearchTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Newsletter extends Model
{
    use HasFactory, LogsActivity, SearchTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'newsletter_pdf_id',
        'user_id',
        'title',
        'description',
        'date',
    ];

    protected $serach_with = ['newsletter_pdfs'];

    protected static $logFillable = true;

    protected static $logName = 'newsletter';

    /**
     * Get the newsletter documents
     */
    public function newsletter_pdfs()
    {
        return $this->hasMany(NewsletterPdf::class);
    }

    /**
     * Load all relations
     * @param $query
     */
    public function scopeWithAll($query)
    {
        $query->with('newsletter_pdfs');
    }

    /**
     *
     */
    public function previous()
    {
        return $this->belongsTo(Newsletter::class, 'newsletters_previous_id');
    }

    /**
     *
     */
    public function scopeWithPrevious($query)
    {

        $query->addSelect(['newsletters_previous_id' => Newsletter::from('newsletters as newsletters_previous')->select('id')
            ->whereColumn('newsletters_previous.date', '<', 'newsletters.date')
            ->orderByDesc('date')
            ->take(1)
        ])->with('previous');
    }

    /**
     *
     */
    public function next()
    {
        return $this->belongsTo(Newsletter::class, 'newsletters_next_id');
    }

     /**
     *
     */
    public function scopeWithNext($query)
    {

        $query->addSelect(['newsletters_next_id' => Newsletter::from('newsletters as newsletters_next')->select('id')
            ->whereColumn('newsletters_next.date', '>', 'newsletters.date')
            ->orderBy('date')
            ->take(1)
        ])->with('next');
    }





}
