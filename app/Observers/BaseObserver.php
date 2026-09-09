<?php
// app/Observers/BaseObserver.php
// ─────────────────────────────────────────────────────────────────
// One observer class handles ALL models that use LogsActivity trait.
// Register once per model in AppServiceProvider.
// ─────────────────────────────────────────────────────────────────
namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class BaseObserver
{
    // ── CREATE ──────────────────────────────────────────────────────
    public function created(Model $model): void
    {
        if (!$this->isLoggable($model)) return;

        ActivityLog::log(
            module:      $model->getLogModule(),
            action:      'created',
            recordId:    $model->id,
            recordLabel: $model->getLogLabel(),
            oldValues:   [],
            newValues:   $this->cleanValues($model, $model->getAttributes()),
            note:        ''
        );
    }

    // ── UPDATE ──────────────────────────────────────────────────────
    public function updated(Model $model): void
    {
        if (!$this->isLoggable($model)) return;

        $dirty = $model->getDirty();  // only changed fields
        if (empty($dirty)) return;

        $ignore = $model->getLogIgnore();

        $oldValues = [];
        $newValues = [];

        foreach ($dirty as $field => $newVal) {
            if (in_array($field, $ignore)) continue;

            $oldValues[$field] = $model->getOriginal($field);
            $newValues[$field] = $newVal;
        }

        if (empty($oldValues) && empty($newValues)) return;

        ActivityLog::log(
            module:      $model->getLogModule(),
            action:      'edited',
            recordId:    $model->id,
            recordLabel: $model->getLogLabel(),
            oldValues:   $oldValues,
            newValues:   $newValues,
            note:        ''
        );
    }

    // ── DELETE ──────────────────────────────────────────────────────
    public function deleted(Model $model): void
    {
        if (!$this->isLoggable($model)) return;

        ActivityLog::log(
            module:      $model->getLogModule(),
            action:      'deleted',
            recordId:    $model->id,
            recordLabel: $model->getLogLabel(),
            oldValues:   $this->cleanValues($model, $model->getAttributes()),
            newValues:   [],
            note:        ''
        );
    }

    // ── SOFT DELETE RESTORE ──────────────────────────────────────────
    public function restored(Model $model): void
    {
        if (!$this->isLoggable($model)) return;

        ActivityLog::log(
            module:      $model->getLogModule(),
            action:      'status_change',
            recordId:    $model->id,
            recordLabel: $model->getLogLabel(),
            oldValues:   ['status' => 'deleted'],
            newValues:   ['status' => 'restored'],
            note:        'Record restored'
        );
    }

    // ── HELPERS ─────────────────────────────────────────────────────
    private function isLoggable(Model $model): bool
    {
        return method_exists($model, 'getLogModule');
    }

    private function cleanValues(Model $model, array $values): array
    {
        $ignore = $model->getLogIgnore();
        return array_filter(
            $values,
            fn($key) => !in_array($key, $ignore),
            ARRAY_FILTER_USE_KEY
        );
    }
}