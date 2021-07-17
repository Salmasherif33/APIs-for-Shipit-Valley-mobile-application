<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        /*inserting dummy data, if we want to insert a 1000 of data,every seeder foreach table, it insert 1 data*/
        // DB::table('users')->insert([
        //     'name'=>str_Random(10), 
        //     'email'=>str_random(10).'@gmail.com',
        //     'password'=>bcrypt('secret')
        // ]);
        //ro run : php artisan db:seed


        /**ANOTHER WAY */

        //User::factory()->count(10)->hasPosts(1)->create(); //if the user has a relation with post
        User::factory()->count(10)->create();

        
        
    }
}
