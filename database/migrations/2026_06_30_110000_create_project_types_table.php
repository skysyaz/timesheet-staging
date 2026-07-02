<?php

use App\Models\ProjectType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $types = [
            ['name' => 'Product Development', 'slug' => 'product-development', 'sort_order' => 1],
            ['name' => 'Maintenance', 'slug' => 'maintenance', 'sort_order' => 2],
            ['name' => 'Work Order', 'slug' => 'work-order', 'sort_order' => 3],
        ];

        foreach ($types as $type) {
            DB::table('project_types')->insert([
                ...$type,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $defaultTypeId = DB::table('project_types')
            ->where('slug', 'product-development')
            ->value('id');

        Schema::table('projects', function (Blueprint $table) use ($defaultTypeId) {
            $table->foreignId('project_type_id')
                ->default($defaultTypeId)
                ->after('description')
                ->constrained('project_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_type_id');
        });

        Schema::dropIfExists('project_types');
    }
};
