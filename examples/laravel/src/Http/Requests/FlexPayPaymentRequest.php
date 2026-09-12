<?php

namespace FlexPay\Laravel\Http\Requests;

use FlexPay\Laravel\Support\OperatorDetector;
use Illuminate\Foundation\Http\FormRequest;

class FlexPayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!OperatorDetector::isValidDrcPhone($value)) {
                        $fail('Le numéro doit être un numéro valide de RDC (Airtel, Vodacom, Orange ou Africell).');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'in:USD,CDF'],
            'reference' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone Mobile Money est obligatoire.',
            'amount.required' => 'Le montant du paiement est obligatoire.',
            'amount.min' => 'Le montant doit être supérieur à zéro.',
            'currency.in' => 'La devise doit être USD ou CDF.',
        ];
    }
}
