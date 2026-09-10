<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Sms\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Por omissão 'email' — uma integração actual que nunca envie
            // 'channel' continua a enviar e-mail exactamente como hoje (CA-01).
            'channel' => ['sometimes', 'string', 'in:email,sms'],
            'to' => ['required', 'string', ...$this->toRules($this->channel())],
            'template' => ['required_without:content', 'string'],
            'content' => ['required_without:template', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
        ];
    }

    public function channel(): string
    {
        return $this->input('channel', 'email');
    }

    /**
     * @return array<int, string|Closure>
     */
    private function toRules(string $channel): array
    {
        return match ($channel) {
            'sms' => [function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! PhoneNumber::isValidForMozambique($value)) {
                    $fail('O número de telefone tem de ser um número moçambicano válido (82–87, 9 dígitos, com ou sem +258).');
                }
            }],
            default => ['email'],
        };
    }
}
