<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait SearchTrait
{

    public static function bootSearchTrait()
    {
        static::created(function (Model $model) {
            (new self)->createdModel($model);
        });
        static::updated(function (Model $model) {
            (new self)->updatedModel($model);
        });

        static::saved(function (Model $model) {
            (new self)->savedModel($model);
        });

        static::deleted(function (Model $model) {
            (new self)->deletedModel($model);
        });
    }

    /**
     * Handle the Model "created" event.
     *
     * @param \App\Models\ $model
     * @return void
     */
    public function createdModel($model)
    {
        \App\Models\SearchIndex::create([
            'type' => get_class($model),
            'type_id' => $model->id,
            'data' => $model
        ]);
    }

    /**
     * Handle the Model "updated" event.
     *
     * @param \App\Models\ $model
     * @return void
     */
    public function updatedModel($model)
    {
        \App\Models\SearchIndex::updateOrCreate(
            [
                'type' => get_class($model),
                'type_id' => $model->id
            ],
            [
                'data' => $model->load($model->serach_with)
            ]
        );
    }

    /**
     * Handle the Model "saving" event.
     *
     * @param \App\Models\ $model
     * @return void
     */
    public function savedModel($model)
    {
        \App\Models\SearchIndex::updateOrCreate(
            [
                'type' => get_class($model),
                'type_id' => $model->id
            ],
            [
                'data' => $model->load($model->serach_with)
            ]
        );
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @param \App\Models $model
     * @return void
     */
    public function deletedModel($model)
    {
        \App\Models\SearchIndex::where('type_id', $model->id)->delete();
    }


}
