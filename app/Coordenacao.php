<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coordenacao extends Model {

    protected $fillable = [
        'name'
    ];
    
    public function setores() {
        return $this->hasMany('App\Setor');
    }
}
