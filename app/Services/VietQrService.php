<?php

namespace App\Services;

class VietQrService
{
    public const ORDER_TAB = ['mb', 'tcb', 'vpb', 'momo'];

    public static function maxLength(): int
    {
        return (int) config('vietqr.max_add_info', 25);
    }

    public static function accountName(): string
    {
        return trim((string) config('vietqr.account_name', ''));
    }

    public static function template(): string
    {
        $template = trim((string) config('vietqr.template', 'compact2'));

        return $template !== '' ? $template : 'compact2';
    }

    public static function normalizeAmount(mixed $amount): int
    {
        return (int) floor((float) $amount);
    }

    public static function isValidAmount(mixed $amount): bool
    {
        return self::normalizeAmount($amount) > 0;
    }

    /**
     * Normalize free text: uppercase, strip Vietnamese diacritics,
     * keep only A-Z 0-9 and single spaces.
     */
    public static function normalizeToken(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }

        // Strip transliteration artifacts (a' a^ d~ ...) without splitting words.
        $value = str_replace(["'", '"', '`', '^', '~'], '', $value ?? '');

        $value = preg_replace('/[^A-Z0-9 ]+/', ' ', $value ?? '');
        $value = preg_replace('/\s+/', ' ', (string) $value);

        return trim((string) $value);
    }

    /**
     * ID-first addInfo: ID part is never truncated, only the
     * description tail is cut to fit MAX length.
     */
    public static function normalizeAddInfo(string $idPart, string $descPart = ''): string
    {
        $max = self::maxLength();
        $id = self::normalizeToken($idPart);
        $desc = self::normalizeToken($descPart);

        if ($id === '') {
            return mb_substr($desc, 0, $max);
        }

        // Never cut the identifier, even if it alone exceeds MAX.
        if (mb_strlen($id) >= $max || $desc === '') {
            return $desc === '' ? $id : mb_substr($id.' '.$desc, 0, $max);
        }

        $remaining = $max - mb_strlen($id) - 1;
        $desc = mb_substr($desc, 0, $remaining);

        return $desc !== '' ? $id.' '.$desc : $id;
    }

    public static function orderAddInfo(int $orderId, string $suffix = ''): string
    {
        return self::normalizeAddInfo('DH'.$orderId, $suffix);
    }

    public static function depositAddInfo(int $reservationId, int $batchId): string
    {
        return self::normalizeAddInfo('R'.$reservationId, 'COC B'.$batchId);
    }

    public static function balanceAddInfo(int $reservationId): string
    {
        return self::normalizeAddInfo('R'.$reservationId, 'TT');
    }

    /**
     * Only methods with complete config are returned.
     * Bank: bank_id + account_no. MoMo wallet: account_no.
     */
    public static function validMethods(): array
    {
        $methods = config('vietqr.methods', []);
        $valid = [];

        foreach (self::ORDER_TAB as $key) {
            if (! isset($methods[$key]) || ! is_array($methods[$key])) {
                continue;
            }

            $method = $methods[$key];
            $type = $method['type'] ?? 'bank';

            if ($type === 'wallet') {
                if (trim((string) ($method['account_no'] ?? '')) === '') {
                    continue;
                }
                $valid[$key] = [
                    'key' => $key,
                    'label' => $method['label'] ?? $key,
                    'type' => 'wallet',
                    'account_no' => trim((string) $method['account_no']),
                ];
                continue;
            }

            $bankId = trim((string) ($method['bank_id'] ?? ''));
            $accountNo = trim((string) ($method['account_no'] ?? ''));

            if ($bankId === '' || $accountNo === '') {
                continue;
            }

            $valid[$key] = [
                'key' => $key,
                'label' => $method['label'] ?? $key,
                'type' => 'bank',
                'bank_id' => $bankId,
                'account_no' => $accountNo,
                'account_name' => self::accountName(),
                'template' => self::template(),
            ];
        }

        return $valid;
    }

    public static function hasValidMethods(): bool
    {
        return count(self::validMethods()) > 0;
    }

    public static function defaultMethod(array $validMethods = []): ?string
    {
        $validMethods = $validMethods ?: self::validMethods();

        if (empty($validMethods)) {
            return null;
        }

        $configured = (string) config('vietqr.default', 'mb');

        if (isset($validMethods[$configured])) {
            return $configured;
        }

        return array_key_first($validMethods);
    }

    public static function buildBankUrl(string $bankId, string $accountNo, string $template, int $amount, string $addInfo): string
    {
        $base = 'https://img.vietqr.io/image/'.rawurlencode($bankId).'-'.rawurlencode($accountNo).'-'.rawurlencode($template).'.png';
        $query = 'amount='.$amount.'&addInfo='.rawurlencode($addInfo);

        $accountName = self::accountName();
        if ($accountName !== '') {
            $query .= '&accountName='.rawurlencode($accountName);
        }

        return $base.'?'.$query;
    }

    /**
     * Shared payload for all payment views. Amount/addInfo are identical
     * across tabs; each bank tab only differs by its QR URL.
     */
    public static function buildOptions(mixed $amount, string $addInfo): array
    {
        $amount = self::normalizeAmount($amount);
        $valid = self::validMethods();
        $methods = [];

        foreach ($valid as $key => $method) {
            if ($method['type'] === 'bank') {
                $method['qr_url'] = $amount > 0
                    ? self::buildBankUrl($method['bank_id'], $method['account_no'], $method['template'], $amount, $addInfo)
                    : null;
            }
            $methods[$key] = $method;
        }

        return [
            'amount' => $amount,
            'addInfo' => $addInfo,
            'amount_valid' => $amount > 0,
            'has_methods' => ! empty($methods),
            'default' => self::defaultMethod($valid),
            'methods' => $methods,
        ];
    }
}
