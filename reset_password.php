<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$users = User::all();
foreach ($users as $u) {
    if ($u->email === 'admin@admin.com') {
        $u->role = 'admin';
    }
    $u->password = Hash::make('password');
    $u->save();
}
echo "All users' passwords reset to 'password'.\n";

