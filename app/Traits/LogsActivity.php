<?php
// app/Traits/LogsActivity.php
// ─────────────────────────────────────────────────────────────────
// Add this trait to ANY Model you want auto-logged.
// Then register its Observer in AppServiceProvider.
//
// Usage in model:
//   use App\Traits\LogsActivity;
//   class DailyRate extends Model {
//       use LogsActivity;
//       protected string $logModule = 'daily_rate'; // override module name
//       protected array  $logIgnore = ['updated_at','created_at']; // fields to skip in diff
//   }
// ─────────────────────────────────────────────────────────────────
namespace App\Traits;

trait LogsActivity
{
    // Override in model to set module name (default: lowercase class name)
    // protected string $logModule = 'daily_rate';

    // Override to skip certain fields from diff
    // protected array $logIgnore = ['updated_at', 'created_at', 'password'];

    public function getLogModule(): string
    {
        return property_exists($this, 'logModule')
            ? $this->logModule
            : strtolower(class_basename($this));
    }

    public function getLogIgnore(): array
    {
        $defaults = ['updated_at', 'created_at', 'deleted_at', 'remember_token', 'password'];
        return array_merge(
            $defaults,
            property_exists($this, 'logIgnore') ? $this->logIgnore : []
        );
    }

    public function getLogLabel(): string
    {
        // Override getLogLabel() in model for custom label
        // e.g. return $this->invoice_no ?? $this->name ?? "#{$this->id}";
        return (string)(
            $this->invoice_no    ??
            $this->tag_no        ??
            $this->purchase_no   ??
            $this->return_no     ??
            $this->enrollment_no ??
            $this->credit_no     ??
            $this->voucher_no    ??
            $this->name          ??
            $this->title         ??
            "#{$this->id}"
        );
    }
}