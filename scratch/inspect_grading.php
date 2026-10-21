<?php
use App\Models\{Assessment, AssessmentScore, GradingConfiguration};

echo "--- Configurations\n";
GradingConfiguration::withCount('components')->get()->each(fn($c) =>
    print("#{$c->id} {$c->name} | {$c->status} | term " . ($c->term_id ?? 'all') . " | {$c->components_count} components | created {$c->created_at}\n"));

echo "--- Assessments\n";
Assessment::withTrashed()->withCount('scores')->get()->each(fn($a) =>
    print("#{$a->id} '{$a->title}' | class {$a->class_assignment_id} | term {$a->term_id} | items {$a->total_items} | {$a->scores_count} scores | created {$a->created_at}" . ($a->deleted_at ? ' | DELETED' : '') . "\n"));
