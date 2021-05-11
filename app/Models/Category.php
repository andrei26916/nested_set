<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{

    use HasFactory, NodeTrait;

    protected $fillable = [
        'id',
        'category',
        '_lft',
        '_rgt',
        'parent_id'
    ];

    protected $hidden = [
        '_lft',
        '_rgt',
        'created_at',
        'updated_at',
        'parent_id'
    ];

    /**
     * @param array $attributes
     * @param Category|null $parent
     * @return Category
     */
    public static function create(array $attributes = [], self $parent = null)
    {
        $children = Arr::pull($attributes, 'subcategories');

        $instance = new static($attributes);

        if ($parent) {
            $instance->appendToNode($parent);
        }

        $instance->save();

        // Now create children
        $relation = new EloquentCollection;

        foreach ((array)$children as $child) {
            $relation->add($child = static::create($child, $instance));

            $child->setRelation('parent', $instance);
        }

        $instance->refreshNode();

        return $instance->setRelation('subcategories', $relation);
    }
}
