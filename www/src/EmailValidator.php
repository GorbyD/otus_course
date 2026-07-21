<?php

class EmailValidator
{
    private const PATTERN = '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/';

    public function isValidSyntax(string $email): bool
    {
        return preg_match(self::PATTERN, $email) === 1;
    }

    public function hasMxRecord(string $email): bool
    {
        $domain = substr((string) strrchr($email, '@'), 1);

        if ($domain === '') {
            return false;
        }

        return checkdnsrr($domain);
    }

    public function isValid(string $email): bool
    {
        return $this->isValidSyntax($email) && $this->hasMxRecord($email);
    }

    public function validateList(array $lines): array
    {
        $results = [];

        foreach ($lines as $line) {
            $email = trim($line);

            if ($email === '') {
                continue;
            }

            $results[] = $this->buildResult($email);
        }

        return $results;
    }

    private function buildResult(string $email): array
    {
        if (!$this->isValidSyntax($email)) {
            return [
                'email' => $email,
                'valid' => false,
                'reason' => 'некорректный формат'
            ];
        }

        if (!$this->hasMxRecord($email)) {
            return [
                'email' => $email,
                'valid' => false,
                'reason' => 'домен не принимает почту (нет MX-записи)'
            ];
        }

        return [
            'email' => $email,
            'valid' => true,
            'reason' => null
        ];
    }
}
