<?php

namespace FlexPay\Laravel\Models;

use FlexPay\Laravel\Support\FlexPayStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FlexPayPayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount'           => 'decimal:2',
        'metadata'         => 'array',
        'response_payload' => 'array',
        'completed_at'     => 'datetime',
    ];

    public function getTable(): string
    {
        return config('flexpay.table_name', 'flexpay_payments');
    }

    /**
     * Entité métier associée (ex: Commande, Réservation, Facture).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope : Transactions en attente.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FlexPayStatus::STATE_PENDING);
    }

    /**
     * Scope : Transactions terminées avec succès.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', FlexPayStatus::STATE_COMPLETED);
    }

    /**
     * Scope : Transactions échouées.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', FlexPayStatus::STATE_FAILED);
    }

    /**
     * Valide le paiement de manière atomique (Protection anti-rejeu et concurrence).
     *
     * Seule une transaction à l'état 'pending' peut passer à 'completed'.
     * Si le polling et le webhook arrivent simultanément, seul le premier mettra à jour.
     */
    public function markAsCompleted(string $channel = 'mobile_money', ?array $payload = null): bool
    {
        $updated = static::where('id', $this->id)
            ->where('status', FlexPayStatus::STATE_PENDING)
            ->update([
                'status'           => FlexPayStatus::STATE_COMPLETED,
                'channel'          => $channel,
                'response_payload' => $payload ?? $this->response_payload,
                'completed_at'     => now(),
                'updated_at'       => now(),
            ]);

        if ($updated) {
            $this->refresh();
            return true;
        }

        return false;
    }

    /**
     * Marque la transaction comme échouée de façon sécurisée.
     */
    public function markAsFailed(string $reason, ?array $payload = null): bool
    {
        $updated = static::where('id', $this->id)
            ->where('status', FlexPayStatus::STATE_PENDING)
            ->update([
                'status'           => FlexPayStatus::STATE_FAILED,
                'error_message'    => $reason,
                'response_payload' => $payload ?? $this->response_payload,
                'updated_at'       => now(),
            ]);

        if ($updated) {
            $this->refresh();
            return true;
        }

        return false;
    }
}
