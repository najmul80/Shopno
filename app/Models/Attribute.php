<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Attribute extends Model
{
    use HasFactory; // Add HasFactory if you generated it
    protected $fillable = ['name', 'display_name'];
    public function values() { return $this->hasMany(AttributeValue::class); }
}