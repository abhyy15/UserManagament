<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkUserCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userData;

    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    public function handle()
    {
        \Log::info('Running job for user: ' . $this->userData['email']);


        User::updateOrCreate(
            ['email' => $this->userData['email']],
            [
                'name'     => $this->userData['name'],
                'password' => $this->userData['password'],
                'role_id'  => $this->userData['role_id'],
            ]
        );
    }
}
