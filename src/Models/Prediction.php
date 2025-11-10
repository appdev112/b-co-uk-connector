<?php

namespace Bwise\BcoUkConnector\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    protected $connection = 'b_co_uk';

    protected $guarded = [];
}