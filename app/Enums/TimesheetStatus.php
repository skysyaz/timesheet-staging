<?php

namespace App\Enums;

enum TimesheetStatus: string
{
    case DRAFT = 'draft';
    case PENDING_PM = 'pending_pm';
    case PENDING_PROGRAM_MANAGER = 'pending_program_manager';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_PM => 'Pending PM',
            self::PENDING_PROGRAM_MANAGER => 'Pending Program Manager',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function isEditable(): bool
    {
        return match ($this) {
            self::DRAFT, self::REJECTED => true,
            default => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
