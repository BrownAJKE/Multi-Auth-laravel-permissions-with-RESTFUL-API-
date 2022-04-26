<!-- <?php 
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Multitenantable {

    protected static function bootMultitenantable(){
        if (auth()->check()){
            static::creating(function ($model) {
                $model->team_id = auth()->id();
            });

            //Global filter scope for quering data from mode
            //Exclude super admin role
            if(auth()->user()->role() != 'Super-Admin'){
                static::addGlobalScope('team_id', function(Builder $builder){
                    $builder->where('team_id', auth()->id());
                });
            }
        }
    }

} -->