<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property int $id
 * @property string $name
 * @property string $slug
 */
final class Category extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug'];
}
