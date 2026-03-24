<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCoinPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        //apenas os users com o tipo P (autenticados) podem comprar coins e não podem estar bloqueados
        return $this->user() !== null
            && ((int)($this->user()->blocked ?? 0) === 0)
            && (($this->user()->type ?? 'P') === 'P');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['MBWAY', 'PAYPAL', 'IBAN', 'MB', 'VISA'])],
            'reference' => ['required', function ($attribute, $value, $fail) {
                $type = $this->input('type');
                if (!$type) {
                    $fail('O tipo de pagamento é obrigatório para validar a referência!');
                    return;
                }

                switch ($type) {
                    case 'MBWAY':
                        //9 digitos a começar por 9
                        if (!preg_match('/^9\d{8}$/', $value)) {
                            $fail('Formato de referência MBWAY inválido. 9 dígitos a começar por 9!');
                        }
                        break;
                    case 'PAYPAL':
                        //um email válido
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail('Formato de email PAYPAL inválido.');
                        }
                        break;
                    case 'IBAN':
                        //2 letras seguidas de 23 digitos
                        if (!preg_match('/^[A-Z]{2}\d{23}$/', strtoupper($value))) {
                            $fail('Formato de IBAN inválido. 2 letras seguidas de 23 dígitos!');
                        }
                        break;
                    case 'MB':
                        //5 digitos, hífen, 9 digitos
                        if (!preg_match('/^\d{5}-\d{9}$/', $value)) {
                            $fail('Formato de referência MB inválido. 5 dígitos, hífen, 9 dígitos!');
                        }
                        break;
                    case 'VISA':
                        //16 digitos a começar por 4
                        if (!preg_match('/^4\d{15}$/', $value)) {
                            $fail('Formato de número VISA inválido. 16 dígitos a começar por 4!');
                        }
                        break;
                }
            }],
            'value' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'O tipo de pagamento é obrigatório.',
            'type.in' => 'O tipo de pagamento tem de ser MBWAY, PAYPAL, IBAN, MB ou VISA.',
            'reference.required' => 'A referência de pagamento é obrigatória.',
            'value.required' => 'O valor da compra (euros) é obrigatório.',
            'value.integer' => 'O valor da compra tem de ser um número inteiro de euros.',
            'value.min' => 'O valor da compra tem de ser no mínimo 1 euro.',
            'value.max' => 'O valor da compra tem de ser no máximo 99 euros.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Apenas jogadores não bloqueados podem comprar coins.'
        ], 403));
    }
}
