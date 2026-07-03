<?php

namespace Database\Seeders;

use App\Models\BroadcastTemplate;
use Illuminate\Database\Seeder;

class BroadcastTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Welcome',
                'subject' => 'Your Quatriz TimeSheet account is ready',
                'body' => "Welcome to Quatriz TimeSheet! Your account has been created.\n\nUse the link below to set your password, then sign in to start logging your weekly hours. If you have any questions, reach out to your project admin.",
            ],
            [
                'name' => 'Password reset reminder',
                'subject' => 'Set your Quatriz TimeSheet password',
                'body' => "This is a reminder to set your password for Quatriz TimeSheet.\n\nClick the link below to choose a password and activate your account.",
            ],
        ];

        foreach ($templates as $template) {
            BroadcastTemplate::firstOrCreate(
                ['name' => $template['name']],
                ['subject' => $template['subject'], 'body' => $template['body']],
            );
        }
    }
}
