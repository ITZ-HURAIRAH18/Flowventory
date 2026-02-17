// database/seeders/RoleSeeder.php
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super_admin', 'branch_manager', 'sales_user'];
        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }
}