<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserCsvSeeder extends Seeder
{
    public function run()
    {
        $path = storage_path('app/seeders/users.csv');

        if (!file_exists($path)) {
            $this->command->error("CSV file not found!");
            return;
        }

        $file = fopen($path, 'r');

        $header = fgetcsv($file); // first row (column names)

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);

            DB::table('users')->insert([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'created_at' => $data['created_at'],   // different times from CSV
                'updated_at' => now(),
            ]);
        }

        fclose($file);

        $this->command->info("Users seeded successfully from CSV!");
    }
}
