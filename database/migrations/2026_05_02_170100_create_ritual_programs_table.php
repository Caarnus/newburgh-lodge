<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ritual_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('points');
            $table->string('degree_group', 32);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        $defaults = [
            ['degree_group' => 'entered_apprentice', 'name' => 'Opened Lodge on EA Degree', 'points' => 15],
            ['degree_group' => 'entered_apprentice', 'name' => 'Worshipful Master 1st Section', 'points' => 90],
            ['degree_group' => 'entered_apprentice', 'name' => 'E.A. Charge', 'points' => 30],
            ['degree_group' => 'entered_apprentice', 'name' => 'E.A. Memory Lecture Initial', 'points' => 85],
            ['degree_group' => 'entered_apprentice', 'name' => 'E.A. Lecture 2nd Section (slide #1)', 'points' => 50],
            ['degree_group' => 'entered_apprentice', 'name' => 'E.A. Lecture 3rd Section (slide #2)', 'points' => 70],

            ['degree_group' => 'fellow_craft', 'name' => 'Opened Lodge on FC Degree', 'points' => 15],
            ['degree_group' => 'fellow_craft', 'name' => '1st Section Worshipful Master', 'points' => 90],
            ['degree_group' => 'fellow_craft', 'name' => 'F.C. Middle Chamber Lecture Traditional', 'points' => 225],
            ['degree_group' => 'fellow_craft', 'name' => 'F.C. Middle Chamber Lecture Abbreviated Version', 'points' => 135],
            ['degree_group' => 'fellow_craft', 'name' => "F.C. Letter 'G' Lecture", 'points' => 30],
            ['degree_group' => 'fellow_craft', 'name' => 'F.C. Memory Lecture Initial', 'points' => 85],
            ['degree_group' => 'fellow_craft', 'name' => 'F.C. Charge', 'points' => 30],

            ['degree_group' => 'master_mason', 'name' => 'Opened Lodge on MM Degree', 'points' => 15],
            ['degree_group' => 'master_mason', 'name' => 'Worshipful Master 1st Section', 'points' => 90],
            ['degree_group' => 'master_mason', 'name' => 'M.M. Charge', 'points' => 30],
            ['degree_group' => 'master_mason', 'name' => 'M.M. Memory Lecture Initial', 'points' => 85],
            ['degree_group' => 'master_mason', 'name' => 'M.M. 2nd Section Lecture (slide #1)', 'points' => 60],
            ['degree_group' => 'master_mason', 'name' => 'M.M. 3rd Section Lecture (slide #2)', 'points' => 60],
            ['degree_group' => 'master_mason', 'name' => 'Senior Deacon (Conducts candidate to 3rd R)', 'points' => 15],
            ['degree_group' => 'master_mason', 'name' => '1st Ruffian', 'points' => 30],
            ['degree_group' => 'master_mason', 'name' => '2nd Ruffian', 'points' => 30],
            ['degree_group' => 'master_mason', 'name' => '3rd Ruffian', 'points' => 15],
            ['degree_group' => 'master_mason', 'name' => 'Sea Captain', 'points' => 10],
            ['degree_group' => 'master_mason', 'name' => '1st Fellow Craft', 'points' => 75],
            ['degree_group' => 'master_mason', 'name' => '2nd Fellow Craft', 'points' => 30],
            ['degree_group' => 'master_mason', 'name' => '3rd Fellow Craft', 'points' => 20],
            ['degree_group' => 'master_mason', 'name' => '4th Fellow Craft', 'points' => 10],
            ['degree_group' => 'master_mason', 'name' => '5th Fellow Craft', 'points' => 10],
            ['degree_group' => 'master_mason', 'name' => '6th Fellow Craft', 'points' => 10],
            ['degree_group' => 'master_mason', 'name' => 'Hiram King of Tyre', 'points' => 20],
            ['degree_group' => 'master_mason', 'name' => 'King Solomon', 'points' => 30],
            ['degree_group' => 'master_mason', 'name' => 'Wayfaring Man', 'points' => 10],
            ['degree_group' => 'master_mason', 'name' => 'Graveside Prayer', 'points' => 30],

            ['degree_group' => 'optional', 'name' => 'Master Mason Bible Presentation', 'points' => 60],
            ['degree_group' => 'optional', 'name' => 'E.A. Apron Lecture', 'points' => 30],
            ['degree_group' => 'optional', 'name' => '3rd Ruffian Soliloquy', 'points' => 30],
            ['degree_group' => 'optional', 'name' => 'M.M. Optional Charge (Yonder Book)', 'points' => 45],
            ['degree_group' => 'optional', 'name' => 'Memorial Service', 'points' => 90],
            ['degree_group' => 'optional', 'name' => 'Past Masters Degree Initial', 'points' => 90],
            ['degree_group' => 'optional', 'name' => 'Grand Lodge Vault Ritual Review', 'points' => 20],
        ];

        $now = now();
        $rows = collect($defaults)
            ->groupBy('degree_group')
            ->flatMap(function ($group) use ($now) {
                return $group->values()->map(function (array $program, int $index) use ($now) {
                    $program['display_order'] = $index + 1;
                    $program['created_at'] = $now;
                    $program['updated_at'] = $now;

                    return $program;
                });
            })
            ->values()
            ->all();

        DB::table('ritual_programs')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('ritual_programs');
    }
};
