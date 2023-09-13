<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nodes extends Model
{
    protected $title = '节点';

    protected $table = 'nodes';
    protected $guarded = ['id'];
}